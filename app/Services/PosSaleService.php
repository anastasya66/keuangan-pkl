<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosSaleService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Cari produk aktif untuk kasir berdasarkan barcode, SKU, atau nama produk.
     */
    public function searchProducts(string $query = '', int $limit = 250): Collection
    {
        $q = Product::query()
            ->with(['category', 'unit', 'consignor'])
            ->where('is_active', true);

        if (!empty($query)) {
            $trimmed = trim($query);
            $q->where(function ($sub) use ($trimmed) {
                $sub->where('barcode', $trimmed)
                    ->orWhere('sku', 'like', "%{$trimmed}%")
                    ->orWhere('name', 'like', "%{$trimmed}%");
            });
        }

        return $q->orderBy('name')->limit($limit)->get();
    }

    /**
     * Cari produk spesifik berdasarkan barcode exact match.
     */
    public function findByBarcode(string $barcode): ?Product
    {
        return Product::with(['category', 'unit', 'consignor'])
            ->where('barcode', trim($barcode))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Eksekusi transaksi penjualan kasir (Checkout POS) secara atomik.
     */
    public function checkout(array $data, int $cashierId): Sale
    {
        return DB::transaction(function () use ($data, $cashierId) {
            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw ValidationException::withMessages([
                    'items' => 'Keranjang belanja kasir tidak boleh kosong.',
                ]);
            }

            $saleDate = $data['sale_date'] ?? now()->toDateString();
            $transactionNumber = Sale::generateTransactionNumber($saleDate);
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $transactionDiscount = (float) ($data['discount_amount'] ?? 0);
            $taxAmount = (float) ($data['tax_amount'] ?? 0);

            // 1. Ekstrak seluruh product ID dan lakukan row locking
            $productIds = array_column($itemsData, 'product_id');
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0.0;
            $totalCogs = 0.0;
            $preparedItems = [];

            foreach ($itemsData as $item) {
                $productId = (int) $item['product_id'];
                /** @var Product|null $product */
                $product = $products->get($productId);

                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => "Produk ID {$productId} tidak ditemukan di sistem.",
                    ]);
                }

                if (!$product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "Produk '{$product->name}' berstatus nonaktif dan tidak dapat dijual.",
                    ]);
                }

                $qty = (float) $item['quantity'];
                if ($qty <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' => "Kuantitas untuk produk '{$product->name}' harus lebih dari 0.",
                    ]);
                }

                // VALIDASI STOK: Cegah stok minus
                $currentStock = (float) $product->stock;
                if ($currentStock < $qty) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok produk '{$product->name}' tidak mencukupi (Tersedia: {$currentStock}, Diminta: {$qty}).",
                    ]);
                }

                $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->selling_price;
                $itemDiscount = (float) ($item['discount_amount'] ?? 0);
                $itemSubtotal = max(0.00, round(($qty * $unitPrice) - $itemDiscount, 2));

                $isConsignment = (bool) $product->is_consignment;
                $consignorId = $product->consignor_id;

                if ($isConsignment) {
                    $consignmentCost = (float) $product->consignment_cost_price;
                    $commissionPerUnit = max(0.00, round($unitPrice - $consignmentCost, 2));
                    $costPrice = $consignmentCost;
                    // HPP toko sendiri TIDAK dibebani barang konsinyasi
                    $itemCogs = 0.00;
                } else {
                    $consignmentCost = 0.00;
                    $commissionPerUnit = 0.00;
                    // Snapshot HPP per unit (Moving Average Cost, fallback purchase_price)
                    $costPrice = (float) $product->effective_cost;
                    $itemCogs = round($qty * $costPrice, 2);
                }

                $subtotal += $itemSubtotal;
                $totalCogs += $itemCogs;

                $stockAfter = round($currentStock - $qty, 2);

                $preparedItems[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'is_consignment' => $isConsignment,
                    'consignor_id' => $consignorId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'consignment_cost' => round($qty * $consignmentCost, 2),
                    'discount_amount' => $itemDiscount,
                    'commission_amount' => round($qty * $commissionPerUnit, 2),
                    'subtotal' => $itemSubtotal,
                    'cogs_total' => $itemCogs,
                    'stock_before' => $currentStock,
                    'stock_after' => $stockAfter,
                ];
            }

            $grandTotal = max(0.00, round($subtotal - $transactionDiscount + $taxAmount, 2));
            $paidAmount = (float) ($data['paid_amount'] ?? $grandTotal);

            // Validasi kecukupan pembayaran tunai
            if ($paymentMethod === 'cash' && $paidAmount < $grandTotal) {
                throw ValidationException::withMessages([
                    'paid_amount' => "Nominal uang yang dibayarkan (Rp " . number_format($paidAmount, 2) . ") kurang dari total belanja (Rp " . number_format($grandTotal, 2) . ").",
                ]);
            }

            // Validasi status lunas untuk QRIS jika order_id disertakan
            if ($paymentMethod === 'qris' && !empty($data['order_id'])) {
                $payTx = PaymentTransaction::where('order_id', $data['order_id'])->first();
                if ($payTx && !$payTx->isPaid()) {
                    throw ValidationException::withMessages([
                        'payment' => "Pembayaran QRIS untuk order {$data['order_id']} belum berstatus sukses / lunas.",
                    ]);
                }
            }

            $changeAmount = $paymentMethod === 'cash' ? max(0.00, round($paidAmount - $grandTotal, 2)) : 0.00;

            // 2. Simpan master transaksi penjualan
            $sale = Sale::create([
                'transaction_number' => $transactionNumber,
                'cashier_id' => $cashierId,
                'customer_name' => !empty($data['customer_name']) ? trim($data['customer_name']) : 'Pelanggan Umum',
                'sale_date' => $saleDate,
                'subtotal' => $subtotal,
                'discount_amount' => $transactionDiscount,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'cogs_total' => $totalCogs,
                'notes' => $data['notes'] ?? null,
            ]);

            // Kaitkan transaksi gateway jika ada
            if (!empty($data['order_id'])) {
                PaymentTransaction::where('order_id', $data['order_id'])->update(['sale_id' => $sale->id]);
            }

            // 3. Simpan detail item & kurangi stok & catat mutasi stok
            foreach ($preparedItems as $prep) {
                $sale->items()->create([
                    'product_id' => $prep['product_id'],
                    'is_consignment' => $prep['is_consignment'],
                    'consignor_id' => $prep['consignor_id'],
                    'quantity' => $prep['quantity'],
                    'unit_price' => $prep['unit_price'],
                    'cost_price' => $prep['cost_price'],
                    'consignment_cost' => $prep['consignment_cost'],
                    'discount_amount' => $prep['discount_amount'],
                    'commission_amount' => $prep['commission_amount'],
                    'subtotal' => $prep['subtotal'],
                    'cogs_total' => $prep['cogs_total'],
                    'settlement_status' => 'unsettled',
                ]);

                // Update saldo stok produk
                /** @var Product $prod */
                $prod = $prep['product'];
                $prod->stock = $prep['stock_after'];
                $prod->save();

                // Catat mutasi barang keluar (inventory_movements)
                InventoryMovement::create([
                    'movement_code' => InventoryMovement::generateMovementCode($saleDate),
                    'product_id' => $prod->id,
                    'movement_type' => 'sale',
                    'quantity' => $prep['quantity'],
                    'cost_price' => $prep['cost_price'],
                    'stock_before' => $prep['stock_before'],
                    'stock_after' => $prep['stock_after'],
                    'reference_number' => $transactionNumber,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'movement_date' => $saleDate,
                    'created_by' => $cashierId,
                    'description' => "Penjualan kasir POS: {$transactionNumber} (" . ($sale->customer_name ?: 'Pelanggan') . ")",
                ]);
            }

            // 4. Catat jurnal akuntansi penjualan otomatis (Kas vs Penjualan, HPP vs Persediaan)
            $this->accountingService->createJournalForSale($sale, $cashierId, true);

            try {
                app(ActivityLogService::class)->log(
                    action: 'create',
                    module: 'pos',
                    description: "Penjualan kasir POS #{$transactionNumber} senilai Rp " . number_format($sale->grand_total, 0, ',', '.'),
                    subjectType: Sale::class,
                    subjectId: (string) $sale->id
                );
            } catch (\Throwable $e) {}

            return $this->getSale($sale->id);
        });
    }

    /**
     * Dapatkan daftar transaksi penjualan dengan filter & pencarian.
     */
    public function listSales(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Sale::query()->with(['cashier', 'items.product.unit']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['cashier_id'])) {
            $query->where('cashier_id', $filters['cashier_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('sale_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('sale_date', '<=', $filters['end_date']);
        }

        return $query->orderByDesc('sale_date')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Dapatkan satu transaksi penjualan lengkap.
     */
    public function getSale(int $id): Sale
    {
        return Sale::with([
            'cashier',
            'canceller',
            'items.product.unit',
            'journalEntries.details.account',
        ])->findOrFail($id);
    }

    /**
     * Format data struk belanja untuk cetak printer kasir (58mm / 80mm).
     */
    public function getReceiptData(int $id): array
    {
        $sale = $this->getSale($id);
        $settings = app(SettingsService::class);

        return [
            'store_name' => $settings->get('store_name', config('app.name', 'FinToko')),
            'store_logo' => $settings->get('store_logo'),
            'store_address' => $settings->get('store_address', 'Jl. Pemuda No. 45, Semarang'),
            'store_phone' => $settings->get('store_phone', '0812-3456-7890'),
            'store_email' => $settings->get('store_email', 'info@fintoko.id'),
            'receipt_header' => $settings->get('receipt_header', ''),
            'printer_width' => $settings->get('pos_receipt_printer_width', '58mm'),
            'transaction_number' => $sale->transaction_number,
            'sale_date' => $sale->sale_date->format('d/m/Y'),
            'sale_time' => $sale->created_at->format('H:i:s'),
            'cashier_name' => $sale->cashier?->name ?? 'Kasir',
            'customer_name' => $sale->customer_name ?? 'Pelanggan',
            'items' => $sale->items->map(function ($item) {
                return [
                    'name' => $item->product?->name ?? 'Item',
                    'sku' => $item->product?->sku ?? '',
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->product?->unit?->symbol ?? 'pcs',
                    'unit_price' => (float) $item->unit_price,
                    'discount_amount' => (float) $item->discount_amount,
                    'subtotal' => (float) $item->subtotal,
                ];
            }),
            'subtotal' => (float) $sale->subtotal,
            'discount_amount' => (float) $sale->discount_amount,
            'tax_amount' => (float) $sale->tax_amount,
            'grand_total' => (float) $sale->grand_total,
            'paid_amount' => (float) $sale->paid_amount,
            'change_amount' => (float) $sale->change_amount,
            'payment_method' => strtoupper($sale->payment_method),
            'status' => $sale->status,
            'notes' => $sale->notes,
            'footer_message' => $settings->get('receipt_footer', 'Terima kasih telah berbelanja di FinToko!'),
        ];
    }

    /**
     * Batalkan transaksi penjualan (retur / void) dengan pengembalian stok dan pembalik jurnal.
     */
    public function cancelSale(Sale $sale, int $userId, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($sale, $userId, $reason) {
            if ($sale->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => "Transaksi {$sale->transaction_number} sudah dibatalkan sebelumnya.",
                ]);
            }

            $sale->loadMissing('items.product');

            foreach ($sale->items as $item) {
                /** @var Product $product */
                $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();

                $stockBefore = (float) $product->stock;
                $qty = (float) $item->quantity;
                $stockAfter = round($stockBefore + $qty, 2);

                $product->stock = $stockAfter;
                $product->save();

                // Catat mutasi pengembalian barang masuk
                InventoryMovement::create([
                    'movement_code' => InventoryMovement::generateMovementCode(now()->toDateString()),
                    'product_id' => $product->id,
                    'movement_type' => 'sale_return',
                    'quantity' => $qty,
                    'cost_price' => (float) $item->cost_price,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_number' => $sale->transaction_number,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'movement_date' => now()->toDateString(),
                    'created_by' => $userId,
                    'description' => "Pembatalan / retur penjualan {$sale->transaction_number}: " . ($reason ?: 'Batal transaksi'),
                ]);
            }

            // Balikkan jurnal akuntansi penjualan
            $this->accountingService->reverseJournalForSource('sale', $sale->id, $userId, $reason ?: 'Pembatalan transaksi penjualan kasir');

            $sale->status = 'cancelled';
            $sale->cancelled_at = now();
            $sale->cancelled_by = $userId;
            $sale->cancellation_reason = $reason ?: 'Pembatalan transaksi';
            $sale->save();

            try {
                app(ActivityLogService::class)->log(
                    action: 'cancel',
                    module: 'pos',
                    description: "Membatalkan penjualan kasir #{$sale->transaction_number}: " . ($reason ?: 'Retur / Pembatalan'),
                    subjectType: Sale::class,
                    subjectId: (string) $sale->id
                );
            } catch (\Throwable $e) {}

            return $this->getSale($sale->id);
        });
    }
}


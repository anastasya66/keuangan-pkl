<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierDebtPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Dapatkan daftar transaksi pembelian dengan filter & pencarian.
     */
    public function listPurchases(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Purchase::query()->with(['supplier', 'creator', 'receiver', 'items.product.unit']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('purchase_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('purchase_date', '<=', $filters['end_date']);
        }

        return $query->orderByDesc('purchase_date')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Dapatkan satu transaksi pembelian lengkap beserta detailnya.
     */
    public function getPurchase(int $id): Purchase
    {
        return Purchase::with([
            'supplier',
            'creator',
            'receiver',
            'items.product.unit',
            'debtPayments.creator',
            'journalEntries.details.account',
        ])->findOrFail($id);
    }

    /**
     * Buat transaksi pembelian baru (status: draft atau langsung received jika barang sudah ada).
     */
    public function createPurchase(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId) {
            $invoiceNumber = !empty($data['invoice_number'])
                ? $data['invoice_number']
                : Purchase::generateInvoiceNumber($data['purchase_date'] ?? null);

            $status = $data['status'] ?? 'draft';
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $discount = (float) ($data['discount_amount'] ?? 0);
            $tax = (float) ($data['tax_amount'] ?? 0);

            // Hitung subtotal dari items
            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw ValidationException::withMessages([
                    'items' => 'Pembelian wajib memiliki minimal 1 item barang.',
                ]);
            }

            $subtotal = 0.0;
            $preparedItems = [];
            foreach ($itemsData as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['purchase_price'];
                if ($qty <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' => "Kuantitas produk ID {$item['product_id']} harus lebih besar dari 0.",
                    ]);
                }
                if ($price < 0) {
                    throw ValidationException::withMessages([
                        'purchase_price' => "Harga beli produk ID {$item['product_id']} tidak boleh negatif.",
                    ]);
                }

                $itemSubtotal = round($qty * $price, 2);
                $subtotal += $itemSubtotal;

                $preparedItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'purchase_price' => $price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $grandTotal = max(0.00, round($subtotal - $discount + $tax, 2));

            // Tentukan status pembayaran awal
            $paidAmount = 0.00;
            if ($paymentMethod === 'cash' || $paymentMethod === 'transfer') {
                $paidAmount = $grandTotal;
                $paymentStatus = 'paid';
            } else {
                // tempo
                $paidAmount = (float) ($data['paid_amount'] ?? 0);
                if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                    $paymentStatus = 'paid';
                } elseif ($paidAmount > 0) {
                    $paymentStatus = 'partial';
                } else {
                    $paymentStatus = 'unpaid';
                }
            }

            $purchase = Purchase::create([
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'status' => 'draft', // Buat sebagai draft terlebih dahulu
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_date' => $paymentMethod === 'tempo' ? ($data['due_date'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($preparedItems as $item) {
                $purchase->items()->create($item);
            }

            // Jika status yang diminta adalah 'received', langsung proses penerimaan barang & stok
            if ($status === 'received') {
                $this->processGoodsReceipt($purchase, $userId);
            }

            try {
                app(\App\Services\ActivityLogService::class)->log(
                    action: 'create',
                    module: 'purchases',
                    description: "Membuat transaksi pembelian #{$purchase->invoice_number} senilai Rp " . number_format($purchase->grand_total, 0, ',', '.'),
                    subjectType: Purchase::class,
                    subjectId: (string) $purchase->id
                );
            } catch (\Throwable $e) {}

            return $this->getPurchase($purchase->id);
        });
    }

    /**
     * Konfirmasi penerimaan barang (status: received).
     * Memicu penambahan stok, kalkulasi moving weighted average cost, kartu stok, dan jurnal akuntansi.
     */
    public function receivePurchase(Purchase $purchase, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $userId) {
            if ($purchase->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => "Pembelian {$purchase->invoice_number} tidak dapat diterima karena berstatus {$purchase->status}.",
                ]);
            }

            $this->processGoodsReceipt($purchase, $userId);

            return $this->getPurchase($purchase->id);
        });
    }

    /**
     * Proses inti penerimaan barang fisik: update stok, HPP rata-rata, kartu stok, dan jurnal.
     */
    protected function processGoodsReceipt(Purchase $purchase, int $userId): void
    {
        $purchase->loadMissing(['items.product', 'supplier']);

        foreach ($purchase->items as $item) {
            /** @var Product $product */
            $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();

            $prevStock = (float) $product->stock;
            $qtyReceived = (float) $item->quantity;
            $unitBuyPrice = (float) $item->purchase_price;

            // Moving Weighted Average Formula:
            // (Nilai Persediaan Sebelum + Nilai Pembelian Baru) / (Stok Sebelum + Qty Pembelian Baru)
            $prevCost = (float) $product->average_cost > 0 ? (float) $product->average_cost : (float) $product->purchase_price;
            if ($prevStock > 0 && ($prevStock + $qtyReceived) > 0) {
                $prevTotalValuation = $prevStock * $prevCost;
                $newBuyValuation = $qtyReceived * $unitBuyPrice;
                $newAverageCost = round(($prevTotalValuation + $newBuyValuation) / ($prevStock + $qtyReceived), 2);
            } else {
                // Jika stok sebelumnya 0 atau negatif, harga pokok baru menggunakan harga beli baru
                $newAverageCost = $unitBuyPrice;
            }

            $stockAfter = round($prevStock + $qtyReceived, 2);

            // Perbarui data produk
            $product->stock = $stockAfter;
            $product->average_cost = $newAverageCost;
            $product->purchase_price = $unitBuyPrice; // Perbarui referensi harga beli terakhir
            $product->save();

            // Catat mutasi barang masuk pada kartu stok (inventory_movements)
            InventoryMovement::create([
                'movement_code' => InventoryMovement::generateMovementCode($purchase->purchase_date->format('Y-m-d')),
                'product_id' => $product->id,
                'movement_type' => 'purchase',
                'quantity' => $qtyReceived,
                'cost_price' => $unitBuyPrice,
                'stock_before' => $prevStock,
                'stock_after' => $stockAfter,
                'reference_number' => $purchase->invoice_number,
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'movement_date' => $purchase->purchase_date->format('Y-m-d'),
                'created_by' => $userId,
                'description' => "Pembelian barang masuk dari supplier " . ($purchase->supplier?->name ?? 'Supplier'),
            ]);
        }

        $purchase->status = 'received';
        $purchase->received_at = now();
        $purchase->received_by = $userId;
        $purchase->save();

        // Buat jurnal akuntansi otomatis
        $this->accountingService->createJournalForPurchase($purchase, $userId, true);
    }

    /**
     * Batalkan transaksi pembelian.
     * Jika sudah received, kembalikan stok secara valid (cegah stok negatif) dan batalkan jurnal.
     */
    public function cancelPurchase(Purchase $purchase, int $userId, ?string $reason = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $userId, $reason) {
            if ($purchase->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => "Pembelian {$purchase->invoice_number} sudah dalam status dibatalkan.",
                ]);
            }

            if ($purchase->status === 'received') {
                // Validasi bahwa stok saat ini mencukupi untuk dikurangi kembali
                foreach ($purchase->items as $item) {
                    /** @var Product $product */
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();
                    $qty = (float) $item->quantity;

                    if ((float) $product->stock < $qty) {
                        throw ValidationException::withMessages([
                            'stock' => "Pembelian tidak dapat dibatalkan karena stok produk '{$product->name}' tersisa {$product->stock}, kurang dari kuantitas yang harus dikembalikan ({$qty}).",
                        ]);
                    }

                    $stockBefore = (float) $product->stock;
                    $stockAfter = round($stockBefore - $qty, 2);

                    $product->stock = $stockAfter;
                    $product->save();

                    // Catat mutasi pembalik di kartu stok
                    InventoryMovement::create([
                        'movement_code' => InventoryMovement::generateMovementCode(now()->toDateString()),
                        'product_id' => $product->id,
                        'movement_type' => 'purchase_reversal',
                        'quantity' => $qty,
                        'cost_price' => (float) $item->purchase_price,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_number' => $purchase->invoice_number,
                        'reference_type' => 'purchase',
                        'reference_id' => $purchase->id,
                        'movement_date' => now()->toDateString(),
                        'created_by' => $userId,
                        'description' => "Pembatalan pembelian {$purchase->invoice_number}: " . ($reason ?: 'Pembatalan transaksi pembelian'),
                    ]);
                }

                // Reversal jurnal akuntansi
                $this->accountingService->reverseJournalForSource('purchase', $purchase->id, $userId, $reason ?: 'Pembatalan transaksi pembelian');
            }

            $purchase->status = 'cancelled';
            $purchase->notes = trim(($purchase->notes ? $purchase->notes . "\n" : '') . "Dibatalkan: " . ($reason ?: 'Pembatalan'));
            $purchase->save();

            return $this->getPurchase($purchase->id);
        });
    }

    /**
     * Bayar sisa utang pembelian tempo.
     */
    public function payDebt(Purchase $purchase, array $data, int $userId): SupplierDebtPayment
    {
        return DB::transaction(function () use ($purchase, $data, $userId) {
            $remaining = $purchase->remaining_debt;
            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'debt' => "Pembelian {$purchase->invoice_number} sudah lunas, tidak ada sisa utang.",
                ]);
            }

            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => "Nominal pembayaran utang harus lebih besar dari 0.",
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => "Nominal pembayaran (Rp " . number_format($amount, 2) . ") melebihi sisa utang (Rp " . number_format($remaining, 2) . ").",
                ]);
            }

            $paymentNumber = SupplierDebtPayment::generatePaymentNumber($data['payment_date'] ?? null);

            $payment = SupplierDebtPayment::create([
                'purchase_id' => $purchase->id,
                'payment_number' => $paymentNumber,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $newPaid = round((float) $purchase->paid_amount + $amount, 2);
            $purchase->paid_amount = $newPaid;
            $purchase->payment_status = $newPaid >= (float) $purchase->grand_total ? 'paid' : 'partial';
            $purchase->save();

            // Buat jurnal pelunasan utang
            $this->accountingService->createJournalForDebtPayment($payment, $userId, true);

            return $payment->load(['purchase.supplier', 'creator']);
        });
    }
}


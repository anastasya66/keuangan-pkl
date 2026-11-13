<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ConsignmentSettlement;
use App\Models\Consignor;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsignmentService
{
    public function __construct(
        protected ?AccountingService $accountingService = null
    ) {
        $this->accountingService = $accountingService ?? app(AccountingService::class);
    }

    /**
     * Dapatkan ringkasan statistik konsinyasi (4 kartu utama).
     */
    public function getSummary(array $filters = []): array
    {
        $salesQuery = SaleItem::query()
            ->where('is_consignment', true)
            ->whereHas('sale', function ($q) use ($filters) {
                if (!empty($filters['date_from'])) {
                    $q->whereDate('sale_date', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $q->whereDate('sale_date', '<=', $filters['date_to']);
                }
            });

        if (!empty($filters['consignor_id'])) {
            $salesQuery->where('consignor_id', $filters['consignor_id']);
        }

        // Metrik penjualan
        $totalSoldQty = (float) (clone $salesQuery)->sum('quantity');
        $totalSalesAmount = (float) (clone $salesQuery)->sum('subtotal');
        $totalStoreCommission = (float) (clone $salesQuery)->sum('commission_amount');

        // Tagihan ke konsinyor (unsettled vs settled)
        $unsettledPayable = (float) (clone $salesQuery)
            ->where('settlement_status', 'unsettled')
            ->sum('consignment_cost');

        $settledPaid = (float) (clone $salesQuery)
            ->where('settlement_status', 'settled')
            ->sum('consignment_cost');

        // Metrik produk konsinyasi
        $productsQuery = Product::query()->where('is_consignment', true);
        if (!empty($filters['consignor_id'])) {
            $productsQuery->where('consignor_id', $filters['consignor_id']);
        }

        $activeSkuCount = (clone $productsQuery)->where('is_active', true)->count();
        $totalRemainingStock = (float) (clone $productsQuery)->where('is_active', true)->sum('stock');
        $totalConsignors = Consignor::where('is_active', true)->count();

        return [
            'total_active_sku' => $activeSkuCount,
            'total_active_stock' => $totalRemainingStock,
            'total_sold_qty' => $totalSoldQty,
            'total_sales_amount' => $totalSalesAmount,
            'total_store_commission' => $totalStoreCommission,
            'unsettled_payable' => $unsettledPayable,
            'settled_paid' => $settledPaid,
            'total_consignors' => $totalConsignors,
        ];
    }

    /**
     * Dapatkan daftar penjualan konsinyasi (Tab 1: Penjualan & Bagi Hasil).
     */
    public function getSales(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SaleItem::query()
            ->with([
                'sale:id,transaction_number,sale_date,payment_method,status',
                'product:id,name,sku,barcode',
                'consignor:id,code,name,phone',
                'settlement:id,settlement_number,settlement_date'
            ])
            ->where('is_consignment', true);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                })->orWhereHas('sale', function ($sq) use ($search) {
                    $sq->where('transaction_number', 'like', "%{$search}%");
                })->orWhereHas('consignor', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($filters['consignor_id'])) {
            $query->where('consignor_id', $filters['consignor_id']);
        }

        if (!empty($filters['settlement_status']) && $filters['settlement_status'] !== 'all') {
            $query->where('settlement_status', $filters['settlement_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereHas('sale', function ($q) use ($filters) {
                $q->whereDate('sale_date', '>=', $filters['date_from']);
            });
        }

        if (!empty($filters['date_to'])) {
            $query->whereHas('sale', function ($q) use ($filters) {
                $q->whereDate('sale_date', '<=', $filters['date_to']);
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Dapatkan daftar stok produk konsinyasi (Tab 2: Stok Barang Konsinyasi).
     */
    public function getProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category:id,name', 'unit:id,name,symbol', 'consignor:id,code,name,phone'])
            ->where('is_consignment', true);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['consignor_id'])) {
            $query->where('consignor_id', $filters['consignor_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query->orderBy('name', 'asc')->paginate($perPage);

        // Tambahkan agregasi total terjual untuk tiap produk
        $productIds = $paginator->getCollection()->pluck('id');
        $soldAggregates = SaleItem::query()
            ->whereIn('product_id', $productIds)
            ->where('is_consignment', true)
            ->selectRaw('product_id, SUM(quantity) as total_sold, SUM(subtotal) as total_sales, SUM(commission_amount) as total_commission')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $paginator->getCollection()->transform(function ($product) use ($soldAggregates) {
            $sold = $soldAggregates->get($product->id);
            $product->total_sold_qty = $sold ? (float) $sold->total_sold : 0.0;
            $product->total_sales_amount = $sold ? (float) $sold->total_sales : 0.0;
            $product->total_commission_amount = $sold ? (float) $sold->total_commission : 0.0;
            return $product;
        });

        return $paginator;
    }

    /**
     * Dapatkan daftar mitra konsinyor beserta rekap tagihan (Tab 3: Data Konsinyor).
     */
    public function getConsignors(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Consignor::query()->withCount('products');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query->orderBy('name', 'asc')->paginate($perPage);

        $consignorIds = $paginator->getCollection()->pluck('id');

        // Agregat penjualan & tagihan per consignor
        $saleAggregates = SaleItem::query()
            ->whereIn('consignor_id', $consignorIds)
            ->where('is_consignment', true)
            ->selectRaw("
                consignor_id,
                SUM(quantity) as total_qty_sold,
                SUM(subtotal) as total_sales_amount,
                SUM(commission_amount) as total_commission,
                SUM(CASE WHEN settlement_status = 'unsettled' THEN consignment_cost ELSE 0 END) as unsettled_amount,
                SUM(CASE WHEN settlement_status = 'settled' THEN consignment_cost ELSE 0 END) as settled_amount,
                COUNT(CASE WHEN settlement_status = 'unsettled' THEN 1 END) as unsettled_items_count
            ")
            ->groupBy('consignor_id')
            ->get()
            ->keyBy('consignor_id');

        // Agregat produk & stok per consignor
        $productAggregates = Product::query()
            ->whereIn('consignor_id', $consignorIds)
            ->where('is_consignment', true)
            ->selectRaw("
                consignor_id,
                COUNT(id) as total_products,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
                SUM(stock) as total_stock_units
            ")
            ->groupBy('consignor_id')
            ->get()
            ->keyBy('consignor_id');

        $paginator->getCollection()->transform(function ($consignor) use ($saleAggregates, $productAggregates) {
            $agg = $saleAggregates->get($consignor->id);
            $consignor->total_qty_sold = $agg ? (float) $agg->total_qty_sold : 0.0;
            $consignor->total_sales_amount = $agg ? (float) $agg->total_sales_amount : 0.0;
            $consignor->total_commission = $agg ? (float) $agg->total_commission : 0.0;
            $consignor->unsettled_amount = $agg ? (float) $agg->unsettled_amount : 0.0;
            $consignor->settled_amount = $agg ? (float) $agg->settled_amount : 0.0;
            $consignor->unsettled_items_count = $agg ? (int) $agg->unsettled_items_count : 0;

            $pAgg = $productAggregates->get($consignor->id);
            $consignor->products_count = $pAgg ? (int) $pAgg->total_products : (int) $consignor->products_count;
            $consignor->active_products_count = $pAgg ? (int) $pAgg->active_products : 0;
            $consignor->total_stock_units = $pAgg ? (float) $pAgg->total_stock_units : 0.0;

            return $consignor;
        });

        return $paginator;
    }

    /**
     * Dapatkan daftar ringkas semua konsinyor aktif untuk opsi dropdown.
     */
    public function getActiveConsignors(): Collection
    {
        return Consignor::query()
            ->where('is_active', true)
            ->select('id', 'code', 'name', 'phone', 'default_commission_rate')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Buat data mitra konsinyor baru.
     */
    public function createConsignor(array $data): Consignor
    {
        if (empty($data['code'])) {
            $data['code'] = Consignor::generateCode();
        }

        return Consignor::create($data);
    }

    /**
     * Perbarui data mitra konsinyor.
     */
    public function updateConsignor(Consignor $consignor, array $data): Consignor
    {
        $consignor->update($data);
        return $consignor;
    }

    /**
     * Hapus data mitra konsinyor.
     */
    public function deleteConsignor(Consignor $consignor): bool
    {
        // Cek apakah ada penjualan yang belum disetor
        $hasUnsettled = SaleItem::where('consignor_id', $consignor->id)
            ->where('is_consignment', true)
            ->where('settlement_status', 'unsettled')
            ->exists();

        if ($hasUnsettled) {
            throw ValidationException::withMessages([
                'consignor' => 'Mitra konsinyor tidak dapat dihapus karena masih memiliki tagihan penjualan konsinyasi yang belum disetor.',
            ]);
        }

        return $consignor->delete();
    }

    /**
     * Generate format SKU produk konsinyasi otomatis jika dikosongkan (KNS-XXXX).
     */
    public static function generateConsignmentSku(): string
    {
        $prefix = 'KNS-';
        $last = Product::withTrashed()
            ->where('sku', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($last && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $last->sku, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextNumber);
    }

    /**
     * Buat produk konsinyasi baru dan sinkronisasikan otomatis ke Katalog & POS.
     */
    public function createConsignmentProduct(array $data, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($data, $userId) {
            $sellingPrice = (float) $data['selling_price'];
            $consignmentCost = (float) ($data['consignment_cost_price'] ?? 0);
            $commissionAmount = isset($data['commission_amount']) ? (float) $data['commission_amount'] : max(0.0, $sellingPrice - $consignmentCost);
            $commissionRate = isset($data['commission_rate']) ? (float) $data['commission_rate'] : ($sellingPrice > 0 ? round(($commissionAmount / $sellingPrice) * 100, 2) : 0);

            $initialStock = (float) ($data['stock'] ?? 0);
            $sku = !empty($data['sku']) ? trim($data['sku']) : self::generateConsignmentSku();

            $categoryId = !empty($data['category_id']) ? $data['category_id'] : (Category::where('is_active', true)->value('id') ?? Category::value('id'));
            $unitId = !empty($data['unit_id']) ? $data['unit_id'] : (Unit::where('is_active', true)->value('id') ?? Unit::value('id'));

            $product = Product::create([
                'category_id' => $categoryId,
                'unit_id' => $unitId,
                'sku' => $sku,
                'barcode' => !empty($data['barcode']) ? trim($data['barcode']) : null,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'purchase_price' => 0.00, // HPP internal toko bernilai 0 untuk konsinyasi
                'cost_price' => 0.00,
                'average_cost' => 0.00,
                'selling_price' => $sellingPrice,
                'stock' => $initialStock,
                'min_stock' => $data['minimum_stock'] ?? 5,
                'minimum_stock' => $data['minimum_stock'] ?? 5,
                'is_active' => $data['is_active'] ?? true,
                'is_consignment' => true,
                'consignor_id' => $data['consignor_id'],
                'consignment_cost_price' => $consignmentCost,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'initial_consignment_stock' => $initialStock,
            ]);

            // Catat mutasi awal jika ada stok masuk
            if ($initialStock > 0) {
                InventoryMovement::create([
                    'movement_code' => InventoryMovement::generateMovementCode(now()->toDateString()),
                    'product_id' => $product->id,
                    'movement_type' => 'stock_in',
                    'quantity' => $initialStock,
                    'cost_price' => 0.00,
                    'stock_before' => 0,
                    'stock_after' => $initialStock,
                    'reference_number' => $sku,
                    'reference_type' => 'consignment_initial',
                    'reference_id' => $product->id,
                    'movement_date' => now()->toDateString(),
                    'notes' => 'Penerimaan stok awal barang titipan konsinyasi dari mitra',
                    'created_by' => $userId,
                ]);
            }

            return $product->load(['category', 'unit', 'consignor']);
        });
    }

    /**
     * Perbarui produk konsinyasi dan sinkronisasikan otomatis ke Katalog & POS.
     */
    public function updateConsignmentProduct(Product $product, array $data): Product
    {
        $sellingPrice = isset($data['selling_price']) ? (float) $data['selling_price'] : (float) $product->selling_price;
        $consignmentCost = isset($data['consignment_cost_price']) ? (float) $data['consignment_cost_price'] : (float) $product->consignment_cost_price;
        $commissionAmount = isset($data['commission_amount']) ? (float) $data['commission_amount'] : max(0.0, $sellingPrice - $consignmentCost);
        $commissionRate = isset($data['commission_rate']) ? (float) $data['commission_rate'] : ($sellingPrice > 0 ? round(($commissionAmount / $sellingPrice) * 100, 2) : 0);

        $updateData = [
            'name' => isset($data['name']) ? trim($data['name']) : $product->name,
            'selling_price' => $sellingPrice,
            'consignment_cost_price' => $consignmentCost,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
        ];

        if (array_key_exists('sku', $data) && !empty($data['sku'])) {
            $updateData['sku'] = trim($data['sku']);
        }
        if (array_key_exists('consignor_id', $data) && !empty($data['consignor_id'])) {
            $updateData['consignor_id'] = $data['consignor_id'];
        }
        if (array_key_exists('category_id', $data) && !empty($data['category_id'])) {
            $updateData['category_id'] = $data['category_id'];
        }
        if (array_key_exists('unit_id', $data) && !empty($data['unit_id'])) {
            $updateData['unit_id'] = $data['unit_id'];
        }
        if (array_key_exists('barcode', $data)) {
            $updateData['barcode'] = !empty($data['barcode']) ? trim($data['barcode']) : null;
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (array_key_exists('minimum_stock', $data)) {
            $updateData['minimum_stock'] = (float) $data['minimum_stock'];
            $updateData['min_stock'] = (float) $data['minimum_stock'];
        }
        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = (bool) $data['is_active'];
        }

        $product->update($updateData);

        return $product->load(['category', 'unit', 'consignor']);
    }

    /**
     * Penyesuaian stok konsinyasi (Tambah stok titipan atau Retur sisa barang ke mitra).
     */
    public function adjustStock(Product $product, array $data, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($product, $data, $userId) {
            $type = $data['type']; // 'in' (tambah stok titipan) atau 'return' (retur ke mitra)
            $qty = abs((float) $data['quantity']);
            $currentStock = (float) $product->stock;

            if ($qty <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah penyesuaian stok harus lebih dari 0.',
                ]);
            }

            $userNotes = !empty($data['notes']) ? trim($data['notes']) : null;
            if ($type === 'return') {
                if ($currentStock < $qty) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stok saat ini ({$currentStock}) tidak mencukupi untuk melakukan retur sebanyak {$qty}.",
                    ]);
                }
                $newStock = $currentStock - $qty;
                $movementType = 'stock_out';
                $defaultReason = 'Retur Barang Konsinyasi ke Mitra';
                $description = 'Retur pengembalian barang konsinyasi ke mitra' . ($userNotes ? ": {$userNotes}" : '');
            } else {
                $newStock = $currentStock + $qty;
                $movementType = 'stock_in';
                $defaultReason = 'Tambah Titipan Konsinyasi';
                $description = 'Penambahan stok titipan barang konsinyasi dari mitra' . ($userNotes ? ": {$userNotes}" : '');
            }

            $product->stock = $newStock;
            $product->save();

            $sku = $product->sku ?: ('KNS-' . str_pad((string) $product->id, 4, '0', STR_PAD_LEFT));

            InventoryMovement::create([
                'movement_code' => InventoryMovement::generateMovementCode(now()->toDateString()),
                'product_id' => $product->id,
                'movement_type' => $movementType,
                'quantity' => $qty,
                'cost_price' => (float) ($product->consignment_cost_price ?? 0),
                'stock_before' => $currentStock,
                'stock_after' => $newStock,
                'adjustment_reason' => $userNotes ?: $defaultReason,
                'description' => $description,
                'reference_number' => $sku,
                'reference_type' => $type === 'return' ? 'consignment_return' : 'consignment_stock_in',
                'reference_id' => $product->id,
                'movement_date' => now()->toDateString(),
                'created_by' => $userId,
            ]);

            try {
                app(\App\Services\ActivityLogService::class)->log(
                    action: 'adjust_stock',
                    module: 'consignments',
                    description: "Penyesuaian stok konsinyasi '{$product->name}' ({$movementType} {$qty}) menjadi {$newStock}",
                    subjectType: Product::class,
                    subjectId: (string) $product->id
                );
            } catch (\Throwable $e) {}

            return $product->fresh(['category', 'unit', 'consignor']);
        });
    }

    /**
     * Proses Penyelesaian / Penyetoran Tagihan ke Mitra Konsinyor (Settlement).
     */
    public function createSettlement(array $data, ?int $userId = null): ConsignmentSettlement
    {
        return DB::transaction(function () use ($data, $userId) {
            $consignorId = $data['consignor_id'];
            $consignor = Consignor::findOrFail($consignorId);

            // Dapatkan item penjualan unsettled
            $query = SaleItem::query()
                ->where('consignor_id', $consignorId)
                ->where('is_consignment', true)
                ->where('settlement_status', 'unsettled');

            if (!empty($data['sale_item_ids']) && is_array($data['sale_item_ids'])) {
                $query->whereIn('id', $data['sale_item_ids']);
            }

            $unsettledItems = $query->lockForUpdate()->get();

            if ($unsettledItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'settlement' => 'Tidak ada transaksi penjualan konsinyasi yang belum disetor untuk mitra ini.',
                ]);
            }

            $totalSoldQty = (float) $unsettledItems->sum('quantity');
            $totalSalesAmount = (float) $unsettledItems->sum('subtotal');
            $totalConsignorAmount = (float) $unsettledItems->sum('consignment_cost');
            $totalStoreCommission = (float) $unsettledItems->sum('commission_amount');

            $settlementNumber = ConsignmentSettlement::generateNumber();

            $settlement = ConsignmentSettlement::create([
                'settlement_number' => $settlementNumber,
                'consignor_id' => $consignorId,
                'settlement_date' => $data['settlement_date'] ?? now()->toDateString(),
                'total_sold_qty' => $totalSoldQty,
                'total_sales_amount' => $totalSalesAmount,
                'total_consignor_amount' => $totalConsignorAmount,
                'total_store_commission' => $totalStoreCommission,
                'payment_method' => $data['payment_method'] ?? 'transfer',
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Update status item penjualan konsinyasi
            $now = now();
            SaleItem::whereIn('id', $unsettledItems->pluck('id'))->update([
                'settlement_status' => 'settled',
                'settlement_id' => $settlement->id,
                'settled_at' => $now,
            ]);

            // Catat jurnal akuntansi pelunasan hutang bagi hasil mitra
            try {
                $this->accountingService->createJournalForConsignmentSettlement($settlement, $userId ?? 1, true);
            } catch (\Throwable $e) {}

            return $settlement->load(['consignor', 'creator']);
        });
    }
}


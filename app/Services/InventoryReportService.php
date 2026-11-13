<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Carbon\Carbon;

class InventoryReportService
{
    /**
     * Laporan Stok Produk Aktual & Valuasi Persediaan.
     *
     * @param array $filters
     * @return array
     */
    public function getInventoryReport(array $filters = []): array
    {
        $query = Product::with(['category', 'unit', 'consignor'])->where('is_active', true);

        // Filter kategori
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Pencarian nama atau SKU/barcode
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $allProducts = $query->orderBy('name')->get();

        // Ambil data mutasi masuk dan keluar per produk
        $productIds = $allProducts->pluck('id')->all();

        $inTypes = ['stock_in', 'purchase', 'adjustment_in', 'initial_stock', 'sale_return'];
        $outTypes = ['stock_out', 'sale', 'adjustment_out', 'purchase_reversal', 'purchase_return'];

        $inMovements = InventoryMovement::whereIn('product_id', $productIds)
            ->whereIn('movement_type', $inTypes)
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        $outMovements = InventoryMovement::whereIn('product_id', $productIds)
            ->whereIn('movement_type', $outTypes)
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        // Filter stock_status di collection
        if (!empty($filters['stock_status'])) {
            $status = $filters['stock_status'];
            $allProducts = $allProducts->filter(function ($p) use ($status) {
                $stock = (float) $p->stock;
                if ($status === 'out_of_stock') {
                    return $stock <= 0;
                }
                if ($status === 'low_stock') {
                    return $stock > 0 && $stock <= 10;
                }
                if ($status === 'available') {
                    return $stock > 10;
                }
                return true;
            })->values();
        }

        $items = [];
        $totalUnits = 0.0;
        $totalValue = 0.0;
        $availableCount = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $totalQtyIn = 0.0;
        $totalQtyOut = 0.0;

        foreach ($allProducts as $p) {
            $stock = (float) $p->stock;
            $purchasePrice = (float) $p->purchase_price;
            $sellingPrice = (float) $p->selling_price;
            $inventoryValue = round($stock * $purchasePrice, 2);

            $qtyIn = (float) ($inMovements[$p->id] ?? 0);
            $qtyOut = (float) ($outMovements[$p->id] ?? 0);

            // Bila mutasi tercatat belum mencakup stok awal, selaraskan mutasi masuk agar sisa stok = in - out
            if (($qtyIn - $qtyOut) < $stock) {
                $qtyIn += ($stock - ($qtyIn - $qtyOut));
            }

            if ($stock <= 0) {
                $status = 'out_of_stock';
                $statusLabel = 'Stok Habis';
                $outOfStockCount++;
            } elseif ($stock <= 10) {
                $status = 'low_stock';
                $statusLabel = 'Stok Menipis';
                $lowStockCount++;
            } else {
                $status = 'available';
                $statusLabel = 'Tersedia';
                $availableCount++;
            }

            $totalUnits += $stock;
            $totalValue += $inventoryValue;
            $totalQtyIn += $qtyIn;
            $totalQtyOut += $qtyOut;

            $items[] = [
                'id' => $p->id,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'name' => $p->name,
                'category' => $p->category?->name ?? '-',
                'category_name' => $p->category?->name ?? '-',
                'unit' => $p->unit?->symbol ?? 'unit',
                'unit_symbol' => $p->unit?->symbol ?? 'unit',
                'stock' => $stock,
                'total_in' => $qtyIn,
                'total_out' => $qtyOut,
                'purchase_price' => $purchasePrice,
                'average_cost' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'inventory_value' => $inventoryValue,
                'valuation' => $inventoryValue,
                'status' => $status,
                'stock_status' => $status,
                'status_label' => $statusLabel,
                'stock_status_label' => $statusLabel,
                'is_consignment' => (bool) $p->is_consignment,
                'consignor_name' => $p->consignor?->name ?? null,
            ];
        }

        return [
            'summary' => [
                'total_products' => count($items),
                'total_units' => round($totalUnits, 2),
                'total_stock_units' => round($totalUnits, 2),
                'total_valuation' => round($totalValue, 2),
                'total_inventory_value' => round($totalValue, 2),
                'total_in' => round($totalQtyIn, 2),
                'total_out' => round($totalQtyOut, 2),
                'available_count' => $availableCount,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
            ],
            'products' => $items,
            'items' => $items,
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Laporan Riwayat Mutasi Persediaan.
     *
     * @param array $filters
     * @return array
     */
    public function getStockMovementsReport(array $filters = []): array
    {
        $query = InventoryMovement::with(['product.unit', 'product.category', 'creator']);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['movement_type']) && $filters['movement_type'] !== 'all') {
            $mType = $filters['movement_type'];
            if ($mType === 'in' || $mType === 'stock_in') {
                $query->whereIn('movement_type', ['stock_in', 'purchase', 'adjustment_in', 'initial_stock', 'sale_return']);
            } elseif ($mType === 'out' || $mType === 'stock_out') {
                $query->whereIn('movement_type', ['stock_out', 'sale', 'adjustment_out', 'purchase_reversal', 'purchase_return']);
            } elseif ($mType === 'adjustment') {
                $query->whereIn('movement_type', ['adjustment_in', 'adjustment_out']);
            } else {
                $query->where('movement_type', $mType);
            }
        }

        if (!empty($filters['start_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('movement_date', '>=', $filters['start_date'])
                  ->orWhere(function ($sub) use ($filters) {
                      $sub->whereNull('movement_date')->whereDate('created_at', '>=', $filters['start_date']);
                  });
            });
        }

        if (!empty($filters['end_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('movement_date', '<=', $filters['end_date'])
                  ->orWhere(function ($sub) use ($filters) {
                      $sub->whereNull('movement_date')->whereDate('created_at', '<=', $filters['end_date']);
                  });
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('movement_code', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        $movements = $query->orderByDesc('movement_date')->orderByDesc('created_at')->orderByDesc('id')->get();

        $typeLabels = [
            'purchase' => 'Masuk (Pembelian)',
            'stock_in' => 'Stok Masuk',
            'sale' => 'Keluar (Penjualan)',
            'stock_out' => 'Stok Keluar',
            'adjustment_in' => 'Opname Bertambah',
            'adjustment_out' => 'Opname Berkurang',
            'initial_stock' => 'Stok Awal',
            'sale_return' => 'Retur Penjualan',
            'purchase_return' => 'Retur Pembelian',
            'purchase_reversal' => 'Batal Pembelian',
        ];

        $inTypes = ['stock_in', 'purchase', 'adjustment_in', 'initial_stock', 'sale_return'];
        $outTypes = ['stock_out', 'sale', 'adjustment_out', 'purchase_reversal', 'purchase_return'];

        $totalIn = 0.0;
        $totalOut = 0.0;
        $totalAdj = 0.0;
        $items = [];

        foreach ($movements as $m) {
            $qty = (float) $m->quantity;
            $type = $m->movement_type;

            if (in_array($type, ['stock_in', 'purchase', 'initial_stock', 'sale_return'])) {
                $totalIn += $qty;
                $category = 'in';
            } elseif (in_array($type, ['stock_out', 'sale', 'purchase_reversal', 'purchase_return'])) {
                $totalOut += $qty;
                $category = 'out';
            } else {
                $totalAdj += $qty;
                $category = 'adj';
            }

            // Kode mutasi & nomor referensi fallback
            $rawCode = $m->movement_code ?: ($m->reference_number ?: ('MUT-' . str_pad($m->id, 5, '0', STR_PAD_LEFT)));
            $refNumber = $m->reference_number ?: $rawCode;

            // Format tanggal dan jam
            $dateObj = $m->created_at ?: ($m->movement_date ? Carbon::parse($m->movement_date) : null);
            $formattedDate = $dateObj ? $dateObj->format('d M Y, H:i') : ($m->movement_date ? Carbon::parse($m->movement_date)->format('d M Y') : '-');
            $dateOnly = $m->movement_date ? Carbon::parse($m->movement_date)->format('Y-m-d') : ($dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d'));

            // Fallback keterangan / alasan
            $desc = $m->description ?: $m->adjustment_reason;
            if (empty($desc)) {
                if ($type === 'sale') {
                    $desc = 'Penjualan Kasir POS' . ($m->reference_number ? " ({$m->reference_number})" : '');
                } elseif ($type === 'purchase') {
                    $desc = 'Pembelian Stok' . ($m->reference_number ? " ({$m->reference_number})" : '');
                } elseif ($type === 'stock_in') {
                    $desc = 'Stok Masuk Penerimaan Barang';
                } elseif ($type === 'stock_out') {
                    $desc = 'Stok Keluar Operasional';
                } elseif ($type === 'adjustment_in') {
                    $desc = 'Penyesuaian Opname Bertambah';
                } elseif ($type === 'adjustment_out') {
                    $desc = 'Penyesuaian Opname Berkurang';
                } else {
                    $desc = 'Penyesuaian Stok Toko';
                }
            }

            // Fallback nama petugas
            $creatorName = $m->creator?->name ?? 'Administrator';
            $typeLabel = $typeLabels[$type] ?? (in_array($type, $inTypes) ? 'Barang Masuk' : (in_array($type, $outTypes) ? 'Barang Keluar' : 'Penyesuaian'));

            $items[] = [
                'id' => $m->id,
                // Kode mutasi & referensi
                'movement_code' => $rawCode,
                'code' => $rawCode,
                'reference_number' => $m->reference_number,
                'ref' => $m->reference_number,

                // Tanggal & waktu
                'movement_date' => $dateOnly,
                'date' => $formattedDate,
                'date_formatted' => $formattedDate,
                'created_at' => $m->created_at?->toISOString(),

                // Tipe mutasi
                'movement_type' => $type,
                'type' => $type,
                'type_category' => $category, // 'in', 'out', 'adj'
                'movement_type_label' => $typeLabel,
                'type_label' => $typeLabel,

                // Detail produk
                'product_id' => $m->product_id,
                'product_name' => $m->product?->name ?? 'Produk Dihapus',
                'product_sku' => $m->product?->sku ?? '-',
                'sku' => $m->product?->sku ?? '-',
                'unit_symbol' => $m->product?->unit?->symbol ?? 'unit',
                'unit' => $m->product?->unit?->symbol ?? 'unit',
                'category_name' => $m->product?->category?->name ?? '-',

                // Jumlah & sisa stok
                'quantity' => $qty,
                'stock_before' => (float) $m->stock_before,
                'stock_after' => (float) $m->stock_after,
                'cost_price' => (float) ($m->cost_price ?? $m->product?->purchase_price ?? 0),

                // Keterangan & Petugas
                'description' => $desc,
                'notes' => $desc,
                'creator_name' => $creatorName,
                'creator' => $creatorName,
            ];
        }

        return [
            'summary' => [
                'total_movements' => count($items),
                'total_stock_in' => round($totalIn, 2),
                'total_stock_out' => round($totalOut, 2),
                'total_adjustments' => round($totalAdj, 2),
            ],
            'movements' => $items,
            'items' => $items,
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}


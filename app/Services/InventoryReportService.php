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
        $query = Product::with(['category', 'unit'])->where('is_active', true);

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

        foreach ($allProducts as $p) {
            $stock = (float) $p->stock;
            $purchasePrice = (float) $p->purchase_price;
            $sellingPrice = (float) $p->selling_price;
            $inventoryValue = round($stock * $purchasePrice, 2);

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

            $items[] = [
                'id' => $p->id,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'name' => $p->name,
                'category_name' => $p->category?->name ?? '-',
                'unit_symbol' => $p->unit?->symbol ?? 'unit',
                'stock' => $stock,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'inventory_value' => $inventoryValue,
                'stock_status' => $status,
                'stock_status_label' => $statusLabel,
            ];
        }

        return [
            'summary' => [
                'total_products' => count($items),
                'total_stock_units' => round($totalUnits, 2),
                'total_inventory_value' => round($totalValue, 2),
                'available_count' => $availableCount,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
            ],
            'products' => $items,
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
        $query = InventoryMovement::with(['product.unit', 'creator']);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['movement_type']) && $filters['movement_type'] !== 'all') {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('movement_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('movement_date', '<=', $filters['end_date']);
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

        $movements = $query->orderByDesc('movement_date')->orderByDesc('id')->get();

        $typeLabels = [
            'stock_in' => 'Stok Masuk',
            'stock_out' => 'Stok Keluar',
            'adjustment_in' => 'Opname Bertambah',
            'adjustment_out' => 'Opname Berkurang',
        ];

        $totalIn = 0.0;
        $totalOut = 0.0;
        $totalAdj = 0.0;
        $items = [];

        foreach ($movements as $m) {
            $qty = (float) $m->quantity;
            if ($m->movement_type === 'stock_in') {
                $totalIn += $qty;
            } elseif ($m->movement_type === 'stock_out') {
                $totalOut += $qty;
            } else {
                $totalAdj += $qty;
            }

            $items[] = [
                'id' => $m->id,
                'movement_code' => $m->movement_code,
                'movement_date' => $m->movement_date->format('Y-m-d'),
                'movement_type' => $m->movement_type,
                'movement_type_label' => $typeLabels[$m->movement_type] ?? ucfirst($m->movement_type),
                'product_name' => $m->product?->name ?? '-',
                'product_sku' => $m->product?->sku ?? '-',
                'unit_symbol' => $m->product?->unit?->symbol ?? 'unit',
                'quantity' => $qty,
                'stock_before' => (float) $m->stock_before,
                'stock_after' => (float) $m->stock_after,
                'reference_number' => $m->reference_number,
                'description' => $m->description ?: $m->adjustment_reason,
                'creator_name' => $m->creator?->name ?? 'Sistem',
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
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}


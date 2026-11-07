<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryValuationService
{
    /**
     * Dapatkan ringkasan valuasi persediaan Moving Weighted Average dan daftar produk.
     */
    public function getValuationOverview(array $filters = [], ?int $perPage = 15): array
    {
        // 1. Ambil seluruh produk aktif untuk kalkulasi ringkasan agregat
        $allProducts = Product::with(['category', 'unit'])->where('is_active', true)->get();

        $totalValuation = 0.0;
        $totalStockUnits = 0.0;
        $totalProducts = $allProducts->count();
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $potentialRevenue = 0.0;

        foreach ($allProducts as $p) {
            $stock = max(0.0, (float) $p->stock);
            $cost = (float) $p->effective_cost;
            $sell = (float) $p->selling_price;

            $val = round($stock * $cost, 2);
            $rev = round($stock * $sell, 2);

            $totalValuation += $val;
            $totalStockUnits += $stock;
            $potentialRevenue += $rev;

            if ($stock <= 0) {
                $outOfStockCount++;
            } elseif ($stock <= (float) $p->min_stock) {
                $lowStockCount++;
            }
        }

        $potentialProfit = round($potentialRevenue - $totalValuation, 2);

        // 2. Rincian per kategori
        $categoryBreakdown = [];
        $categories = Category::with(['products' => function ($q) {
            $q->where('is_active', true);
        }])->where('is_active', true)->get();

        foreach ($categories as $cat) {
            $catStock = 0.0;
            $catValuation = 0.0;
            foreach ($cat->products as $p) {
                $st = max(0.0, (float) $p->stock);
                $catStock += $st;
                $catValuation += round($st * (float) $p->effective_cost, 2);
            }

            if ($cat->products->count() > 0) {
                $categoryBreakdown[] = [
                    'category_id' => $cat->id,
                    'category_name' => $cat->name,
                    'product_count' => $cat->products->count(),
                    'total_stock' => round($catStock, 2),
                    'total_valuation' => round($catValuation, 2),
                    'percentage' => $totalValuation > 0 ? round(($catValuation / $totalValuation) * 100, 1) : 0,
                ];
            }
        }

        // 3. Query produk dengan filter & pagination
        $query = Product::query()->with(['category', 'unit'])->where('is_active', true);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'out_of_stock') {
                $query->where('stock', '<=', 0);
            } elseif ($filters['stock_status'] === 'low_stock') {
                $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
            } elseif ($filters['stock_status'] === 'available') {
                $query->whereColumn('stock', '>', 'min_stock');
            }
        }

        $orderColumn = $filters['sort_by'] ?? 'name';
        $orderDir = $filters['sort_dir'] ?? 'asc';
        if (in_array($orderColumn, ['name', 'stock', 'average_cost', 'purchase_price', 'selling_price'])) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('name', 'asc');
        }

        $paginated = $perPage ? $query->paginate($perPage) : $query->get();

        return [
            'summary' => [
                'total_valuation' => round($totalValuation, 2),
                'total_stock_units' => round($totalStockUnits, 2),
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'potential_revenue' => round($potentialRevenue, 2),
                'potential_profit' => round($potentialProfit, 2),
                'valuation_method' => 'Moving Weighted Average',
            ],
            'category_breakdown' => $categoryBreakdown,
            'products' => $paginated,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Dapatkan seluruh data valuasi tanpa pagination untuk diexport.
     */
    public function getFullExportData(array $filters = []): array
    {
        return $this->getValuationOverview($filters, null);
    }
}

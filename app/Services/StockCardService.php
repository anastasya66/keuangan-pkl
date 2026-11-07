<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StockCardService
{
    /**
     * Dapatkan data kartu stok terperinci untuk suatu produk dalam rentang tanggal.
     */
    public function getStockCard(int $productId, ?string $startDate = null, ?string $endDate = null): array
    {
        $product = Product::with(['category', 'unit'])->findOrFail($productId);

        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::parse('2020-01-01')->startOfDay();

        // 1. Hitung Saldo Awal sebelum tanggal mulai
        $priorMovements = InventoryMovement::where('product_id', $productId)
            ->where('movement_date', '<', $start->toDateString())
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        $openingBalance = 0.0;
        foreach ($priorMovements as $mov) {
            $type = $mov->movement_type;
            $qty = (float) $mov->quantity;

            if (in_array($type, ['stock_in', 'purchase', 'adjustment_in', 'initial_stock', 'sale_return'])) {
                $openingBalance += $qty;
            } else {
                $openingBalance -= $qty;
            }
        }

        // 2. Ambil seluruh pergerakan mutasi dalam periode yang dipilih
        $periodMovements = InventoryMovement::with(['creator'])
            ->where('product_id', $productId)
            ->whereDate('movement_date', '>=', $start->toDateString())
            ->whereDate('movement_date', '<=', $end->toDateString())
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        $runningBalance = $openingBalance;
        $totalIn = 0.0;
        $totalOut = 0.0;

        $rows = [];
        foreach ($periodMovements as $mov) {
            $type = $mov->movement_type;
            $qty = (float) $mov->quantity;
            $isIn = in_array($type, ['stock_in', 'purchase', 'adjustment_in', 'initial_stock', 'sale_return']);

            $qtyIn = $isIn ? $qty : 0.0;
            $qtyOut = $isIn ? 0.0 : $qty;

            if ($isIn) {
                $runningBalance += $qty;
                $totalIn += $qty;
            } else {
                $runningBalance -= $qty;
                $totalOut += $qty;
            }

            $rows[] = [
                'id' => $mov->id,
                'movement_code' => $mov->movement_code,
                'movement_date' => $mov->movement_date->format('Y-m-d'),
                'movement_type' => $type,
                'movement_type_label' => $this->getMovementTypeLabel($type),
                'reference_number' => $mov->reference_number,
                'reference_type' => $mov->reference_type,
                'reference_id' => $mov->reference_id,
                'description' => $mov->description ?: $mov->adjustment_reason,
                'quantity_in' => $qtyIn,
                'quantity_out' => $qtyOut,
                'balance' => round($runningBalance, 2),
                'cost_price' => (float) $mov->cost_price,
                'user_name' => $mov->creator?->name ?? 'Sistem',
            ];
        }

        return [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'category_name' => $product->category?->name ?? '-',
                'unit_symbol' => $product->unit?->symbol ?? 'pcs',
                'current_stock' => (float) $product->stock,
                'average_cost' => (float) $product->average_cost,
                'purchase_price' => (float) $product->purchase_price,
                'selling_price' => (float) $product->selling_price,
            ],
            'period' => [
                'start_date' => $startDate ?: $start->toDateString(),
                'end_date' => $endDate ?: $end->toDateString(),
            ],
            'summary' => [
                'opening_balance' => round($openingBalance, 2),
                'total_in' => round($totalIn, 2),
                'total_out' => round($totalOut, 2),
                'closing_balance' => round($runningBalance, 2),
            ],
            'movements' => $rows,
        ];
    }

    /**
     * Label manusiawi untuk jenis mutasi barang.
     */
    protected function getMovementTypeLabel(string $type): string
    {
        return match ($type) {
            'purchase' => 'Pembelian Masuk',
            'stock_in' => 'Barang Masuk',
            'adjustment_in' => 'Penyesuaian Masuk',
            'sale_return' => 'Retur Penjualan',
            'initial_stock' => 'Stok Awal',
            'sale' => 'Penjualan Kasir',
            'stock_out' => 'Barang Keluar',
            'adjustment_out' => 'Penyesuaian Keluar',
            'purchase_return', 'purchase_reversal' => 'Batal/Retur Pembelian',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}


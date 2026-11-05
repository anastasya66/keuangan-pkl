<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds for initial inventory stocks and movements.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            return;
        }

        $service = app(InventoryService::class);

        // Product 1: Beras Pandan Wangi
        $beras = Product::where('sku', 'PRD-SMB-001')->first();
        if ($beras && $beras->movements()->count() === 0) {
            $service->recordStockIn([
                'product_id' => $beras->id,
                'quantity' => 50,
                'movement_date' => '2026-11-01',
                'description' => 'Penerimaan stok awal dari supplier beras',
                'reference_number' => 'PO-BERAS-001',
            ], $admin->id);
        }

        // Product 2: Minyak Goreng Sawit 2L
        $minyak = Product::where('sku', 'PRD-SMB-002')->first();
        if ($minyak && $minyak->movements()->count() === 0) {
            $service->recordStockIn([
                'product_id' => $minyak->id,
                'quantity' => 30,
                'movement_date' => '2026-11-01',
                'description' => 'Penerimaan stok awal distributor minyak',
                'reference_number' => 'PO-MNYK-001',
            ], $admin->id);

            // Mutasi keluar sample
            $service->recordStockOut([
                'product_id' => $minyak->id,
                'quantity' => 4,
                'movement_date' => '2026-11-03',
                'description' => 'Penjualan grosir langganan',
                'reference_number' => 'INV-OUT-001',
            ], $admin->id);
        }

        // Product 3: Gula Pasir 1kg
        $gula = Product::where('sku', 'PRD-SMB-003')->first();
        if ($gula && $gula->movements()->count() === 0) {
            $service->recordStockIn([
                'product_id' => $gula->id,
                'quantity' => 40,
                'movement_date' => '2026-11-01',
                'description' => 'Stok awal gudang gula pasir',
                'reference_number' => 'PO-GULA-001',
            ], $admin->id);

            // Penyesuaian stok sample
            $service->recordAdjustment([
                'product_id' => $gula->id,
                'actual_stock' => 38,
                'adjustment_reason' => '2 bungkus kemasan rusak saat bongkar muat gudang',
                'movement_date' => '2026-11-04',
            ], $admin->id);
        }

        // Product 4: Air Mineral Botol 600ml
        $air = Product::where('sku', 'PRD-MNM-001')->first();
        if ($air && $air->movements()->count() === 0) {
            $service->recordStockIn([
                'product_id' => $air->id,
                'quantity' => 120,
                'movement_date' => '2026-11-02',
                'description' => 'Drop stok 5 dus air mineral botol',
                'reference_number' => 'SJ-AIR-001',
            ], $admin->id);
        }
    }
}


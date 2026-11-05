<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sembako = Category::where('name', 'Sembako')->first();
        $minuman = Category::where('name', 'Minuman')->first();
        $snack = Category::where('name', 'Makanan Ringan')->first();
        $bumbu = Category::where('name', 'Bumbu & Dapur')->first();
        $perawatan = Category::where('name', 'Perawatan Diri & Kebersihan')->first();

        $kg = Unit::where('symbol', 'kg')->first();
        $pcs = Unit::where('symbol', 'pcs')->first();
        $btl = Unit::where('symbol', 'btl')->first();
        $pack = Unit::where('symbol', 'pack')->first();
        $liter = Unit::where('symbol', 'L')->first();

        $products = [
            [
                'category_id' => $sembako?->id,
                'unit_id' => $kg?->id,
                'sku' => 'PRD-SMB-001',
                'barcode' => '8991234560011',
                'name' => 'Beras Pandan Wangi Premium 5kg',
                'description' => 'Beras pulen pilihan kualitas super.',
                'purchase_price' => 65000.00,
                'selling_price' => 75000.00,
                'is_active' => true,
            ],
            [
                'category_id' => $sembako?->id,
                'unit_id' => $liter?->id,
                'sku' => 'PRD-SMB-002',
                'barcode' => '8991234560028',
                'name' => 'Minyak Goreng Sawit 2 Liter',
                'description' => 'Minyak goreng jernih 2 kali penyaringan.',
                'purchase_price' => 31000.00,
                'selling_price' => 36000.00,
                'is_active' => true,
            ],
            [
                'category_id' => $sembako?->id,
                'unit_id' => $kg?->id,
                'sku' => 'PRD-SMB-003',
                'barcode' => '8991234560035',
                'name' => 'Gula Pasir Kristal Putih 1kg',
                'description' => 'Gula pasir tebu kristal higienis.',
                'purchase_price' => 14500.00,
                'selling_price' => 17500.00,
                'is_active' => true,
            ],
            [
                'category_id' => $minuman?->id,
                'unit_id' => $btl?->id,
                'sku' => 'PRD-MNM-001',
                'barcode' => '8991234560042',
                'name' => 'Air Mineral Botol 600ml',
                'description' => 'Air mineral pegunungan alami.',
                'purchase_price' => 2500.00,
                'selling_price' => 3500.00,
                'is_active' => true,
            ],
            [
                'category_id' => $minuman?->id,
                'unit_id' => $btl?->id,
                'sku' => 'PRD-MNM-002',
                'barcode' => '8991234560059',
                'name' => 'Teh Melati Segar 500ml',
                'description' => 'Minuman teh rasa melati dingin menyegarkan.',
                'purchase_price' => 3800.00,
                'selling_price' => 5000.00,
                'is_active' => true,
            ],
            [
                'category_id' => $snack?->id,
                'unit_id' => $pack?->id,
                'sku' => 'PRD-SNK-001',
                'barcode' => '8991234560066',
                'name' => 'Keripik Kentang Balado 75g',
                'description' => 'Keripik kentang renyah rasa balado pedas manis.',
                'purchase_price' => 8500.00,
                'selling_price' => 11000.00,
                'is_active' => true,
            ],
            [
                'category_id' => $snack?->id,
                'unit_id' => $pack?->id,
                'sku' => 'PRD-SNK-002',
                'barcode' => '8991234560073',
                'name' => 'Biskuit Cokelat Sandwich 120g',
                'description' => 'Biskuit renyah dengan krim cokelat lezat.',
                'purchase_price' => 7000.00,
                'selling_price' => 9500.00,
                'is_active' => true,
            ],
            [
                'category_id' => $bumbu?->id,
                'unit_id' => $btl?->id,
                'sku' => 'PRD-BMB-001',
                'barcode' => '8991234560080',
                'name' => 'Kecap Manis Refill 550ml',
                'description' => 'Kecap manis kedelai hitam berkualitas tinggi.',
                'purchase_price' => 17000.00,
                'selling_price' => 20500.00,
                'is_active' => true,
            ],
            [
                'category_id' => $perawatan?->id,
                'unit_id' => $pcs?->id,
                'sku' => 'PRD-PRW-001',
                'barcode' => '8991234560097',
                'name' => 'Sabun Mandi Antiseptik 85g',
                'description' => 'Sabun batang perlindungan higienis kuman.',
                'purchase_price' => 3500.00,
                'selling_price' => 5000.00,
                'is_active' => true,
            ],
            [
                'category_id' => $snack?->id,
                'unit_id' => $pack?->id,
                'sku' => 'PRD-SNK-003',
                'barcode' => '8991234560103',
                'name' => 'Wafer Cokelat Karamel 40g (Arsip)',
                'description' => 'Contoh produk non-aktif untuk pengujian status.',
                'purchase_price' => 2000.00,
                'selling_price' => 3000.00,
                'is_active' => false,
            ],
        ];

        foreach ($products as $p) {
            if ($p['category_id'] && $p['unit_id']) {
                Product::firstOrCreate(['sku' => $p['sku']], $p);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Income Categories
            [
                'name' => 'Penjualan Produk',
                'type' => 'income',
                'description' => 'Pendapatan langsung dari hasil penjualan produk toko ke konsumen.',
                'is_active' => true,
            ],
            [
                'name' => 'Pendapatan Tambahan',
                'type' => 'income',
                'description' => 'Pendapatan dari jasa pengiriman, titipan, atau komisi layanan.',
                'is_active' => true,
            ],
            [
                'name' => 'Pendapatan Lainnya',
                'type' => 'income',
                'description' => 'Pemasukan non-operasional atau bunga bank.',
                'is_active' => true,
            ],
            [
                'name' => 'Kategori Pemasukan Nonaktif',
                'type' => 'income',
                'description' => 'Contoh kategori pemasukan non-aktif untuk pengujian validasi.',
                'is_active' => false,
            ],

            // Expense Categories
            [
                'name' => 'Pembelian Stok',
                'type' => 'expense',
                'description' => 'Pengeluaran untuk kulakan atau restok barang dagangan dari supplier.',
                'is_active' => true,
            ],
            [
                'name' => 'Biaya Operasional',
                'type' => 'expense',
                'description' => 'Biaya perlengkapan toko, kantong kresek, nota, dan alat kebersihan.',
                'is_active' => true,
            ],
            [
                'name' => 'Biaya Transportasi',
                'type' => 'expense',
                'description' => 'Biaya bensin dan ongkos angkut pengiriman barang dagangan.',
                'is_active' => true,
            ],
            [
                'name' => 'Biaya Listrik',
                'type' => 'expense',
                'description' => 'Pembayaran tagihan listrik PLN dan air toko bulanan.',
                'is_active' => true,
            ],
            [
                'name' => 'Biaya Lainnya',
                'type' => 'expense',
                'description' => 'Pengeluaran darurat atau biaya tak terduga lainnya.',
                'is_active' => true,
            ],
            [
                'name' => 'Kategori Pengeluaran Nonaktif',
                'type' => 'expense',
                'description' => 'Contoh kategori pengeluaran non-aktif untuk pengujian validasi.',
                'is_active' => false,
            ],
        ];

        foreach ($categories as $cat) {
            TransactionCategory::firstOrCreate(
                ['name' => $cat['name'], 'type' => $cat['type']],
                $cat
            );
        }
    }
}


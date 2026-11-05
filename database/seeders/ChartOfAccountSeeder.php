<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // ASSETS (1000 - 1999)
            [
                'account_code' => '1110',
                'account_name' => 'Kas Toko',
                'account_type' => 'assets',
                'normal_balance' => 'debit',
                'description' => 'Kas tunai fisik di kasir toko',
                'is_active' => true,
            ],
            [
                'account_code' => '1120',
                'account_name' => 'Bank Operasional',
                'account_type' => 'assets',
                'normal_balance' => 'debit',
                'description' => 'Rekening bank untuk transaksi non-tunai dan transfer',
                'is_active' => true,
            ],
            [
                'account_code' => '1130',
                'account_name' => 'Persediaan Barang Dagang',
                'account_type' => 'assets',
                'normal_balance' => 'debit',
                'description' => 'Nilai aset persediaan produk yang siap dijual',
                'is_active' => true,
            ],
            [
                'account_code' => '1140',
                'account_name' => 'Piutang Usaha',
                'account_type' => 'assets',
                'normal_balance' => 'debit',
                'description' => 'Tagihan belum terbayar oleh pelanggan',
                'is_active' => true,
            ],
            [
                'account_code' => '1210',
                'account_name' => 'Peralatan Toko & Rak',
                'account_type' => 'assets',
                'normal_balance' => 'debit',
                'description' => 'Aset tetap berupa etalase, rak display, dan perlengkapan toko',
                'is_active' => true,
            ],

            // LIABILITIES (2000 - 2999)
            [
                'account_code' => '2110',
                'account_name' => 'Utang Usaha / Supplier',
                'account_type' => 'liabilities',
                'normal_balance' => 'credit',
                'description' => 'Kewajiban pembayaran tempo kepada pemasok/distributor',
                'is_active' => true,
            ],
            [
                'account_code' => '2120',
                'account_name' => 'Beban yang Masih Harus Dibayar',
                'account_type' => 'liabilities',
                'normal_balance' => 'credit',
                'description' => 'Kewajiban beban operasional yang belum dibayarkan',
                'is_active' => true,
            ],

            // EQUITY (3000 - 3999)
            [
                'account_code' => '3100',
                'account_name' => 'Modal Awal Pemilik',
                'account_type' => 'equity',
                'normal_balance' => 'credit',
                'description' => 'Setoran modal pemilik untuk pendirian dan operasional toko',
                'is_active' => true,
            ],
            [
                'account_code' => '3200',
                'account_name' => 'Prive Pemilik',
                'account_type' => 'equity',
                'normal_balance' => 'debit',
                'description' => 'Penarikan dana pribadi pemilik toko',
                'is_active' => true,
            ],

            // REVENUE (4000 - 4999)
            [
                'account_code' => '4100',
                'account_name' => 'Pendapatan Penjualan Toko',
                'account_type' => 'revenue',
                'normal_balance' => 'credit',
                'description' => 'Penerimaan utama dari hasil penjualan barang dagangan',
                'is_active' => true,
            ],
            [
                'account_code' => '4200',
                'account_name' => 'Pendapatan Selisih Persediaan',
                'account_type' => 'revenue',
                'normal_balance' => 'credit',
                'description' => 'Keuntungan dari selisih lebih opname fisik persediaan barang',
                'is_active' => true,
            ],
            [
                'account_code' => '4300',
                'account_name' => 'Pendapatan Lain-lain',
                'account_type' => 'revenue',
                'normal_balance' => 'credit',
                'description' => 'Penerimaan non-operasional toko lainnya',
                'is_active' => true,
            ],

            // EXPENSES (5000 - 5999)
            [
                'account_code' => '5100',
                'account_name' => 'Beban Operasional Toko',
                'account_type' => 'expenses',
                'normal_balance' => 'debit',
                'description' => 'Beban umum sehari-hari toko (kantong plastik, lakban, ATK)',
                'is_active' => true,
            ],
            [
                'account_code' => '5200',
                'account_name' => 'Beban Kerugian Selisih Persediaan',
                'account_type' => 'expenses',
                'normal_balance' => 'debit',
                'description' => 'Kerugian akibat barang rusak, afkir, atau susut saat opname fisik',
                'is_active' => true,
            ],
            [
                'account_code' => '5300',
                'account_name' => 'Beban Gaji Karyawan',
                'account_type' => 'expenses',
                'normal_balance' => 'debit',
                'description' => 'Gaji dan tunjangan staf serta pengelola toko',
                'is_active' => true,
            ],
            [
                'account_code' => '5400',
                'account_name' => 'Beban Listrik, Air & Internet',
                'account_type' => 'expenses',
                'normal_balance' => 'debit',
                'description' => 'Tagihan utilitas bulanan toko',
                'is_active' => true,
            ],
            [
                'account_code' => '5500',
                'account_name' => 'Beban Sewa Tempat Usaha',
                'account_type' => 'expenses',
                'normal_balance' => 'debit',
                'description' => 'Biaya sewa ruko / bangunan toko',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $item) {
            ChartOfAccount::firstOrCreate(
                ['account_code' => $item['account_code']],
                $item
            );
        }
    }
}

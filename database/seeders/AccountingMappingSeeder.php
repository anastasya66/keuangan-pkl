<?php

namespace Database\Seeders;

use App\Models\AccountingMapping;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class AccountingMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = ChartOfAccount::all()->keyBy('account_code');

        $mappings = [
            [
                'mapping_code' => 'cash_default',
                'name' => 'Akun Kas Utama Default',
                'account_code' => '1110',
                'description' => 'Akun kas tunai default untuk transaksi pemasukan, pengeluaran, dan pembelian tunai',
            ],
            [
                'mapping_code' => 'revenue_default',
                'name' => 'Akun Pendapatan Utama Default',
                'account_code' => '4100',
                'description' => 'Akun pendapatan default untuk pencatatan transaksi pemasukan toko',
            ],
            [
                'mapping_code' => 'expense_default',
                'name' => 'Akun Beban Operasional Default',
                'account_code' => '5100',
                'description' => 'Akun beban default untuk pencatatan transaksi pengeluaran operasional toko',
            ],
            [
                'mapping_code' => 'inventory_asset',
                'name' => 'Akun Aset Persediaan Barang',
                'account_code' => '1130',
                'description' => 'Akun persediaan barang dagang untuk stok masuk dan mutasi penyesuaian opname',
            ],
            [
                'mapping_code' => 'inventory_loss',
                'name' => 'Akun Kerugian Persediaan',
                'account_code' => '5200',
                'description' => 'Akun beban kerugian akibat selisih kurang opname fisik atau barang rusak',
            ],
            [
                'mapping_code' => 'inventory_gain',
                'name' => 'Akun Pendapatan Selisih Persediaan',
                'account_code' => '4200',
                'description' => 'Akun pendapatan lain akibat selisih lebih opname fisik barang',
            ],
            [
                'mapping_code' => 'cogs_default',
                'name' => 'Akun Beban Pokok Penjualan (HPP)',
                'account_code' => '5110',
                'description' => 'Akun beban pokok penjualan untuk mencatat HPP saat penjualan POS',
            ],
            [
                'mapping_code' => 'payable_default',
                'name' => 'Akun Utang Usaha / Supplier',
                'account_code' => '2110',
                'description' => 'Akun utang usaha untuk pembelian barang dagang secara tempo',
            ],
            [
                'mapping_code' => 'bank_default',
                'name' => 'Akun Bank Operasional',
                'account_code' => '1120',
                'description' => 'Akun bank untuk pembayaran non-tunai atau transfer',
            ],
        ];

        foreach ($mappings as $item) {
            $account = $accounts->get($item['account_code']);
            if ($account) {
                AccountingMapping::updateOrCreate(
                    ['mapping_code' => $item['mapping_code']],
                    [
                        'name' => $item['name'],
                        'account_id' => $account->id,
                        'description' => $item['description'],
                    ]
                );
            }
        }
    }
}


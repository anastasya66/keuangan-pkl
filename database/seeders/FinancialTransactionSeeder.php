<?php

namespace Database\Seeders;

use App\Models\FinancialTransaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinancialTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $staff = User::where('role', 'staff')->first();

        $catPenjualan = TransactionCategory::where('name', 'Penjualan Produk')->first();
        $catTambahan = TransactionCategory::where('name', 'Pendapatan Tambahan')->first();
        $catStok = TransactionCategory::where('name', 'Pembelian Stok')->first();
        $catOperasional = TransactionCategory::where('name', 'Biaya Operasional')->first();
        $catListrik = TransactionCategory::where('name', 'Biaya Listrik')->first();

        $transactions = [
            // Incomes
            [
                'transaction_code' => 'TRX-20261101-0001',
                'transaction_type' => 'income',
                'category_id' => $catPenjualan?->id,
                'amount' => 850000.00,
                'transaction_date' => '2026-11-01',
                'description' => 'Penjualan sembako dan minuman shift pagi',
                'created_by' => $staff?->id ?? $admin?->id,
                'status' => 'completed',
            ],
            [
                'transaction_code' => 'TRX-20261102-0001',
                'transaction_type' => 'income',
                'category_id' => $catPenjualan?->id,
                'amount' => 1250000.00,
                'transaction_date' => '2026-11-02',
                'description' => 'Penjualan grosir sembako pelanggan setia',
                'created_by' => $admin?->id,
                'status' => 'completed',
            ],
            [
                'transaction_code' => 'TRX-20261103-0001',
                'transaction_type' => 'income',
                'category_id' => $catTambahan?->id,
                'amount' => 75000.00,
                'transaction_date' => '2026-11-03',
                'description' => 'Komisi jasa titip pembayaran tagihan',
                'created_by' => $staff?->id ?? $admin?->id,
                'status' => 'completed',
            ],

            // Expenses
            [
                'transaction_code' => 'TRX-20261101-0002',
                'transaction_type' => 'expense',
                'category_id' => $catOperasional?->id,
                'amount' => 45000.00,
                'transaction_date' => '2026-11-01',
                'description' => 'Beli kantong plastik kresek dan lakban toko',
                'created_by' => $staff?->id ?? $admin?->id,
                'status' => 'completed',
            ],
            [
                'transaction_code' => 'TRX-20261102-0002',
                'transaction_type' => 'expense',
                'category_id' => $catStok?->id,
                'amount' => 650000.00,
                'transaction_date' => '2026-11-02',
                'description' => 'Kulakan beras dan minyak goreng ke agen distributor',
                'created_by' => $admin?->id,
                'status' => 'completed',
            ],
            [
                'transaction_code' => 'TRX-20261104-0001',
                'transaction_type' => 'expense',
                'category_id' => $catListrik?->id,
                'amount' => 185000.00,
                'transaction_date' => '2026-11-04',
                'description' => 'Token listrik PLN 900VA periode November',
                'created_by' => $admin?->id,
                'status' => 'completed',
            ],

            // Cancelled Transaction Example
            [
                'transaction_code' => 'TRX-20261104-0002',
                'transaction_type' => 'income',
                'category_id' => $catPenjualan?->id,
                'amount' => 200000.00,
                'transaction_date' => '2026-11-04',
                'description' => 'Penjualan salah input kasir (dibatalkan)',
                'created_by' => $staff?->id ?? $admin?->id,
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $admin?->id,
                'cancellation_reason' => 'Salah input nominal pada pesanan dibatalkan',
            ],
        ];

        foreach ($transactions as $t) {
            if ($t['category_id'] && $t['created_by']) {
                FinancialTransaction::firstOrCreate(['transaction_code' => $t['transaction_code']], $t);
            }
        }
    }
}

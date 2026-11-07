<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed System Settings
        $settings = [
            // Store Information
            ['key' => 'store_name', 'value' => 'FinToko Minimarket & Retail', 'group' => 'store', 'type' => 'string', 'description' => 'Nama resmi toko atau minimarket', 'is_public' => true],
            ['key' => 'store_logo', 'value' => null, 'group' => 'store', 'type' => 'string', 'description' => 'URL logo toko', 'is_public' => true],
            ['key' => 'store_address', 'value' => 'Jl. Pemuda No. 45, Semarang, Jawa Tengah 50132', 'group' => 'store', 'type' => 'string', 'description' => 'Alamat lengkap toko', 'is_public' => true],
            ['key' => 'store_phone', 'value' => '024-87654321 / 0812-3456-7890', 'group' => 'store', 'type' => 'string', 'description' => 'Nomor kontak / WhatsApp toko', 'is_public' => true],
            ['key' => 'store_email', 'value' => 'info@fintoko.id', 'group' => 'store', 'type' => 'string', 'description' => 'Email resmi toko', 'is_public' => true],
            ['key' => 'store_owner', 'value' => 'Budi Santoso', 'group' => 'store', 'type' => 'string', 'description' => 'Nama pemilik / penanggung jawab toko', 'is_public' => false],
            ['key' => 'store_npwp', 'value' => '01.234.567.8-901.000', 'group' => 'store', 'type' => 'string', 'description' => 'Nomor Pokok Wajib Pajak (NPWP)', 'is_public' => false],
            ['key' => 'store_description', 'value' => 'Aplikasi Pengelolaan Kasir, Persediaan & Keuangan Toko', 'group' => 'store', 'type' => 'string', 'description' => 'Deskripsi profil toko', 'is_public' => true],
            ['key' => 'receipt_header', 'value' => "SELAMAT DATANG DI FINTOKO\nBelanja Hemat, Lengkap & Nyaman", 'group' => 'store', 'type' => 'string', 'description' => 'Pesan header struk belanja', 'is_public' => true],
            ['key' => 'receipt_footer', 'value' => "Terima Kasih Atas Kunjungan Anda!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan.", 'group' => 'store', 'type' => 'string', 'description' => 'Pesan footer struk belanja', 'is_public' => true],

            // Transaction Settings
            ['key' => 'date_format', 'value' => 'd/m/Y', 'group' => 'transaction', 'type' => 'string', 'description' => 'Format tampilan tanggal', 'is_public' => true],
            ['key' => 'currency', 'value' => 'IDR', 'group' => 'transaction', 'type' => 'string', 'description' => 'Mata uang standar', 'is_public' => true],
            ['key' => 'currency_symbol', 'value' => 'Rp', 'group' => 'transaction', 'type' => 'string', 'description' => 'Simbol mata uang', 'is_public' => true],
            ['key' => 'thousand_separator', 'value' => '.', 'group' => 'transaction', 'type' => 'string', 'description' => 'Pemisah ribuan', 'is_public' => true],
            ['key' => 'decimal_separator', 'value' => ',', 'group' => 'transaction', 'type' => 'string', 'description' => 'Pemisah desimal', 'is_public' => true],
            ['key' => 'rounding_mode', 'value' => 'round', 'group' => 'transaction', 'type' => 'string', 'description' => 'Metode pembulatan (round/ceil/floor)', 'is_public' => false],
            ['key' => 'allow_transaction_cancellation', 'value' => '1', 'group' => 'transaction', 'type' => 'boolean', 'description' => 'Izinkan pembatalan transaksi oleh admin', 'is_public' => false],
            ['key' => 'allow_transaction_edit', 'value' => '1', 'group' => 'transaction', 'type' => 'boolean', 'description' => 'Izinkan pengeditan transaksi berstatus draft', 'is_public' => false],
            ['key' => 'require_approval', 'value' => '0', 'group' => 'transaction', 'type' => 'boolean', 'description' => 'Memerlukan persetujuan admin untuk transaksi besar', 'is_public' => false],

            // POS Settings
            ['key' => 'pos_barcode_auto_submit', 'value' => '1', 'group' => 'pos', 'type' => 'boolean', 'description' => 'Otomatis masukkan ke keranjang saat barcode di-scan', 'is_public' => true],
            ['key' => 'pos_default_payment_method', 'value' => 'cash', 'group' => 'pos', 'type' => 'string', 'description' => 'Metode pembayaran default kasir', 'is_public' => true],
            ['key' => 'pos_allow_mixed_payment', 'value' => '1', 'group' => 'pos', 'type' => 'boolean', 'description' => 'Izinkan pembayaran kombinasi / campuran', 'is_public' => false],
            ['key' => 'pos_max_discount_percent', 'value' => '50', 'group' => 'pos', 'type' => 'integer', 'description' => 'Batas maksimal diskon kasir dalam persen (%)', 'is_public' => false],
            ['key' => 'pos_receipt_printer_width', 'value' => '58mm', 'group' => 'pos', 'type' => 'string', 'description' => 'Lebar printer thermal struk (58mm / 80mm)', 'is_public' => true],
            ['key' => 'pos_enable_cash_session', 'value' => '0', 'group' => 'pos', 'type' => 'boolean', 'description' => 'Aktifkan pembukaan modal awal kasir & tutup shift', 'is_public' => false],
            ['key' => 'pos_default_cash_float', 'value' => '100000', 'group' => 'pos', 'type' => 'integer', 'description' => 'Nominal modal awal kasir default (Rp)', 'is_public' => false],

            // Inventory Settings
            ['key' => 'inventory_valuation_method', 'value' => 'moving_average', 'group' => 'inventory', 'type' => 'string', 'description' => 'Metode penilaian persediaan (moving_average)', 'is_public' => true],
            ['key' => 'inventory_allow_negative_stock', 'value' => '0', 'group' => 'inventory', 'type' => 'boolean', 'description' => 'Izinkan stok bernilai minus / negatif', 'is_public' => false],
            ['key' => 'inventory_default_min_stock', 'value' => '5', 'group' => 'inventory', 'type' => 'integer', 'description' => 'Batas stok minimum peringatan menipis', 'is_public' => true],
            ['key' => 'inventory_low_stock_notification', 'value' => '1', 'group' => 'inventory', 'type' => 'boolean', 'description' => 'Aktifkan notifikasi stok menipis', 'is_public' => true],
            ['key' => 'inventory_barcode_prefix', 'value' => 'PRD-', 'group' => 'inventory', 'type' => 'string', 'description' => 'Prefix kode produk otomatis', 'is_public' => true],
            ['key' => 'inventory_auto_generate_barcode', 'value' => '1', 'group' => 'inventory', 'type' => 'boolean', 'description' => 'Otomatis generate barcode saat tambah produk baru', 'is_public' => false],

            // Financial & Accounting Settings
            ['key' => 'financial_default_cash_account_id', 'value' => null, 'group' => 'financial', 'type' => 'integer', 'description' => 'ID Akun Kas Utama', 'is_public' => false],
            ['key' => 'financial_default_bank_account_id', 'value' => null, 'group' => 'financial', 'type' => 'integer', 'description' => 'ID Akun Bank Utama', 'is_public' => false],
            ['key' => 'financial_allow_debt', 'value' => '1', 'group' => 'financial', 'type' => 'boolean', 'description' => 'Izinkan pembelian tempo / utang usaha', 'is_public' => false],
            ['key' => 'financial_default_debt_due_days', 'value' => '30', 'group' => 'financial', 'type' => 'integer', 'description' => 'Jatuh tempo utang tempo default (hari)', 'is_public' => false],
            ['key' => 'accounting_fiscal_year', 'value' => '2026', 'group' => 'accounting', 'type' => 'integer', 'description' => 'Tahun buku aktif', 'is_public' => true],
            ['key' => 'accounting_auto_journal', 'value' => '1', 'group' => 'accounting', 'type' => 'boolean', 'description' => 'Otomatis buat jurnal umum pada transaksi', 'is_public' => false],
            ['key' => 'accounting_lock_past_periods', 'value' => '0', 'group' => 'accounting', 'type' => 'boolean', 'description' => 'Kunci jurnal pada periode yang telah lewat', 'is_public' => false],

            // Notification Settings
            ['key' => 'notify_low_stock', 'value' => '1', 'group' => 'notification', 'type' => 'boolean', 'description' => 'Notifikasi saat stok barang menipis', 'is_public' => true],
            ['key' => 'notify_out_of_stock', 'value' => '1', 'group' => 'notification', 'type' => 'boolean', 'description' => 'Notifikasi saat stok barang habis', 'is_public' => true],
            ['key' => 'notify_purchase_received', 'value' => '1', 'group' => 'notification', 'type' => 'boolean', 'description' => 'Notifikasi saat barang kulakan diterima', 'is_public' => true],
            ['key' => 'notify_sale_completed', 'value' => '0', 'group' => 'notification', 'type' => 'boolean', 'description' => 'Notifikasi setiap penjualan kasir selesai', 'is_public' => true],
            ['key' => 'notify_debt_due', 'value' => '1', 'group' => 'notification', 'type' => 'boolean', 'description' => 'Peringatan utang supplier mendekati jatuh tempo', 'is_public' => true],
            ['key' => 'notify_backup_alert', 'value' => '1', 'group' => 'notification', 'type' => 'boolean', 'description' => 'Notifikasi status backup database berkala', 'is_public' => true],

            // Security Settings
            ['key' => 'security_min_password_length', 'value' => '8', 'group' => 'security', 'type' => 'integer', 'description' => 'Panjang minimal password', 'is_public' => false],
            ['key' => 'security_require_mixed_case', 'value' => '0', 'group' => 'security', 'type' => 'boolean', 'description' => 'Wajib mengandung huruf besar & kecil', 'is_public' => false],
            ['key' => 'security_require_numbers', 'value' => '0', 'group' => 'security', 'type' => 'boolean', 'description' => 'Wajib mengandung angka', 'is_public' => false],
            ['key' => 'security_require_symbols', 'value' => '0', 'group' => 'security', 'type' => 'boolean', 'description' => 'Wajib mengandung karakter khusus', 'is_public' => false],
            ['key' => 'security_max_login_attempts', 'value' => '5', 'group' => 'security', 'type' => 'integer', 'description' => 'Maksimal percobaan login salah', 'is_public' => false],
            ['key' => 'security_lockout_minutes', 'value' => '15', 'group' => 'security', 'type' => 'integer', 'description' => 'Durasi blokir sementara jika gagal login (menit)', 'is_public' => false],
            ['key' => 'security_session_timeout_minutes', 'value' => '120', 'group' => 'security', 'type' => 'integer', 'description' => 'Waktu kedaluwarsa sesi tidak aktif (menit)', 'is_public' => false],

            // Application Preferences
            ['key' => 'pref_locale', 'value' => 'id', 'group' => 'preferences', 'type' => 'string', 'description' => 'Bahasa sistem default', 'is_public' => true],
            ['key' => 'pref_timezone', 'value' => 'Asia/Jakarta', 'group' => 'preferences', 'type' => 'string', 'description' => 'Zona waktu sistem', 'is_public' => true],
            ['key' => 'pref_table_per_page', 'value' => '15', 'group' => 'preferences', 'type' => 'integer', 'description' => 'Jumlah baris per halaman pada tabel data', 'is_public' => true],
        ];

        // Temukan akun kas default jika tabel chart_of_accounts sudah ada
        $cashAcc = ChartOfAccount::where('account_code', '1101')->first();
        $bankAcc = ChartOfAccount::where('account_code', '1102')->first();

        foreach ($settings as $setting) {
            if ($setting['key'] === 'financial_default_cash_account_id' && $cashAcc) {
                $setting['value'] = (string) $cashAcc->id;
            }
            if ($setting['key'] === 'financial_default_bank_account_id' && $bankAcc) {
                $setting['value'] = (string) $bankAcc->id;
            }

            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // 2. Seed Roles
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator / Pemilik', 'description' => 'Akses penuh ke seluruh modul, keuangan, dan pengaturan sistem.', 'is_system' => true],
            ['name' => 'staff', 'display_name' => 'Staff Kasir / Gudang', 'description' => 'Akses operasional penjualan POS, persediaan, dan laporan operasional.', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                array_merge($role, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $staffRoleId = DB::table('roles')->where('name', 'staff')->value('id');

        // 3. Seed Permissions
        $permissions = [
            // POS
            ['name' => 'pos.view', 'group' => 'pos', 'display_name' => 'Buka Halaman Kasir POS', 'description' => 'Melihat katalog dan membuka kasir'],
            ['name' => 'pos.checkout', 'group' => 'pos', 'display_name' => 'Transaksi Penjualan Kasir', 'description' => 'Menyelesaikan pembayaran pelanggan'],
            ['name' => 'pos.cancel', 'group' => 'pos', 'display_name' => 'Batalkan Penjualan Kasir', 'description' => 'Membatalkan transaksi penjualan'],
            ['name' => 'pos.reprint', 'group' => 'pos', 'display_name' => 'Cetak Ulang Struk', 'description' => 'Mencetak ulang struk penjualan'],

            // Pembelian
            ['name' => 'purchases.view', 'group' => 'purchases', 'display_name' => 'Lihat Data Pembelian', 'description' => 'Melihat riwayat dan detail kulakan'],
            ['name' => 'purchases.create', 'group' => 'purchases', 'display_name' => 'Buat Transaksi Pembelian', 'description' => 'Membuat draft transaksi kulakan'],
            ['name' => 'purchases.receive', 'group' => 'purchases', 'display_name' => 'Konfirmasi Barang Masuk', 'description' => 'Menerima barang & menambah stok'],
            ['name' => 'purchases.cancel', 'group' => 'purchases', 'display_name' => 'Batalkan Pembelian', 'description' => 'Membatalkan transaksi kulakan'],
            ['name' => 'purchases.pay_debt', 'group' => 'purchases', 'display_name' => 'Bayar Utang Tempo', 'description' => 'Membayar sisa tagihan utang supplier'],

            // Inventory
            ['name' => 'inventory.view', 'group' => 'inventory', 'display_name' => 'Lihat Persediaan & Kartu Stok', 'description' => 'Melihat stok, mutasi, dan kartu stok'],
            ['name' => 'inventory.stock_in', 'group' => 'inventory', 'display_name' => 'Catat Barang Masuk', 'description' => 'Mencatat penerimaan barang non-pembelian'],
            ['name' => 'inventory.stock_out', 'group' => 'inventory', 'display_name' => 'Catat Barang Keluar', 'description' => 'Mencatat pengeluaran barang rusak/expired'],
            ['name' => 'inventory.adjust', 'group' => 'inventory', 'display_name' => 'Penyesuaian Stok (Opname)', 'description' => 'Melakukan stock adjustment'],
            ['name' => 'inventory.valuation', 'group' => 'inventory', 'display_name' => 'Lihat Valuasi Nilai Persediaan', 'description' => 'Melihat laporan nilai aset HPP'],

            // Keuangan & Kas
            ['name' => 'financial.view', 'group' => 'financial', 'display_name' => 'Lihat Transaksi Kas', 'description' => 'Melihat arus kas masuk dan keluar'],
            ['name' => 'financial.create', 'group' => 'financial', 'display_name' => 'Catat Pemasukan / Pengeluaran', 'description' => 'Membuat pencatatan kas baru'],
            ['name' => 'financial.cancel', 'group' => 'financial', 'display_name' => 'Batalkan Transaksi Kas', 'description' => 'Membatalkan pemasukan/pengeluaran'],

            // Akuntansi
            ['name' => 'accounting.view', 'group' => 'accounting', 'display_name' => 'Lihat Akuntansi', 'description' => 'Melihat rekening COA dan buku besar'],
            ['name' => 'accounting.journal_create', 'group' => 'accounting', 'display_name' => 'Buat Jurnal Umum', 'description' => 'Membuat draft jurnal akuntansi'],
            ['name' => 'accounting.journal_post', 'group' => 'accounting', 'display_name' => 'Posting Jurnal', 'description' => 'Memposting jurnal ke buku besar'],
            ['name' => 'accounting.journal_reverse', 'group' => 'accounting', 'display_name' => 'Pembalikan (Reverse) Jurnal', 'description' => 'Membalikkan jurnal akuntansi'],

            // Laporan
            ['name' => 'reports.view', 'group' => 'reports', 'display_name' => 'Lihat Laporan Bisnis', 'description' => 'Melihat laporan laba rugi & neraca'],
            ['name' => 'reports.export', 'group' => 'reports', 'display_name' => 'Ekspor Laporan (Excel & PDF)', 'description' => 'Mengunduh laporan dalam format file'],

            // Master Data
            ['name' => 'master.manage', 'group' => 'master', 'display_name' => 'Kelola Master Data', 'description' => 'Menambah/mengedit produk, kategori, satuan, supplier'],

            // Settings & Audit
            ['name' => 'settings.view', 'group' => 'settings', 'display_name' => 'Lihat Pengaturan Sistem', 'description' => 'Melihat halaman pengaturan toko'],
            ['name' => 'settings.update', 'group' => 'settings', 'display_name' => 'Ubah Pengaturan Sistem', 'description' => 'Mengubah konfigurasi toko & POS'],
            ['name' => 'users.manage', 'group' => 'settings', 'display_name' => 'Kelola Pengguna & Hak Akses', 'description' => 'Mengatur staf dan role permission'],
            ['name' => 'backup.manage', 'group' => 'settings', 'display_name' => 'Kelola Cadangan Database', 'description' => 'Membuat dan memulihkan backup'],
            ['name' => 'audit.view', 'group' => 'settings', 'display_name' => 'Lihat Riwayat Aktivitas (Log)', 'description' => 'Melihat audit trail aktivitas pengguna'],
        ];

        foreach ($permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $p['name']],
                array_merge($p, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // Hubungkan semua permissions ke Admin
        $allPermIds = DB::table('permissions')->pluck('id');
        foreach ($allPermIds as $pId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $adminRoleId, 'permission_id' => $pId]
            );
        }

        // Hubungkan permissions operasional standar ke Staff
        $staffPermNames = [
            'pos.view', 'pos.checkout', 'pos.reprint',
            'purchases.view',
            'inventory.view', 'inventory.stock_in', 'inventory.stock_out',
            'reports.view',
        ];
        $staffPermIds = DB::table('permissions')->whereIn('name', $staffPermNames)->pluck('id');
        foreach ($staffPermIds as $pId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $staffRoleId, 'permission_id' => $pId]
            );
        }

        // 4. Seed Document Sequences
        $sequences = [
            ['document_type' => 'purchase', 'name' => 'Nomor Invoice Pembelian', 'prefix' => 'INV', 'format' => '{PREFIX}/{YYYY}/{MM}/{SEQ}', 'padding' => 4, 'reset_frequency' => 'monthly'],
            ['document_type' => 'pos_sale', 'name' => 'Nomor Struk Penjualan Kasir', 'prefix' => 'POS', 'format' => '{PREFIX}-{YYYY}{MM}{DD}-{SEQ}', 'padding' => 4, 'reset_frequency' => 'daily'],
            ['document_type' => 'general_journal', 'name' => 'Nomor Jurnal Akuntansi', 'prefix' => 'JRN', 'format' => '{PREFIX}/{YYYY}/{MM}/{SEQ}', 'padding' => 4, 'reset_frequency' => 'monthly'],
            ['document_type' => 'income', 'name' => 'Nomor Kas Masuk (Pemasukan)', 'prefix' => 'INC', 'format' => '{PREFIX}/{YYYY}/{MM}/{SEQ}', 'padding' => 4, 'reset_frequency' => 'monthly'],
            ['document_type' => 'expense', 'name' => 'Nomor Kas Keluar (Pengeluaran)', 'prefix' => 'EXP', 'format' => '{PREFIX}/{YYYY}/{MM}/{SEQ}', 'padding' => 4, 'reset_frequency' => 'monthly'],
            ['document_type' => 'stock_adjustment', 'name' => 'Nomor Penyesuaian Stok (Opname)', 'prefix' => 'ADJ', 'format' => '{PREFIX}/{YYYY}/{MM}/{SEQ}', 'padding' => 4, 'reset_frequency' => 'monthly'],
            ['document_type' => 'supplier_code', 'name' => 'Kode Mitra Supplier', 'prefix' => 'SPL', 'format' => '{PREFIX}-{SEQ}', 'padding' => 3, 'reset_frequency' => 'never'],
        ];

        foreach ($sequences as $seq) {
            DB::table('document_sequences')->updateOrInsert(
                ['document_type' => $seq['document_type']],
                array_merge($seq, [
                    'current_sequence' => 0,
                    'last_reset_date' => now()->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 5. Seed Payment Methods
        $paymentMethods = [
            ['code' => 'cash', 'name' => 'Tunai (Cash)', 'type' => 'cash', 'account_id' => $cashAcc?->id, 'is_active' => true, 'requires_reference' => false, 'instructions' => 'Pembayaran tunai langsung di kasir.', 'sort_order' => 1],
            ['code' => 'bank_transfer', 'name' => 'Transfer Bank (BCA / Mandiri / BRI)', 'type' => 'bank', 'account_id' => $bankAcc?->id, 'is_active' => true, 'requires_reference' => true, 'instructions' => 'Kirim bukti transfer dengan mencantumkan nomor referensi bank.', 'sort_order' => 2],
            ['code' => 'qris', 'name' => 'QRIS (GoPay / OVO / Dana / ShopeePay)', 'type' => 'digital', 'account_id' => $bankAcc?->id, 'is_active' => true, 'requires_reference' => true, 'instructions' => 'Scan QRIS toko dan verifikasi status pembayaran di layar kasir.', 'sort_order' => 3],
            ['code' => 'tempo', 'name' => 'Tempo / Utang Supplier', 'type' => 'debt', 'account_id' => null, 'is_active' => true, 'requires_reference' => false, 'instructions' => 'Pembayaran berkala dengan batas waktu jatuh tempo.', 'sort_order' => 4],
        ];

        foreach ($paymentMethods as $pm) {
            DB::table('payment_methods')->updateOrInsert(
                ['code' => $pm['code']],
                array_merge($pm, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // 6. Seed Accounting Periods (2026 Monthly Periods)
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        foreach ($months as $m => $name) {
            $startDate = sprintf('2026-%02d-01', $m);
            $endDate = date('Y-m-t', strtotime($startDate));

            DB::table('accounting_periods')->updateOrInsert(
                ['fiscal_year' => 2026, 'period_name' => "Periode {$name} 2026"],
                [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_closed' => false,
                    'is_locked' => false,
                    'notes' => "Periode akuntansi resmi bulan {$name} 2026",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

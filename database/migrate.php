<?php
/**
 * =====================================================================
 * FinToko — Database Migration & Schema Setup CLI Script
 * =====================================================================
 * Script ini dijalankan melalui command-line (CLI) atau saat startup container.
 * TIDAK DIJALANKAN pada setiap HTTP request web.
 *
 * Penggunaan CLI:
 *   php database/migrate.php
 * =====================================================================
 */

if (php_sapi_name() !== 'cli' && (!isset($_GET['token']) || !hash_equals((string)getenv('SETUP_TOKEN'), (string)$_GET['token']))) {
    http_response_code(403);
    die("Script migrasi hanya dapat dijalankan melalui CLI terminal atau dengan SETUP_TOKEN yang valid.\n");
}

echo "=== FinToko Database Migration ===\n";

require_once __DIR__ . '/../config/koneksi.php';

// Pastikan skema tabel tersedia
$sql_file = __DIR__ . '/schema.sql';
if (file_exists($sql_file)) {
    echo "Membaca skema dari database/schema.sql...\n";
    $sql = file_get_contents($sql_file);

    // Eksekusi multi query
    if ($koneksi->multi_query($sql)) {
        do {
            if ($result = $koneksi->store_result()) {
                $result->free();
            }
        } while ($koneksi->more_results() && $koneksi->next_result());
        echo "[OK] Skema tabel dan data awal berhasil dieksekusi.\n";
    } else {
        echo "[ERROR] Gagal mengeksekusi schema.sql: " . $koneksi->error . "\n";
    }
}

// Validasi kolom tabel users
$check_users = $koneksi->query("SHOW TABLES LIKE 'users'");
if ($check_users && $check_users->num_rows > 0) {
    $c_email = $koneksi->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($c_email && $c_email->num_rows === 0) {
        $koneksi->query("ALTER TABLE users ADD COLUMN email VARCHAR(100) NULL UNIQUE AFTER username");
        echo "[OK] Menambahkan kolom 'email' pada tabel users.\n";
    }
    $c_role = $koneksi->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($c_role && $c_role->num_rows === 0) {
        $koneksi->query("ALTER TABLE users ADD COLUMN role ENUM('admin', 'customer') NOT NULL DEFAULT 'admin' AFTER nama_lengkap");
        echo "[OK] Menambahkan kolom 'role' pada tabel users.\n";
    }
}

// Validasi kolom transaksi
$check_trx = $koneksi->query("SHOW TABLES LIKE 'transaksi'");
if ($check_trx && $check_trx->num_rows > 0) {
    $c_metode = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'metode'");
    if ($c_metode && $c_metode->num_rows === 0) {
        $koneksi->query("ALTER TABLE transaksi ADD COLUMN metode ENUM('Tunai','Transfer','QRIS','Debit') NOT NULL DEFAULT 'Tunai' AFTER nominal");
        echo "[OK] Menambahkan kolom 'metode' pada tabel transaksi.\n";
    }
    $c_oleh = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'input_oleh'");
    if ($c_oleh && $c_oleh->num_rows === 0) {
        $koneksi->query("ALTER TABLE transaksi ADD COLUMN input_oleh VARCHAR(100) NOT NULL DEFAULT 'System' AFTER metode");
        echo "[OK] Menambahkan kolom 'input_oleh' pada tabel transaksi.\n";
    }
    $c_bukti = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'bukti_file'");
    if ($c_bukti && $c_bukti->num_rows === 0) {
        $koneksi->query("ALTER TABLE transaksi ADD COLUMN bukti_file VARCHAR(255) DEFAULT NULL AFTER input_oleh");
        echo "[OK] Menambahkan kolom 'bukti_file' pada tabel transaksi.\n";
    }
}

echo "=== Migrasi Selesai ===\n";

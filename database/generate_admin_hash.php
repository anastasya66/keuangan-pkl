<?php
/**
 * ============================================================
 * GENERATE & TULIS seed_admin.sql OTOMATIS - FinToko
 * ============================================================
 * Jalankan via terminal Laragon:
 *   php database/generate_admin_hash.php
 *
 * Script ini akan:
 * 1. Generate hash bcrypt yang valid untuk password Admin1234
 * 2. Menulis ulang file seed_admin.sql dengan hash yang benar
 * 3. Mencetak petunjuk selanjutnya
 * ============================================================
 */

$password = 'Admin1234';
$hash     = password_hash($password, PASSWORD_BCRYPT);

// Verifikasi
if (!password_verify($password, $hash)) {
    echo "❌ GAGAL: Hash tidak valid. Coba jalankan ulang.\n";
    exit(1);
}

// Tulis file seed_admin.sql dengan hash yang benar
$sql = <<<SQL
-- ============================================================
-- SEED AKUN ADMIN DEFAULT - FinToko
-- ============================================================
-- Script ini menyisipkan atau me-reset akun administrator default.
-- Jalankan di HeidiSQL: File > Run SQL File... > pilih file ini
--
-- Kredensial login setelah script ini dijalankan:
--   Username : admin
--   Email    : admin@fintoko.com
--   Password : Admin1234
--   Role     : admin
-- ============================================================

USE keuangan;

INSERT INTO users (username, email, password_hash, nama_lengkap, role)
VALUES (
    'admin',
    'admin@fintoko.com',
    '{$hash}',
    'Administrator FinToko',
    'admin'
)
ON DUPLICATE KEY UPDATE
    password_hash = '{$hash}',
    nama_lengkap  = 'Administrator FinToko',
    role          = 'admin';

-- Verifikasi hasil (password_hash tidak ditampilkan untuk keamanan)
SELECT id, username, email, nama_lengkap, role, created_at
FROM users;
SQL;

$sqlFile = __DIR__ . '/seed_admin.sql';
file_put_contents($sqlFile, $sql);

echo "============================================================\n";
echo "  ✅ File seed_admin.sql berhasil dibuat/diperbarui!\n";
echo "============================================================\n\n";
echo "  Password : {$password}\n";
echo "  Hash     : {$hash}\n\n";
echo "  Langkah selanjutnya:\n";
echo "  1. Buka HeidiSQL\n";
echo "  2. Pilih menu: File > Run SQL File\n";
echo "  3. Pilih file: database/seed_admin.sql\n";
echo "  4. Klik Execute / Run\n";
echo "  5. Coba login dengan username: admin, password: Admin1234\n\n";

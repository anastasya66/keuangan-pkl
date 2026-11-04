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
    '$2y$10$OzBTpymgjcvYyaDyf2O.D.BEhx/uMhfjMIF/HiPEsdSI41WIHqx1m',
    'Administrator FinToko',
    'admin'
)
ON DUPLICATE KEY UPDATE
    password_hash = '$2y$10$OzBTpymgjcvYyaDyf2O.D.BEhx/uMhfjMIF/HiPEsdSI41WIHqx1m',
    nama_lengkap  = 'Administrator FinToko',
    role          = 'admin';

-- Verifikasi hasil (password_hash tidak ditampilkan untuk keamanan)
SELECT id, username, email, nama_lengkap, role, created_at
FROM users;
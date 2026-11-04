-- =============================================================
-- MIGRASI DATABASE: Tambah Kolom Baru untuk Fitur Profesional Kas
-- FinToko — Sistem Informasi Keuangan
-- =============================================================
-- PRASYARAT : Database 'keuangan' dan tabel 'transaksi' sudah ada.
-- INSTRUKSI : Jalankan SEKALI SAJA di phpMyAdmin atau MySQL CLI.
--             Aman dijalankan ulang (IF NOT EXISTS).
-- =============================================================

USE keuangan;

-- ─────────────────────────────────────────────────────────────
-- 1. [FITUR 3] Metode Pembayaran (Tunai / Transfer / QRIS / Debit)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE transaksi
    ADD COLUMN IF NOT EXISTS metode
        ENUM('Tunai','Transfer','QRIS','Debit') NOT NULL DEFAULT 'Tunai'
    AFTER nominal;

-- ─────────────────────────────────────────────────────────────
-- 2. [FITUR 6] Nama staf / kasir yang menginput (User Tracker)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE transaksi
    ADD COLUMN IF NOT EXISTS input_oleh
        VARCHAR(100) NOT NULL DEFAULT 'System'
    AFTER metode;

-- ─────────────────────────────────────────────────────────────
-- 3. [FITUR 7] File bukti transaksi / foto struk (path relatif)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE transaksi
    ADD COLUMN IF NOT EXISTS bukti_file
        VARCHAR(255) DEFAULT NULL
    AFTER input_oleh;

-- ─────────────────────────────────────────────────────────────
-- 4. [FITUR 1] Index tambahan untuk pencarian keterangan
-- ─────────────────────────────────────────────────────────────
ALTER TABLE transaksi
    ADD INDEX IF NOT EXISTS idx_keterangan (keterangan(50));

-- ─────────────────────────────────────────────────────────────
-- 5. Index untuk metode pembayaran (performa filter)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE transaksi
    ADD INDEX IF NOT EXISTS idx_metode (metode);

-- =============================================================
-- VERIFIKASI: Tampilkan struktur kolom tabel transaksi
-- =============================================================
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_TYPE
FROM
    INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = 'keuangan'
    AND TABLE_NAME = 'transaksi'
ORDER BY ORDINAL_POSITION;

-- =============================================================
-- SKEMA DATABASE - SISTEM INFORMASI KEUANGAN & STOK FinToko
-- "Implementasi Sistem Informasi Keuangan Berbasis Web Menggunakan
--  Infrastruktur Container Docker dengan Skalabilitas Tinggi pada FinToko"
-- =============================================================

CREATE DATABASE IF NOT EXISTS keuangan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE keuangan;

-- -------------------------------------------------------------
-- 1. TABEL USERS (Autentikasi Multi-User Dinamis)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    nama_lengkap   VARCHAR(100) NOT NULL,
    role           ENUM('admin', 'customer') NOT NULL DEFAULT 'admin',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- -------------------------------------------------------------
-- 2. TABEL TRANSAKSI KEUANGAN (Kas Masuk & Kas Keluar)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transaksi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tanggal     DATE NOT NULL,
    jenis       ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
    kategori    VARCHAR(100) NOT NULL DEFAULT 'Umum',
    keterangan  VARCHAR(255) NOT NULL DEFAULT '',
    nominal     DECIMAL(15,2) NOT NULL,
    metode      ENUM('Tunai','Transfer','QRIS','Debit') NOT NULL DEFAULT 'Tunai',
    input_oleh  VARCHAR(100) NOT NULL DEFAULT 'System',
    bukti_file  VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_jenis (jenis),
    INDEX idx_tanggal (tanggal),
    INDEX idx_metode (metode),
    INDEX idx_keterangan (keterangan(50))
) ENGINE = InnoDB;

-- -------------------------------------------------------------
-- 3. TABEL PRODUK / STOK BARANG (Inventory FinToko)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS produk (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    kode_produk   VARCHAR(50)  NOT NULL UNIQUE,
    nama_produk   VARCHAR(150) NOT NULL,
    kategori      VARCHAR(100) NOT NULL DEFAULT 'Umum',
    satuan        VARCHAR(30)  NOT NULL DEFAULT 'Pcs',
    harga_beli    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    harga_jual    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    stok          INT NOT NULL DEFAULT 0,
    stok_minimum  INT NOT NULL DEFAULT 5,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kategori (kategori),
    INDEX idx_kode (kode_produk)
) ENGINE = InnoDB;

-- -------------------------------------------------------------
-- 4. TABEL RIWAYAT MUTASI STOK
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS riwayat_stok (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    produk_id     INT NOT NULL,
    tanggal       DATE NOT NULL,
    jenis         ENUM('Masuk', 'Keluar') NOT NULL,
    jumlah        INT NOT NULL,
    harga_satuan  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_biaya   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    keterangan    VARCHAR(255) NOT NULL DEFAULT '',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE,
    INDEX idx_mutasi_tgl (tanggal)
) ENGINE = InnoDB;

-- -------------------------------------------------------------
-- 5. TABEL LOGIN ATTEMPTS (Rate Limiting)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    identifier   VARCHAR(255) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier),
    INDEX idx_attempted_at (attempted_at)
) ENGINE = InnoDB;

-- -------------------------------------------------------------
-- SEED DATA AWAL (Produk & Transaksi Sampel — TANPA akun user)
-- -------------------------------------------------------------
-- ⛔ SECURITY: Tidak ada seed akun default.
-- Gunakan setup_admin.php (dengan SETUP_TOKEN) untuk membuat akun pertama.

-- Produk Awal FinToko
INSERT INTO produk (kode_produk, nama_produk, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum) VALUES
('PRD-001', 'Kertas HVS A4 70gr PaperOne', 'Alat Tulis Kantor', 'Rim', 42000, 52000, 45, 10),
('PRD-002', 'Tinta Printer Epson 003 Hitam', 'Aksesoris Komputer', 'Botol', 75000, 95000, 20, 5),
('PRD-003', 'Flashdisk Sandisk 32GB USB 3.0', 'Elektronik', 'Unit', 55000, 75000, 18, 5),
('PRD-004', 'Buku Kas Folio 100 Lembar', 'Alat Tulis Kantor', 'Buku', 22000, 30000, 30, 8),
('PRD-005', 'Kalkulator Digital Casio MJ-120D', 'Elektronik', 'Unit', 85000, 115000, 12, 4),
('PRD-006', 'Mouse Wireless Logitech M170', 'Aksesoris Komputer', 'Unit', 95000, 135000, 8, 5)
ON DUPLICATE KEY UPDATE id=id;

-- Transaksi Keuangan Awal
INSERT INTO transaksi (tanggal, jenis, kategori, keterangan, nominal) VALUES
(CURDATE() - INTERVAL 25 DAY, 'Pemasukan',   'Penjualan Produk', 'Penjualan Kertas HVS & Flashdisk', 3500000),
(CURDATE() - INTERVAL 22 DAY, 'Pengeluaran', 'Listrik & Air',    'Tagihan listrik toko bulan ini', 450000),
(CURDATE() - INTERVAL 18 DAY, 'Pemasukan',   'Penjualan Produk', 'Penjualan Aksesoris Komputer', 1200000),
(CURDATE() - INTERVAL 14 DAY, 'Pengeluaran', 'Gaji Karyawan',    'Gaji staf toko paruh bulan', 2500000),
(CURDATE() - INTERVAL 9  DAY, 'Pemasukan',   'Penjualan Produk', 'Penjualan Grosir ATK FinToko', 4100000),
(CURDATE() - INTERVAL 5  DAY, 'Pengeluaran', 'Transportasi',     'Biaya ekspedisi stok masuk', 300000),
(CURDATE() - INTERVAL 2  DAY, 'Pemasukan',   'Pemasukan Lain-lain', 'Pendapatan jasa print & scan', 150000)
ON DUPLICATE KEY UPDATE id=id;

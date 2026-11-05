# FinToko — Financial & Inventory Management System

Aplikasi modern pengelolaan transaksi keuangan, master data produk, dan persediaan (*inventory*) toko. Dibangun secara terpadu (**Unified Modern Monolith SPA**) menggunakan **Laravel 12 (PHP 8.2)**, **React 19**, **Tailwind CSS v4**, dan **MariaDB**.

---

## 1. Perkembangan Proyek

- **Tahap 1 — Project Foundation**: ✅ SELESAI (Konfigurasi Laravel 12, MariaDB `fintoko_db`, React 19 + Vite, Tailwind CSS v4, Sanctum API prefix `/api/v1`).
- **Tahap 2 — Authentication & User Management**: ✅ SELESAI (Sistem login/logout Sanctum, proteksi status akun `is_active`, Laravel Policies, Admin User Management, Profile & Password management, Protected Routes React, Admin & Staff Layouts).
- **Tahap 3 — Master Data**: ✅ SELESAI (Modul Kategori Produk, Satuan Produk, Master Produk, dan User Management terintegrasi penuh backend REST API & frontend React dengan pencarian, filter, pagination, dan otorisasi role).
- **Tahap 4 — Financial Management**: ✅ SELESAI (Modul Transaksi Keuangan Tunggal untuk Pemasukan & Pengeluaran, Kategori Transaksi, Validasi Kepemilikan & Integritas, Mekanisme Pembatalan Transaksi, Perhitungan Saldo Aktif Real-time).
- **Tahap 5 — Inventory Management**: ✅ SELESAI (Modul Persediaan Stok: Stok Masuk, Stok Keluar, Penyesuaian Stok Opname, Riwayat Mutasi Stok dengan kode unik `MOV-YYYYMMDD-XXXX`, Concurrency Protection `lockForUpdate()`).
- **Tahap 6 — Accounting (Double-Entry Bookkeeping)**: ✅ SELESAI (Chart of Accounts, Jurnal Umum Draft/Posted/Reversal, Buku Besar Running Balance, Neraca Saldo Otomatis, Integrasi Transaksi Keuangan & Persediaan, 96 Automated Tests lulus 100%).

---

## 2. Technology Stack

### Backend
- **Framework**: Laravel 12
- **Bahasa**: PHP 8.2+
- **Database**: MariaDB 10.4+ / MySQL 8+
- **Autentikasi**: Laravel Sanctum (Personal Access Token / Bearer Token)
- **Otorisasi**: Laravel Policies (`InventoryMovementPolicy`, `FinancialTransactionPolicy`, `TransactionCategoryPolicy`, `ProductPolicy`, `CategoryPolicy`, `UnitPolicy`, `UserPolicy`) & Custom Middleware (`active`, `role`)
- **Penghapusan & Integritas**: Soft Deletes (`deleted_at`), Referential Integrity (`restrictOnDelete`), Row-level Locking (`lockForUpdate()`), Database Transactions (`DB::transaction()`)
- **API Spec**: RESTful JSON responses (`/api/v1/*` dan direct alias `/api/*`)

### Frontend
- **Framework**: React 19 (SPA)
- **Bundler**: Vite 7 via `laravel-vite-plugin`
- **Styling**: Tailwind CSS v4
- **State Autentikasi**: React Context (`AuthContext` & `useAuth`)
- **HTTP Client**: Axios dengan Token Interceptors (`resources/js/services/api.js`)
- **Routing & Proteksi**: React Router DOM v7 (`GuestRoute`, `ProtectedRoute`)
- **Komponen Reusable**: `DataTable`, `SearchInput`, `Pagination`, `StatusBadge`, `ConfirmDialog`

---

## 3. Database Design & Relasi Tabel

### A. Tabel `products` (Penambahan Kolom Stok)
Menyimpan informasi master produk toko beserta kuantitas stok terkini (*Single Source of Truth*).
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `stock` | DECIMAL(12,2) | Kuantitas stok produk terkini (default: 0.00, tidak boleh negatif) |

### B. Tabel `inventory_movements`
Menyimpan catatan permanen (*audit trail*) setiap perubahan kuantitas stok produk.
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment primary key |
| `movement_code` | VARCHAR(50) | Kode unik mutasi (cth: `MOV-20261105-0001`, unik) |
| `product_id` | BIGINT (FK) | Relasi ke `products.id` (`restrictOnDelete`) |
| `movement_type` | VARCHAR(20) | Jenis mutasi: `stock_in`, `stock_out`, `adjustment_in`, `adjustment_out` |
| `quantity` | DECIMAL(12,2)| Jumlah kuantitas barang yang bermutasi (wajib > 0) |
| `stock_before` | DECIMAL(12,2)| Kuantitas stok sebelum mutasi |
| `stock_after` | DECIMAL(12,2)| Kuantitas stok sesudah mutasi |
| `adjustment_reason` | TEXT | Alasan penyesuaian fisik (wajib jika adjustment) |
| `description` | TEXT | Keterangan mutasi (opsional) |
| `reference_number` | VARCHAR(100)| Nomor dokumen/PO/Invoice referensi |
| `movement_date` | DATE | Tanggal mutasi terjadi |
| `created_by` | BIGINT (FK) | User pencatat mutasi (`users.id`, dari `auth()->id()`) |
| `created_at` | TIMESTAMP | Waktu mutasi dicatat |
| `updated_at` | TIMESTAMP | Waktu pembaruan |

### Relasi Antar Tabel
```text
users (id) ─────────────────< (created_by) ──── inventory_movements
                                                     │ (product_id)
products (id) ──────────────< (hasMany) ─────────────┘
  │ (category_id)               │ (unit_id)
  ▼                             ▼
categories (id)               units (id)
```

---

## 4. Otorisasi Berdasarkan Role

| Fitur / Modul | Role Admin | Role Staff |
| :--- | :---: | :---: |
| **Lihat Stok & Ringkasan**: `/api/inventory` | ✅ Semua produk | ✅ Produk aktif |
| **Detail Stok Produk**: `/api/inventory/{id}` | ✅ Ya | ✅ Ya |
| **Stok Masuk (Stock In)**: Catat penerimaan barang | ✅ Ya | ✅ Ya |
| **Stok Keluar (Stock Out)**: Catat pengeluaran barang | ✅ Ya | ✅ Ya |
| **Penyesuaian Stok (Adjustment)**: Stock opname fisik | ✅ Ya | ❌ Ditolak (403 Forbidden - Khusus Admin) |
| **Riwayat Mutasi**: Lihat seluruh mutasi stok | ✅ Semua | ✅ Semua |

---

## 5. Daftar Lengkap REST API Endpoints (Inventory)

Semua endpoint dapat diakses melalui prefix `/api/v1/*` maupun direct alias `/api/*`. Wajib menyertakan header:
`Authorization: Bearer <TOKEN>` dan `Accept: application/json`.

| Method | Endpoint | Query Param / Body | Keterangan | Akses |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/inventory` | `search`, `category_id`, `unit_id`, `low_stock`, `out_of_stock`, `page` | Ringkasan stok seluruh produk | Admin & Staff |
| `GET` | `/api/inventory/{productId}` | - | Detail stok produk & 15 mutasi terakhir | Admin & Staff |
| `GET` | `/api/inventory/stock-in` | `search`, `product_id`, `start_date`, `end_date`, `page` | Daftar riwayat stok masuk | Admin & Staff |
| `POST` | `/api/inventory/stock-in` | Body: `product_id`, `quantity`, `movement_date`, `description`, `reference_number` | Tambah stok masuk produk | Admin & Staff |
| `GET` | `/api/inventory/stock-out` | `search`, `product_id`, `start_date`, `end_date`, `page` | Daftar riwayat stok keluar | Admin & Staff |
| `POST` | `/api/inventory/stock-out` | Body: `product_id`, `quantity`, `movement_date`, `description`, `reference_number` | Tambah stok keluar produk | Admin & Staff |
| `GET` | `/api/inventory/adjustments` | `search`, `product_id`, `start_date`, `end_date`, `page` | Daftar riwayat penyesuaian | Admin & Staff |
| `POST` | `/api/inventory/adjustments` | Body: `product_id`, `actual_stock`, `adjustment_reason`, `movement_date`, `description` | Catat penyesuaian stok fisik | Admin Only |
| `GET` | `/api/inventory/movements` | `search`, `product_id`, `movement_type`, `created_by`, `start_date`, `end_date`, `page` | Seluruh riwayat mutasi stok | Admin & Staff |
| `GET` | `/api/inventory/movements/{id}` | - | Detail satu mutasi stok | Admin & Staff |

---

## 6. Contoh Request & Response JSON

### A. Tambah Stok Masuk (Stock In)
`POST /api/inventory/stock-in`

**Body Request:**
```json
{
  "product_id": 1,
  "quantity": 15,
  "movement_date": "2026-11-05",
  "description": "Penerimaan pasokan beras dari supplier",
  "reference_number": "PO-202611-001"
}
```

**Response Sukses (HTTP 201 Created):**
```json
{
  "success": true,
  "message": "Stok berhasil ditambahkan",
  "data": {
    "id": 8,
    "movement_code": "MOV-20261105-0002",
    "product_id": 1,
    "movement_type": "stock_in",
    "quantity": "15.00",
    "quantity_numeric": 15,
    "stock_before": "50.00",
    "stock_before_numeric": 50,
    "stock_after": "65.00",
    "stock_after_numeric": 65,
    "adjustment_reason": null,
    "description": "Penerimaan pasokan beras dari supplier",
    "reference_number": "PO-202611-001",
    "movement_date": "2026-11-05",
    "product": {
      "id": 1,
      "sku": "PRD-SMB-001",
      "name": "Beras Pandan Wangi Premium 5kg",
      "current_stock": 65,
      "unit": {
        "id": 2,
        "name": "Kilogram",
        "symbol": "kg"
      }
    },
    "created_by": {
      "id": 2,
      "name": "Staff Toko",
      "email": "staff@fintoko.test",
      "role": "staff"
    },
    "created_at": "2026-11-05T06:24:00.000000Z",
    "updated_at": "2026-11-05T06:24:00.000000Z"
  }
}
```

### B. Penyesuaian Stok Opname Fisik (Stock Adjustment)
`POST /api/inventory/adjustments`

**Body Request:**
```json
{
  "product_id": 3,
  "actual_stock": 38,
  "adjustment_reason": "2 bungkus kemasan rusak saat bongkar muat gudang",
  "movement_date": "2026-11-05"
}
```

**Response Sukses (HTTP 201 Created):**
```json
{
  "success": true,
  "message": "Penyesuaian stok berhasil disimpan",
  "data": {
    "id": 9,
    "movement_code": "MOV-20261105-0003",
    "product_id": 3,
    "movement_type": "adjustment_out",
    "quantity": "2.00",
    "quantity_numeric": 2,
    "stock_before": "40.00",
    "stock_after": "38.00",
    "adjustment_reason": "2 bungkus kemasan rusak saat bongkar muat gudang"
  }
}
```

---

## 7. Modul Accounting (Tahap 6 — Double-Entry Bookkeeping)

Modul akuntansi mencakup pencatatan keuangan berpasangan (*double-entry bookkeeping*):
- **Chart of Accounts (COA)**: Standar akun Assets, Liabilities, Equity, Revenue, dan Expenses dengan penentuan saldo normal otomatis. Akun yang telah memiliki jurnal diproteksi dari penghapusan (*restrictOnDelete*).
- **Jurnal Umum (General Journal)**: Siklus jurnal `draft` -> `posted` -> `reversed`. Nomor jurnal unik `JRN-YYYYMMDD-XXXX`, validasi total debit == total kredit, minimal dua baris, akun aktif, dan nilai positif.
- **Buku Besar (General Ledger)**: Menghitung saldo awal sebelum periode, mutasi debit/kredit posted/reversed, dan saldo berjalan (*running balance*).
- **Neraca Saldo (Trial Balance)**: Mengakumulasi saldo debit dan kredit seluruh akun per tanggal, memverifikasi status keseimbangan (*equilibrium*).
- **Integrasi Otomatis**: Integrasi transaksi keuangan pemasukan/pengeluaran dan mutasi persediaan opname fisik atau pembelian stok. Reversal otomatis jika transaksi sumber dibatalkan.

### Daftar Endpoint API Accounting
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET, POST` | `/api/v1/chart-of-accounts` | Admin & Staff (POST Admin) | Daftar & Tambah Akun COA |
| `GET, PUT, DELETE`| `/api/v1/chart-of-accounts/{id}` | Admin & Staff (PUT/DELETE Admin) | Detail, Edit, Hapus Akun COA |
| `PATCH` | `/api/v1/chart-of-accounts/{id}/status` | Admin | Aktifkan/Nonaktifkan Akun COA |
| `GET, POST` | `/api/v1/journals` | Admin & Staff | Daftar Jurnal & Buat Jurnal Draft |
| `GET, PUT, DELETE`| `/api/v1/journals/{id}` | Admin & Staff (PUT/DELETE Draft) | Detail, Edit, Hapus Jurnal Draft |
| `POST` | `/api/v1/journals/{id}/post` | **Admin Only** | Posting Jurnal ke Buku Besar |
| `POST` | `/api/v1/journals/{id}/reverse` | **Admin Only** | Reversal Jurnal Posted |
| `GET` | `/api/v1/general-ledger` | Admin & Staff | Buku Besar Akun Default / Query |
| `GET` | `/api/v1/general-ledger/{accountId}` | Admin & Staff | Buku Besar Akun Spesifik |
| `GET` | `/api/v1/trial-balance` | Admin & Staff | Laporan Neraca Saldo |
| `POST` | `/api/v1/accounting/sync-transaction/{id}`| Admin & Staff | Sinkronisasi Jurnal Transaksi Keuangan |
| `POST` | `/api/v1/accounting/sync-inventory/{id}` | Admin & Staff | Sinkronisasi Jurnal Mutasi Persediaan |
| `GET` | `/api/v1/accounting/mappings` | Admin & Staff | Daftar Pemetaan Akun Otomatis |

---

## 8. Panduan Setup Awal di Laptop Baru (Laragon)

Jika aplikasi baru saja di-clone/download dari Git, folder `vendor/` dan `node_modules/` belum ada karena diabaikan oleh Git.

### Cara Cepat (Otomatis):
Klik dua kali file **`setup.bat`** di folder project, atau jalankan melalui Terminal:
```cmd
setup.bat
```

### Cara Manual Langkah-demi-Langkah:
1. **Buka Terminal Laragon**, arahkan ke direktori project:
   ```bash
   cd E:\laragon\www\keuangan-pkl
   ```
2. **Unduh dependensi PHP (Composer)** *(Mencegah error `vendor/autoload.php not found`)*:
   ```bash
   composer install
   ```
3. **Buat file konfigurasi `.env`**:
   ```bash
   copy .env.example .env
   ```
4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```
5. **Jalankan Migrasi & Data Seeder** *(Pastikan MariaDB di Laragon sudah klik "Start All")*:
   ```bash
   php artisan migrate --seed
   ```
6. **Unduh dependensi & Kompilasi Frontend (React 19 + Vite)**:
   ```bash
   npm install
   npm run build
   ```
7. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Akses di browser: **`http://127.0.0.1:8000`**

### Menjalankan Automated Testing (112 Tests Passing)
```bash
php artisan test
```
Untuk menguji modul Accounting secara khusus:
```bash
php artisan test --filter=AccountingModuleTest
```

---

## 9. Menjalankan Aplikasi Menggunakan Docker & Docker Compose

FinToko mendukung eksekusi secara langsung melalui **Laragon** (development lokal) maupun **Docker & Docker Compose** secara berdampingan tanpa konflik port.

### Arsitektur Docker:
- **`app`**: PHP 8.2-FPM Alpine dengan ekstensi lengkap (`pdo_mysql`, `gd`, `bcmath`, `zip`, `intl`, `opcache`), Composer 2, dan multi-stage build frontend asset.
- **`nginx`**: Web server Nginx Alpine sebagai reverse proxy ke PHP-FPM dan penyaji static asset di port **8080**.
- **`mariadb`**: Database MariaDB 10.11 LTS terisolasi dengan persistent volume `fintoko_mariadb_data` di port **3307** (mencegah bentrok dengan port 3306 Laragon).

### Panduan Menjalankan Docker:

1. **Gunakan File `.env` yang Sama (Terpadu)**:
   ```bash
   cp .env.example .env
   ```
   *(Cukup 1 file `.env` untuk Laragon maupun Docker! Port dan host Docker terkonfigurasi otomatis).*

2. **Jalankan Container & Build Otomatis**:
   ```bash
   docker compose up -d --build
   ```

3. **Akses Aplikasi FinToko**:
   Buka peramban di: **`http://localhost:8080`**

4. **Perintah Docker Berguna**:
   - Menjalankan migrasi manual:
     ```bash
     docker compose exec app php artisan migrate
     ```
   - Menjalankan data seeder:
     ```bash
     docker compose exec app php artisan db:seed
     ```
   - Menjalankan test suite di dalam container:
     ```bash
     docker compose exec app php artisan test
     ```
   - Melihat log container secara real-time:
     ```bash
     docker compose logs -f app
     ```
   - Menghentikan container:
     ```bash
     docker compose down
     ```


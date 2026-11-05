# FinToko — Financial Management System

Aplikasi modern pengelolaan transaksi, master data, dan pencatatan keuangan toko. Dibangun secara terpadu (**Unified Modern Monolith SPA**) menggunakan **Laravel 12 (PHP 8.2)**, **React 19**, **Tailwind CSS v4**, dan **MariaDB**.

---

## 1. Perkembangan Proyek

- **Tahap 1 — Project Foundation**: ✅ SELESAI (Konfigurasi Laravel 12, MariaDB `fintoko_db`, React 19 + Vite, Tailwind CSS v4, Sanctum API prefix `/api/v1`).
- **Tahap 2 — Authentication & User Management**: ✅ SELESAI (Sistem login/logout Sanctum, proteksi status akun `is_active`, Laravel Policies, Admin User Management, Profile & Password management, Protected Routes React, Admin & Staff Layouts).
- **Tahap 3 — Master Data**: ✅ SELESAI (Modul Kategori Produk, Satuan Produk, Master Produk, dan User Management terintegrasi penuh backend REST API & frontend React dengan pencarian, filter, pagination, dan otorisasi role).
- **Tahap 4 — Financial Management**: ✅ SELESAI (Modul Transaksi Keuangan Tunggal untuk Pemasukan & Pengeluaran, Kategori Transaksi, Validasi Kepemilikan & Integritas, Mekanisme Pembatalan Transaksi, Perhitungan Saldo Aktif Real-time, dan 68 Automated Tests lulus 100%).

---

## 2. Technology Stack

### Backend
- **Framework**: Laravel 12
- **Bahasa**: PHP 8.2+
- **Database**: MariaDB 10.4+ / MySQL 8+
- **Autentikasi**: Laravel Sanctum (Personal Access Token / Bearer Token)
- **Otorisasi**: Laravel Policies (`FinancialTransactionPolicy`, `TransactionCategoryPolicy`, `ProductPolicy`, `CategoryPolicy`, `UnitPolicy`, `UserPolicy`) & Custom Middleware (`active`, `role`)
- **Penghapusan & Integritas**: Soft Deletes (`deleted_at`), Referential Integrity Protection (`restrictOnDelete`), Database Transactions
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

### A. Tabel `transaction_categories`
Menyimpan kelompok kategori pencatatan keuangan untuk pemasukan dan pengeluaran.
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment primary key |
| `name` | VARCHAR(100) | Nama kategori (wajib, unik per `type`) |
| `type` | VARCHAR(20) | Jenis kategori: `income` atau `expense` |
| `description` | TEXT | Keterangan kelompok transaksi (opsional) |
| `is_active` | BOOLEAN | Status aktif kategori (default: true) |
| `created_at` | TIMESTAMP | Waktu pembuatan |
| `updated_at` | TIMESTAMP | Waktu pembaruan terakhir |
| `deleted_at` | TIMESTAMP | Waktu soft delete |

### B. Tabel `financial_transactions`
Menyimpan seluruh transaksi pemasukan (*income*) dan pengeluaran (*expense*) dalam satu tabel terpadu.
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment primary key |
| `transaction_code` | VARCHAR(50) | Kode unik transaksi (cth: `TRX-20261105-0001`, unik) |
| `transaction_type` | VARCHAR(20) | Jenis transaksi: `income` atau `expense` |
| `category_id` | BIGINT (FK) | Relasi ke `transaction_categories.id` (`restrictOnDelete`) |
| `amount` | DECIMAL(15,2)| Nominal transaksi (wajib > 0) |
| `transaction_date` | DATE | Tanggal transaksi toko |
| `description` | TEXT | Keterangan transaksi (opsional) |
| `created_by` | BIGINT (FK) | ID user pencatat transaksi (dari `auth()->id()`) |
| `updated_by` | BIGINT (FK) | ID user pengubah transaksi terakhir |
| `status` | VARCHAR(20) | Status transaksi: `completed` (default) atau `cancelled` |
| `cancelled_at` | TIMESTAMP | Waktu transaksi dibatalkan |
| `cancelled_by` | BIGINT (FK) | ID user yang membatalkan transaksi |
| `cancellation_reason` | TEXT | Alasan pembatalan transaksi |
| `created_at` | TIMESTAMP | Waktu pembuatan |
| `updated_at` | TIMESTAMP | Waktu pembaruan terakhir |
| `deleted_at` | TIMESTAMP | Waktu soft delete |

### C. Tabel Master Data Lainnya
- `categories`: Kategori barang/produk toko.
- `units`: Satuan ukuran produk toko.
- `products`: Katalog produk toko dengan harga beli & jual.
- `users`: Data pengguna (admin & staff) dengan hash kata sandi.

### Relasi Antar Tabel
```text
users (1) ────────────────< (hasMany) ──── financial_transactions (N)
                                                 │ (belongsTo)
transaction_categories (1) ──< (hasMany) ────────┘
```
- Transaksi `income` hanya boleh memilih kategori transaksi berjenis `income`.
- Transaksi `expense` hanya boleh memilih kategori transaksi berjenis `expense`.
- Kategori transaksi berstatus nonaktif ditolak untuk pembuatan transaksi baru.
- Kategori transaksi yang masih memiliki riwayat transaksi dilarang dihapus.

---

## 4. Otorisasi Berdasarkan Role

| Fitur / Modul | Role Admin | Role Staff |
| :--- | :---: | :---: |
| **Kategori Transaksi**: Lihat daftar | ✅ Semua | ✅ Hanya yang Aktif |
| **Kategori Transaksi**: Tambah / Ubah / Hapus | ✅ Ya | ❌ Ditolak (403 Forbidden) |
| **Transaksi Keuangan**: Lihat semua transaksi | ✅ Semua | ✅ Semua |
| **Transaksi Keuangan**: Tambah transaksi baru | ✅ Ya | ✅ Ya (Otomatis tercatat sebagai pembuat) |
| **Transaksi Keuangan**: Ubah transaksi | ✅ Ya (Kecuali cancelled) | ✅ Hanya miliknya sendiri (Kecuali cancelled) |
| **Transaksi Keuangan**: Batalkan transaksi | ✅ Ya (Kecuali cancelled) | ✅ Hanya miliknya sendiri (Kecuali cancelled) |
| **Transaksi Keuangan**: Hapus (Soft delete) | ✅ Ya | ❌ Ditolak (403 Forbidden) |

---

## 5. Daftar REST API Endpoints

Semua endpoint dapat diakses melalui prefix `/api/v1/*` maupun direct alias `/api/*`. Wajib menyertakan header:
`Authorization: Bearer <TOKEN>` dan `Accept: application/json`.

### A. Kategori Transaksi (`/api/v1/transaction-categories` & `/api/transaction-categories`)
| Method | Endpoint | Query Param / Body | Keterangan | Akses |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/transaction-categories` | `search`, `type`, `is_active`, `all=true`, `page` | Daftar kategori transaksi | Admin & Staff (aktif saja) |
| `POST` | `/api/v1/transaction-categories` | Body: `name`, `type`, `description`, `is_active` | Tambah kategori baru | Admin |
| `GET` | `/api/v1/transaction-categories/{id}` | - | Detail kategori transaksi | Admin & Staff |
| `PUT` | `/api/v1/transaction-categories/{id}` | Body: `name`, `type`, `description`, `is_active` | Perbarui kategori transaksi | Admin |
| `PATCH`| `/api/v1/transaction-categories/{id}/status` | Body: `is_active` (boolean) | Ubah status aktif/nonaktif | Admin |
| `DELETE`| `/api/v1/transaction-categories/{id}` | - | Hapus kategori (ditolak jika ada relasi) | Admin |

### B. Transaksi Keuangan (`/api/v1/transactions` & `/api/transactions`)
| Method | Endpoint | Query Param / Body | Keterangan | Akses |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/transactions` | `search`, `transaction_type`, `category_id`, `status`, `start_date`, `end_date`, `created_by`, `page` | Seluruh transaksi + ringkasan saldo | Admin & Staff |
| `POST` | `/api/v1/transactions` | Body: `transaction_type`, `category_id`, `amount`, `transaction_date`, `description` | Tambah transaksi baru | Admin & Staff |
| `GET` | `/api/v1/transactions/{id}` | - | Detail transaksi keuangan | Admin & Staff |
| `PUT` | `/api/v1/transactions/{id}` | Body: `category_id`, `amount`, `transaction_date`, `description` | Perbarui transaksi (cek kepemilikan) | Admin / Pemilik Staff |
| `PATCH`| `/api/v1/transactions/{id}/cancel` | Body: `cancellation_reason` | Batalkan transaksi (audit log) | Admin / Pemilik Staff |
| `DELETE`| `/api/v1/transactions/{id}` | - | Hapus transaksi (soft delete) | Admin |

### C. Dedicated Endpoint Pemasukan & Pengeluaran
| Method | Endpoint | Keterangan | Akses |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/incomes` (atau `/api/incomes`) | Daftar khusus transaksi pemasukan (income) | Admin & Staff |
| `POST` | `/api/v1/incomes` (atau `/api/incomes`) | Tambah transaksi pemasukan (otomatis type=income) | Admin & Staff |
| `GET` | `/api/v1/expenses` (atau `/api/expenses`) | Daftar khusus transaksi pengeluaran (expense) | Admin & Staff |
| `POST` | `/api/v1/expenses` (atau `/api/expenses`) | Tambah transaksi pengeluaran (otomatis type=expense) | Admin & Staff |

---

## 6. Contoh Request & Response JSON

### A. Contoh Request: Tambah Pemasukan
`POST /api/v1/transactions` (atau `/api/v1/incomes`)
```json
{
  "transaction_type": "income",
  "category_id": 1,
  "amount": 150000.00,
  "transaction_date": "2026-11-05",
  "description": "Penjualan beras dan sembako shift pagi"
}
```

### B. Contoh Response Berhasil (HTTP 201 Created)
```json
{
  "success": true,
  "message": "Transaksi Pemasukan berhasil ditambahkan",
  "data": {
    "id": 1,
    "transaction_code": "TRX-20261105-0001",
    "transaction_type": "income",
    "amount": "150000.00",
    "amount_numeric": 150000,
    "transaction_date": "2026-11-05",
    "description": "Penjualan beras dan sembako shift pagi",
    "status": "completed",
    "category": {
      "id": 1,
      "name": "Penjualan Produk",
      "type": "income",
      "is_active": true
    },
    "created_by": {
      "id": 2,
      "name": "Staff Toko",
      "email": "staff@fintoko.test",
      "role": "staff"
    },
    "updated_by": null,
    "cancelled_at": null,
    "cancelled_by": null,
    "cancellation_reason": null,
    "created_at": "2026-11-05T06:05:00.000000Z",
    "updated_at": "2026-11-05T06:05:00.000000Z"
  }
}
```

### C. Contoh Response Daftar Transaksi + Ringkasan Saldo (HTTP 200 OK)
`GET /api/v1/transactions`
```json
{
  "success": true,
  "message": "Daftar transaksi keuangan berhasil diambil",
  "data": [ ... ],
  "summary": {
    "total_income": "2175000.00",
    "total_expense": "880000.00",
    "net_balance": "1295000.00",
    "total_income_numeric": 2175000,
    "total_expense_numeric": 880000,
    "net_balance_numeric": 1295000
  },
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 6
  }
}
```

---

## 7. Akun Pengujian Development (Tersedia dari Seeder)

| Role | Alamat Email / Username | Kata Sandi | Status | Hak Akses |
| :--- | :--- | :--- | :---: | :--- |
| **admin** | `admin@fintoko.test` / `admin` | `password` / `Admin1234` | **Aktif** | Akses penuh (Dashboard Admin, Master Data, Financial Management, Kelola Pengguna) |
| **staff** | `staff@fintoko.test` | `password` | **Aktif** | Akses staf kasir (Pencatatan Pemasukan/Pengeluaran, Katalog Master, Profil Pribadi) |
| **staff** | `nonaktif@fintoko.test` | `password` | **Nonaktif** | Untuk pengujian blokir akun dinonaktifkan |

---

## 8. Cara Menjalankan Aplikasi & Pengujian di Laragon

### 1. Migrasi Database
Jalankan migrasi seluruh tabel (users, categories, units, products, transaction_categories, financial_transactions):
```bash
php artisan migrate
```

### 2. Seeder Database
Isi data awal pengguna, master data produk, kategori transaksi, dan riwayat transaksi awal:
```bash
php artisan db:seed
```

### 3. Jalankan Web Server Laravel
```bash
php artisan serve --port=8000
```
Buka browser di: **http://127.0.0.1:8000**

### 4. Menjalankan Automated Testing
Seluruh 68 automated test dapat dijalankan kapan saja dengan:
```bash
php artisan test
```
Untuk menguji modul Financial Management secara spesifik:
```bash
php artisan test --filter=FinancialManagementTest
```

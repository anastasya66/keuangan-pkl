# FinToko — Financial Management System

Aplikasi modern pengelolaan transaksi, master data, dan pencatatan keuangan toko. Dibangun secara terpadu (**Unified Modern Monolith SPA**) menggunakan **Laravel 12 (PHP 8.2)**, **React 19**, **Tailwind CSS v4**, dan **MariaDB**.

---

## 1. Perkembangan Proyek

- **Tahap 1 — Project Foundation**: ✅ SELESAI (Konfigurasi Laravel 12, MariaDB `fintoko_db`, React 19 + Vite, Tailwind CSS v4, Sanctum API prefix `/api/v1`).
- **Tahap 2 — Authentication & User Management**: ✅ SELESAI (Sistem login/logout Sanctum, proteksi status akun `is_active`, Laravel Policies, Admin User Management, Profile & Password management, Protected Routes React, Admin & Staff Layouts).
- **Tahap 3 — Master Data**: ✅ SELESAI (Modul Kategori Produk, Satuan Produk, Master Produk, dan User Management terintegrasi penuh backend REST API & frontend React dengan pencarian, filter, pagination, dan otorisasi role).

---

## 2. Technology Stack

### Backend
- **Framework**: Laravel 12
- **Bahasa**: PHP 8.2+
- **Database**: MariaDB 10.4+ / MySQL 8+
- **Autentikasi**: Laravel Sanctum (Personal Access Token / Bearer Token)
- **Otorisasi**: Laravel Policies (`CategoryPolicy`, `UnitPolicy`, `ProductPolicy`, `UserPolicy`) & Custom Middleware (`active`, `role`)
- **Penghapusan Data**: Soft Deletes (`deleted_at`) & Referential Integrity Protection (`restrictOnDelete`)
- **API Spec**: RESTful JSON responses (`/api/v1`)

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

### A. Tabel `categories`
Menyimpan kelompok / kategori produk toko.
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment primary key |
| `name` | VARCHAR(100) | Nama kategori (wajib, unik) |
| `description` | TEXT | Keterangan kelompok produk (opsional) |
| `is_active` | BOOLEAN | Status aktif kategori (default: true) |
| `created_at` | TIMESTAMP | Waktu pembuatan |
| `updated_at` | TIMESTAMP | Waktu pembaruan terakhir |
| `deleted_at` | TIMESTAMP | Waktu soft delete |

### B. Tabel `units`
Menyimpan standarisasi satuan takaran dan kemasan produk toko.
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment primary key |
| `name` | VARCHAR(50) | Nama satuan (wajib, unik, cth: Kilogram, Pieces) |
| `symbol` | VARCHAR(20) | Simbol satuan (wajib, unik, cth: kg, pcs, L, dus) |
| `description` | TEXT | Keterangan satuan (opsional) |
| `is_active` | BOOLEAN | Status aktif satuan (default: true) |
| `created_at` | TIMESTAMP | Waktu pembuatan |
| `updated_at` | TIMESTAMP | Waktu pembaruan terakhir |
| `deleted_at` | TIMESTAMP | Waktu soft delete |

### C. Tabel `products`
Menyimpan informasi master produk, harga modal, dan harga jual.
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment primary key |
| `category_id` | BIGINT (FK) | Relasi ke `categories.id` (`restrictOnDelete`) |
| `unit_id` | BIGINT (FK) | Relasi ke `units.id` (`restrictOnDelete`) |
| `sku` | VARCHAR(50) | Kode unik produk (wajib, unik) |
| `barcode` | VARCHAR(100) | Kode barcode scanner (opsional, unik jika terisi) |
| `name` | VARCHAR(150) | Nama produk (wajib) |
| `description` | TEXT | Deskripsi produk (opsional) |
| `purchase_price` | DECIMAL(15,2)| Harga beli modal toko (min: 0) |
| `selling_price` | DECIMAL(15,2) | Harga jual kasir toko (min: 0) |
| `is_active` | BOOLEAN | Status ketersediaan produk (default: true) |
| `created_at` | TIMESTAMP | Waktu pembuatan |
| `updated_at` | TIMESTAMP | Waktu pembaruan terakhir |
| `deleted_at` | TIMESTAMP | Waktu soft delete |

### D. Tabel `users`
Menyimpan kredensial dan hak akses administrator & staf toko.
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Primary key |
| `name` | VARCHAR | Nama lengkap pengguna |
| `email` | VARCHAR | Alamat email unik |
| `password` | VARCHAR | Kata sandi hash Bcrypt |
| `role` | VARCHAR | Peran pengguna (`admin` atau `staff`) |
| `is_active` | BOOLEAN | Status akun dapat login (default: true) |
| `created_at` | TIMESTAMP | Waktu pendaftaran |
| `updated_at` | TIMESTAMP | Waktu pembaruan |
| `deleted_at` | TIMESTAMP | Waktu soft delete |

### Relasi Antar Tabel
```text
categories (1) ──< (hasMany) ─── products (N) >─── (belongsTo) >── units (1)
```
- Setiap produk wajib berelasi dengan 1 Kategori dan 1 Satuan yang aktif.
- Kategori dan satuan yang masih digunakan oleh produk **dilarang dihapus** untuk menjaga integritas data (dilindungi oleh database foreign key `restrictOnDelete` dan validasi controller).

---

## 4. Otorisasi Berdasarkan Role

| Fitur / Resource | Role Admin | Role Staff |
| :--- | :---: | :---: |
| **Kategori**: Lihat daftar & detail | ✅ Semua | ✅ Hanya yang Aktif |
| **Kategori**: Tambah, Ubah, Ubah Status, Hapus | ✅ Ya | ❌ Ditolak (403 Forbidden) |
| **Satuan**: Lihat daftar & detail | ✅ Semua | ✅ Hanya yang Aktif |
| **Satuan**: Tambah, Ubah, Ubah Status, Hapus | ✅ Ya | ❌ Ditolak (403 Forbidden) |
| **Produk**: Lihat katalog produk & filter | ✅ Semua | ✅ Hanya yang Aktif |
| **Produk**: Tambah, Ubah, Ubah Status, Hapus | ✅ Ya | ❌ Ditolak (403 Forbidden) |
| **Kelola Pengguna**: CRUD akun staf & admin | ✅ Ya | ❌ Ditolak (403 Forbidden) |
| **Profil Pribadi**: Lihat & ubah data sendiri | ✅ Ya | ✅ Ya |

---

## 5. Daftar Lengkap REST API Endpoints

Semua endpoint diawali dengan prefix `/api/v1`. Seluruh endpoint terproteksi mewajibkan header:
`Authorization: Bearer <TOKEN>` dan `Accept: application/json`.

### A. Autentikasi (`/api/v1/auth`)
| Method | Endpoint | Keterangan | Akses |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/auth/login` | Login (menerima email/username & password) | Publik (Throttle 10/min) |
| `POST` | `/api/v1/auth/logout` | Cabut token autentikasi aktif | Authenticated |
| `GET` | `/api/v1/auth/me` | Dapatkan data pengguna login saat ini | Authenticated |
| `POST` | `/api/v1/auth/refresh`| Rotasi Personal Access Token baru | Authenticated |

### B. Profil Pengguna (`/api/v1/user`)
| Method | Endpoint | Keterangan | Akses |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/user/profile` | Dapatkan data profil saya | Admin & Staff |
| `PUT` | `/api/v1/user/profile` | Perbarui nama dan email saya | Admin & Staff |
| `PUT` | `/api/v1/user/change-password` | Ganti kata sandi saya | Admin & Staff |

### C. Kategori Produk (`/api/v1/categories`)
| Method | Endpoint | Parameter Query | Keterangan | Akses |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/categories` | `search`, `is_active`, `all=true`, `page` | Daftar kategori | Admin & Staff (aktif saja) |
| `GET` | `/api/v1/categories/{id}` | - | Detail kategori | Admin & Staff |
| `POST` | `/api/v1/categories` | Body: `name`, `description`, `is_active` | Tambah kategori | Admin |
| `PUT` | `/api/v1/categories/{id}` | Body: `name`, `description`, `is_active` | Perbarui kategori | Admin |
| `PATCH`| `/api/v1/categories/{id}/status` | Body: `is_active` (boolean) | Ubah status aktif | Admin |
| `DELETE`| `/api/v1/categories/{id}` | - | Soft delete (ditolak jika berelasi) | Admin |

### D. Satuan Produk (`/api/v1/units`)
| Method | Endpoint | Parameter Query | Keterangan | Akses |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/units` | `search`, `is_active`, `all=true`, `page` | Daftar satuan produk | Admin & Staff (aktif saja) |
| `GET` | `/api/v1/units/{id}` | - | Detail satuan | Admin & Staff |
| `POST` | `/api/v1/units` | Body: `name`, `symbol`, `description`, `is_active` | Tambah satuan | Admin |
| `PUT` | `/api/v1/units/{id}` | Body: `name`, `symbol`, `description`, `is_active` | Perbarui satuan | Admin |
| `PATCH`| `/api/v1/units/{id}/status` | Body: `is_active` (boolean) | Ubah status aktif | Admin |
| `DELETE`| `/api/v1/units/{id}` | - | Soft delete (ditolak jika berelasi) | Admin |

### E. Master Produk (`/api/v1/products`)
| Method | Endpoint | Parameter Query | Keterangan | Akses |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/products` | `search`, `category_id`, `unit_id`, `is_active`, `min_price`, `max_price`, `page` | Daftar produk toko | Admin & Staff (aktif saja) |
| `GET` | `/api/v1/products/{id}` | - | Detail produk | Admin & Staff |
| `POST` | `/api/v1/products` | Body: `name`, `sku`, `barcode`, `category_id`, `unit_id`, `purchase_price`, `selling_price`, `is_active` | Tambah produk baru | Admin |
| `PUT` | `/api/v1/products/{id}` | Body sama dengan store | Perbarui produk | Admin |
| `PATCH`| `/api/v1/products/{id}/status` | Body: `is_active` (boolean) | Ubah status aktif | Admin |
| `DELETE`| `/api/v1/products/{id}` | - | Soft delete produk | Admin |

### F. Manajemen Pengguna (`/api/v1/admin/users`)
| Method | Endpoint | Keterangan | Akses |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/admin/users` | Daftar semua akun pengguna (search & pagination) | Admin Only |
| `POST` | `/api/v1/admin/users` | Daftarkan akun admin atau staff baru | Admin Only |
| `GET` | `/api/v1/admin/users/{id}` | Detail profil pengguna | Admin Only |
| `PUT` | `/api/v1/admin/users/{id}` | Perbarui nama, email, atau password pengguna | Admin Only |
| `PATCH`| `/api/v1/admin/users/{id}/role` | Ubah peran pengguna (`admin` / `staff`) | Admin Only |
| `PATCH`| `/api/v1/admin/users/{id}/status` | Aktifkan/nonaktifkan akun (cabut token jika nonaktif) | Admin Only |
| `DELETE`| `/api/v1/admin/users/{id}` | Hapus akun pengguna (soft delete, lindungi admin terakhir) | Admin Only |

---

## 6. Akun Pengujian Development (Tersedia dari Seeder)

| Role | Alamat Email / Username | Kata Sandi | Status | Hak Akses |
| :--- | :--- | :--- | :---: | :--- |
| **admin** | `admin@fintoko.test` / `admin` | `password` / `Admin1234` | **Aktif** | Akses penuh (Dashboard Admin, Master Data, Kelola Pengguna) |
| **staff** | `staff@fintoko.test` | `password` | **Aktif** | Akses staf kasir (Katalog Produk & Satuan Aktif, Profil) |
| **staff** | `nonaktif@fintoko.test` | `password` | **Nonaktif** | Untuk pengujian blokir akun dinonaktifkan |

---

## 7. Cara Menjalankan Aplikasi & Pengujian

### 1. Migrasi Database
Jalankan migrasi seluruh tabel (users, categories, units, products):
```bash
php artisan migrate
```

### 2. Seeder Database
Isi data awal pengguna dan contoh master data katalog toko:
```bash
php artisan db:seed
```

### 3. Kompilasi Frontend React SPA
```bash
npm run build
```

### 4. Jalankan Web Server Laravel
```bash
php artisan serve --port=8000
```
Buka browser di: **http://127.0.0.1:8000**

### 5. Menjalankan Automated Testing
Seluruh 51 test (Unit, ApiFoundation, AuthenticationAndUser, MasterData) dapat dijalankan kapan saja dengan:
```bash
php artisan test
```
Untuk menguji modul Master Data secara spesifik:
```bash
php artisan test --filter=MasterDataTest
```

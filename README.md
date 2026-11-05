# FinToko — Financial Management System

Aplikasi modern pengelolaan transaksi dan pencatatan keuangan toko. Dibangun secara terpadu (Unified Modern Monolith SPA) menggunakan Laravel, React, Tailwind CSS, dan MariaDB dalam **satu kesatuan proyek tanpa pemisahan folder frontend dan backend**.

---

## 1. Deskripsi Aplikasi
**FinToko** menyediakan platform pencatatan keuangan toko yang rapi, cepat, dan aman. Seluruh aset React dan REST API dikelola dalam satu repository terpadu:
- **Backend API**: Berbasis Laravel 12 dengan arsitektur RESTful (`/api/v1`) & Laravel Sanctum.
- **Frontend SPA**: Berbasis React 19 + Tailwind CSS v4 yang di-bundle secara efisien menggunakan Vite dan Laravel Vite Plugin.

---

## 2. Technology Stack

### Core Frameworks & Libraries
- **Backend Framework**: Laravel 12
- **Language**: PHP 8.2+
- **API Authentication**: Laravel Sanctum (Bearer Token)
- **Frontend Library**: React 19
- **Build Tool**: Vite 7
- **Styling**: Tailwind CSS v4
- **HTTP Client**: Axios (dengan request/response interceptor)
- **Routing**: React Router DOM v7
- **Database**: MariaDB 10.4+ / MySQL 8+
- **Database Name**: `fintoko_db`

---

## 3. Struktur Direktori Proyek (Unified)

```text
fintoko/ (keuangan-pkl/)
├── app/                      # Model Eloquent (User), Controller API, FormRequests, Middleware
│   ├── Http/
│   │   ├── Controllers/Api/  # HealthController, AuthController, AdminController
│   │   ├── Middleware/       # EnsureUserHasRole
│   │   └── Requests/Auth/    # LoginRequest
│   └── Models/User.php       # Model User dengan HasApiTokens & kolom role
├── bootstrap/                # Bootstrap aplikasi & prefix /api/v1
├── config/                   # Konfigurasi aplikasi (CORS, Sanctum, Database)
├── database/                 # Migrasi (users, tokens, roles) & Seeder default
├── docs/                     # Dokumentasi arsitektur dan spesifikasi API
├── public/                   # Entry point web server & compiled assets
├── resources/                # Source code Frontend (React & Styles)
│   ├── css/
│   │   └── app.css           # Tailwind CSS v4 setup
│   ├── js/                   # Source Code React SPA
│   │   ├── assets/           # Media & gambar
│   │   ├── components/       # Komponen UI reusable
│   │   ├── contexts/         # State management context
│   │   ├── hooks/            # Custom hooks
│   │   ├── layouts/          # Layout halaman
│   │   ├── pages/            # View pages
│   │   ├── routes/           # Routing React Router DOM
│   │   ├── services/
│   │   │   └── api.js        # Axios instance terpusat (/api/v1)
│   │   ├── utils/            # Helper fungsi & formatter
│   │   ├── App.jsx           # UI FinToko & indikator status health
│   │   └── main.jsx          # Entry point React
│   └── views/
│       └── app.blade.php     # Template Blade SPA pembungkus React
├── routes/
│   ├── api.php               # Route REST API v1 (/api/v1/health, auth, admin)
│   └── web.php               # Route SPA handler
├── storage/                  # Storage aplikasi & logs
├── tests/                    # Feature & Unit automated test suite
├── .env.example              # Template konfigurasi environment
├── artisan                   # CLI tool Laravel
├── composer.json             # Dependensi PHP
├── package.json              # Dependensi JavaScript (React, Tailwind, Vite)
├── vite.config.js            # Konfigurasi Vite + Laravel + React
├── .gitignore                # Aturan Git ignore kredensial & build
├── README.md                 # Dokumentasi proyek
└── LICENSE                   # MIT License
```

---

## 4. Persyaratan Sistem
- **PHP** >= 8.2 (dengan ekstensi `pdo_mysql`, `bcmath`, `curl`, `mbstring`, `openssl`, `fileinfo`)
- **Composer** >= 2.x
- **Node.js** >= 18.x
- **NPM** >= 9.x
- **MariaDB** atau **MySQL** (via Laragon / XAMPP)

---

## 5. Konfigurasi Database (MariaDB)

Buat database melalui terminal atau phpMyAdmin / HeidiSQL:
```sql
CREATE DATABASE IF NOT EXISTS fintoko_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

---

## 6. Panduan Menjalankan Aplikasi

Semua perintah dijalankan langsung dari root direktori proyek:

### 1. Salin Environment
```bash
cp .env.example .env
```
Pastikan pengaturan database di `.env` sesuai:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fintoko_db
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Install Dependensi & Setup Awal
```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
```

### 3. Menjalankan Server Development
Buka 2 tab terminal di folder proyek:

**Terminal 1 — Backend Server:**
```bash
php artisan serve
```
> Server aktif di `http://127.0.0.1:8000`.

**Terminal 2 — Vite Asset Compiler / HMR:**
```bash
npm run dev
```

Buka browser di **`http://127.0.0.1:8000`** untuk melihat dashboard FinToko dengan status badge hijau **"Connected"**.

---

## 7. Menjalankan Automated Test Backend

```bash
php artisan test --filter=ApiFoundationTest
```

---

## 8. Daftar Endpoint API (`/api/v1`)

| Method | Endpoint | Hak Akses | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/health` | Public | Cek status kesehatan API |
| `POST` | `/api/v1/auth/login` | Public | Login dan peroleh token Sanctum |
| `POST` | `/api/v1/auth/logout` | `auth:sanctum` | Cabut token aktif |
| `GET` | `/api/v1/auth/me` | `auth:sanctum` | Informasi profil user yang sedang login |
| `GET` | `/api/v1/admin/check` | `auth:sanctum`, `role:admin` | Verifikasi hak akses admin |

---

## 9. Akun Default Pengujian

| Role | Email | Password |
| :--- | :--- | :--- |
| **admin** | `admin@fintoko.test` | `password` |
| **staff** | `staff@fintoko.test` | `password` |

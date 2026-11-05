# FinToko — Financial Management System

Aplikasi modern pengelolaan transaksi dan pencatatan keuangan toko yang dirancang dengan arsitektur decoupled (Backend RESTful API + Frontend Single Page Application).

---

## 1. Deskripsi Aplikasi
**FinToko** dibangun untuk memberikan visibilitas penuh terhadap kondisi finansial toko secara cepat, akurat, dan aman. Proyek ini memisahkan backend dan frontend secara menyeluruh sehingga memudahkan skalabilitas dan deployment di berbagai platform.

---

## 2. Technology Stack

### Backend
- **Framework**: Laravel 11/12
- **Bahasa**: PHP 8.2+
- **Arsitektur**: RESTful API
- **Autentikasi**: Laravel Sanctum (Bearer Token)
- **Format Pertukaran Data**: JSON
- **API Versioning**: `/api/v1`

### Frontend
- **Library**: React 19
- **Bahasa**: JavaScript
- **Build Tool**: Vite
- **Styling**: Tailwind CSS v4
- **HTTP Client**: Axios (dengan interceptors)
- **Routing**: React Router DOM v7

### Database & Environment
- **Database**: MariaDB 10.4+ (atau MySQL 8+)
- **Nama Database**: `fintoko_db`
- **Charset & Collation**: `utf8mb4` / `utf8mb4_unicode_ci`
- **Development Stack**: Laragon / XAMPP

---

## 3. Struktur Direktori Proyek

```text
fintoko/
│
├── backend/                  # RESTful API Backend (Laravel)
│   ├── app/                  # Controller, Models, Middleware, FormRequests
│   ├── bootstrap/            # Application configuration & API prefix setup
│   ├── config/               # Konfigurasi aplikasi (CORS, Sanctum, Database)
│   ├── database/             # Migrasi dan Seeder (Admin & Staff)
│   ├── routes/               # api.php (/api/v1/...) & web.php
│   ├── storage/              # Storage & Logs
│   ├── tests/                # Automated Feature & Unit Tests
│   ├── .env.example          # Template konfigurasi environment backend
│   └── composer.json         # Dependensi backend PHP
│
├── frontend/                 # Client Single Page Application (React + Vite)
│   ├── public/               # Asset statis
│   ├── src/
│   │   ├── assets/           # Media & gambar
│   │   ├── components/       # Komponen UI modular
│   │   ├── layouts/          # Layout halaman
│   │   ├── pages/            # Komponen halaman tampilan
│   │   ├── routes/           # Routing aplikasi
│   │   ├── services/         # api.js (Axios instance & interceptors)
│   │   ├── hooks/            # Custom hooks
│   │   ├── contexts/         # State management context
│   │   ├── utils/            # Helper formatters
│   │   ├── App.jsx           # Komponen utama & uji status health
│   │   └── main.jsx          # Entry point React
│   ├── .env.example          # Template environment frontend
│   ├── package.json          # Dependensi frontend NPM
│   └── vite.config.js        # Konfigurasi Vite & Tailwind CSS
│
├── docs/                     # Dokumentasi arsitektur dan spesifikasi API
├── .gitignore                # Aturan ignore Git root
├── README.md                 # Dokumentasi proyek
└── LICENSE                   # MIT License
```

---

## 4. Persyaratan Instalasi

Pastikan telah terpasang pada komputer Anda:
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

## 6. Konfigurasi & Menjalankan Backend

1. Masuk ke direktori `backend`:
   ```bash
   cd backend
   ```
2. Salin template `.env.example` ke `.env`:
   ```bash
   cp .env.example .env
   ```
3. Sesuaikan konfigurasi database di file `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=fintoko_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Install dependensi PHP (jika belum):
   ```bash
   composer install
   ```
5. Generate application key:
   ```bash
   php artisan key:generate
   ```
6. Jalankan migrasi dan seeder awal:
   ```bash
   php artisan migrate --seed
   ```
7. Jalankan development server backend:
   ```bash
   php artisan serve
   ```
   Backend akan aktif di `http://127.0.0.1:8000`.

---

## 7. Konfigurasi & Menjalankan Frontend

1. Buka terminal baru dan masuk ke direktori `frontend`:
   ```bash
   cd frontend
   ```
2. Salin `.env.example` ke `.env`:
   ```bash
   cp .env.example .env
   ```
   Pastikan berisi:
   ```env
   VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1
   ```
3. Install dependensi JavaScript (jika belum):
   ```bash
   npm install
   ```
4. Jalankan development server frontend:
   ```bash
   npm run dev
   ```
   Frontend akan aktif di `http://127.0.0.1:5173`. Buka di browser untuk memverifikasi koneksi status **Connected**.

---

## 8. Menjalankan Automated Test Backend

Backend dilengkapi dengan Feature Test otomatis untuk memastikan seluruh fungsi fondasi bekerja:
```bash
cd backend
php artisan test --filter=ApiFoundationTest
```

---

## 9. Daftar Endpoint API (`/api/v1`)

| Method | Endpoint | Hak Akses | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/health` | Public | Cek status operasional API |
| `POST` | `/api/v1/auth/login` | Public | Login dan dapatkan token Sanctum |
| `POST` | `/api/v1/auth/logout` | `auth:sanctum` | Cabut token aktif saat ini |
| `GET` | `/api/v1/auth/me` | `auth:sanctum` | Informasi profil user yang sedang login |
| `GET` | `/api/v1/admin/check` | `auth:sanctum`, `role:admin` | Verifikasi hak akses admin |

---

## 10. Panduan Pengujian API Melalui Postman

### Akun Awal (Tersedia dari Seeder)
- **Akun Admin**: `admin@fintoko.test` / Password: `password`
- **Akun Staff**: `staff@fintoko.test` / Password: `password`

### Langkah Pengujian:

#### 1. Uji Health Check
- Buat request `GET http://127.0.0.1:8000/api/v1/health`
- Klik **Send**. Status harus `200 OK` dengan status `success: true`.

#### 2. Uji Login
- Buat request `POST http://127.0.0.1:8000/api/v1/auth/login`
- Header: `Content-Type: application/json` & `Accept: application/json`
- Body (Raw JSON):
  ```json
  {
    "email": "admin@fintoko.test",
    "password": "password"
  }
  ```
- Klik **Send**. Salin token yang dihasilkan pada field `data.token`.

#### 3. Uji Endpoint Me (Profil)
- Buat request `GET http://127.0.0.1:8000/api/v1/auth/me`
- Tab **Auth**: Pilih Type **Bearer Token** dan paste token tadi.
- Klik **Send**. Status harus `200 OK` dan mengembalikan data profil admin.

#### 4. Uji Role Protection (Admin Check)
- Buat request `GET http://127.0.0.1:8000/api/v1/admin/check`
- Gunakan token admin: Respon `200 OK` (`authorized: true`).
- Gunakan token staff: Respon `403 Forbidden` (`Akses ditolak`).

#### 5. Uji Logout
- Buat request `POST http://127.0.0.1:8000/api/v1/auth/logout`
- Gunakan Bearer Token aktif.
- Respon `200 OK`. Token telah dicabut. Jika token tersebut digunakan kembali ke `/auth/me`, API akan mengembalikan `401 Unauthorized`.

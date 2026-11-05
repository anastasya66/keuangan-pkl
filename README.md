# FinToko — Financial Management System

Aplikasi modern pengelolaan transaksi dan pencatatan keuangan toko. Dibangun secara terpadu (**Unified Modern Monolith SPA**) menggunakan **Laravel 12 (PHP 8.2)**, **React 19**, **Tailwind CSS v4**, dan **MariaDB**.

---

## 1. Perkembangan Proyek

- **Tahap 1 — Project Foundation**: ✅ SELESAI (Konfigurasi Laravel 12, MariaDB `fintoko_db`, React 19 + Vite, Tailwind CSS v4, Sanctum API prefix `/api/v1`).
- **Tahap 2 — Authentication & User Management**: ✅ SELESAI (Sistem login/logout Sanctum, proteksi status akun `is_active`, Laravel Policies, Admin User Management, Profile & Password management, Protected Routes React, Admin & Staff Layouts).

---

## 2. Technology Stack

### Backend
- **Framework**: Laravel 12
- **Bahasa**: PHP 8.2+
- **Database**: MariaDB 10.4+ / MySQL 8+
- **Autentikasi**: Laravel Sanctum (Personal Access Token)
- **Otorisasi**: Laravel Policies & Custom Middleware (`active`, `role`)
- **Penghapusan Data**: Soft Deletes (`deleted_at`)

### Frontend
- **Framework**: React 19 (SPA)
- **Bundler**: Vite 7 via `laravel-vite-plugin`
- **Styling**: Tailwind CSS v4
- **State Autentikasi**: React Context (`AuthContext` & `useAuth`)
- **HTTP Client**: Axios dengan Token Interceptors (`resources/js/services/api.js`)
- **Routing & Proteksi**: React Router DOM v7 (`GuestRoute`, `ProtectedRoute`)

---

## 3. Struktur Direktori Proyek

```text
keuangan-pkl/ (Root FinToko)
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── Admin/UserController.php  # Admin CRUD User Management
│   │   │   ├── AdminController.php       # Verifikasi role admin
│   │   │   ├── AuthController.php        # Login, Logout, Me, Refresh Token
│   │   │   ├── HealthController.php      # Health check endpoint
│   │   │   └── ProfileController.php     # Profil & Ubah Password
│   │   ├── Middleware/
│   │   │   ├── EnsureAccountIsActive.php # Blokir akun nonaktif
│   │   │   └── EnsureUserHasRole.php     # Otorisasi role admin & staff
│   │   └── Requests/                     # Validasi FormRequest ketat
│   ├── Models/User.php                   # Model User (HasApiTokens, SoftDeletes)
│   └── Policies/UserPolicy.php           # Aturan otorisasi resource User
├── bootstrap/app.php                     # Prefix /api/v1 & alias middleware
├── config/                               # Konfigurasi aplikasi
├── database/
│   ├── migrations/                       # Tabel users (role, is_active, soft deletes)
│   └── seeders/DatabaseSeeder.php        # Akun development awal
├── docs/                                 # Dokumentasi arsitektur & API
├── public/                               # Web root & output kompilasi Vite
├── resources/
│   ├── css/app.css                       # Styling Tailwind CSS v4
│   ├── js/                               # Aplikasi React SPA
│   │   ├── components/auth/              # ProtectedRoute & GuestRoute
│   │   ├── contexts/AuthContext.jsx      # State manajemen sesi & token
│   │   ├── hooks/useAuth.js              # Custom hook useAuth
│   │   ├── layouts/                      # AdminLayout & StaffLayout
│   │   ├── pages/
│   │   │   ├── auth/LoginPage.jsx        # Halaman Login modern
│   │   │   ├── admin/                    # AdminDashboard & UserManagementPage
│   │   │   ├── staff/                    # StaffDashboardPage
│   │   │   └── profile/ProfilePage.jsx   # Pengaturan Profil & Ubah Sandi
│   │   ├── routes/AppRoutes.jsx          # Routing aplikasi terproteksi
│   │   └── services/                     # api.js, authService, userService
│   └── views/app.blade.php               # Template Blade SPA
├── routes/
│   ├── api.php                           # Route REST API v1
│   └── web.php                           # Catch-all SPA route
├── tests/Feature/                        # Automated Feature Tests (31 tests)
├── .env.example                          # Template konfigurasi environment
├── artisan                               # CLI tool Laravel
├── composer.json                         # Dependensi PHP
├── package.json                          # Dependensi JavaScript
└── vite.config.js                        # Konfigurasi Vite
```

---

## 4. Akun Pengujian Development (Tersedia dari Seeder)

| Role | Alamat Email | Kata Sandi | Status | Hak Akses |
| :--- | :--- | :--- | :---: | :--- |
| **admin** | `admin@fintoko.test` | `password` | **Aktif** | Akses penuh (Dashboard Admin & Kelola Pengguna) |
| **staff** | `staff@fintoko.test` | `password` | **Aktif** | Akses staf kasir (Dashboard Staff & Profil Pribadi) |
| **staff** | `nonaktif@fintoko.test` | `password` | **Nonaktif** | Untuk pengujian blokir akun dinonaktifkan |

---

## 5. Menjalankan Aplikasi

Pastikan MariaDB pada Laragon/XAMPP telah aktif pada port 3306.

### 1. Migrasi & Seeder Database (Jika Belum)
```bash
php artisan migrate --seed
```

### 2. Kompilasi Aset Frontend (React + Tailwind CSS)
```bash
npm run build
```
*(Atau jalankan `npm run dev` untuk hot reload saat proses development)*.

### 3. Jalankan Development Server
```bash
php artisan serve --port=8000
```

Buka browser Anda di **[http://127.0.0.1:8000](http://127.0.0.1:8000)**:
- Belum login akan otomatis diarahkan ke halaman login: `/login`.
- Login sebagai **admin** akan diarahkan ke dashboard admin: `/admin/dashboard`.
- Login sebagai **staff** akan diarahkan ke dashboard staf: `/staff/dashboard`.

---

## 6. Menjalankan Pengujian Otomatis (Automated Testing)

Seluruh logika bisnis, otorisasi, dan proteksi role diuji dengan **31 Feature Tests (100% Pass)**:
```bash
php artisan test
```

Skenario pengujian meliputi:
- Autentikasi email & password benar vs salah.
- Penolakan akun nonaktif.
- Pencabutan token saat logout.
- Perlindungan endpoint admin terhadap akses staf (403 Forbidden).
- Pencegahan penurunan role, penonaktifan, atau penghapusan admin terakhir.
- Soft delete pengguna dan pencabutan token seketika saat dinonaktifkan.

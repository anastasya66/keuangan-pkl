# FinToko — Architecture & System Design Documentation

## 1. Overview
**FinToko** adalah aplikasi manajemen keuangan toko modern yang dirancang dalam struktur **Unified Modern Monolith (Single Codebase SPA)**:
- Backend RESTful API dan Frontend React SPA disatukan dalam satu repository tanpa folder terpisah `frontend/` dan `backend/`.
- Frontend dikompilasi oleh Vite melalui `laravel-vite-plugin` ke dalam direktori `public/build/`.
- Seluruh routing web diarahkan ke view `resources/views/app.blade.php`, sedangkan request `/api/v1/*` dilayani oleh router API Laravel.

---

## 2. Technology Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **Architecture**: RESTful API
- **Authentication**: Laravel Sanctum (Token-based API authentication)
- **API Versioning**: Prefix `/api/v1`
- **Database**: MariaDB 10.4+ / MySQL

### Frontend
- **Framework/Library**: React 19
- **Build Tool**: Vite 7 via `laravel-vite-plugin`
- **Styling**: Tailwind CSS v4
- **HTTP Client**: Axios dengan interceptors
- **Routing**: React Router DOM v7

---

## 3. Directory Structure

```text
fintoko/
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

## 4. Keuntungan Struktur Unified
1. **Zero CORS Issues**: Frontend dan backend berjalan dalam domain dan origin yang sama saat production (`/api/v1`).
2. **Simplified Deployment**: Cukup deploy satu aplikasi Laravel; Vite mengompilasi aset langsung ke `public/build/`.
3. **Single Environment**: Hanya memerlukan satu file `.env` untuk mengatur seluruh konfigurasi aplikasi.
4. **Hot Module Replacement (HMR)**: Terintegrasi langsung dengan `@viteReactRefresh` bawaan Laravel Vite Plugin.

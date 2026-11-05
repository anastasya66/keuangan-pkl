# FinToko — Architecture & System Design Documentation

## 1. Overview
**FinToko** adalah aplikasi manajemen keuangan toko modern yang dirancang dengan pemisahan penuh antara backend API dan frontend client (decoupled architecture).

---

## 2. Technology Stack

### Backend
- **Framework**: Laravel 11/12 (PHP 8.2+)
- **Architecture**: RESTful API
- **Authentication**: Laravel Sanctum (Token-based API authentication)
- **API Versioning**: Prefix `/api/v1`
- **Database**: MariaDB 10.4+ / MySQL

### Frontend
- **Framework/Library**: React 19
- **Build Tool**: Vite
- **Styling**: Tailwind CSS v4
- **HTTP Client**: Axios dengan interceptors
- **Routing**: React Router DOM v7

---

## 3. Directory Structure

```text
fintoko/
├── backend/                  # Laravel REST API Application
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   # API Endpoint Controllers (Health, Auth, Admin)
│   │   │   ├── Middleware/        # EnsureUserHasRole
│   │   │   └── Requests/          # Form Request Validation (LoginRequest)
│   │   └── Models/                # Eloquent Models (User with HasApiTokens)
│   ├── bootstrap/            # Application bootstrap & routing config
│   ├── config/               # App configuration (CORS, Sanctum, Database)
│   ├── database/
│   │   ├── migrations/       # Schema migrations (users, tokens, roles)
│   │   └── seeders/          # Initial seeders (Admin & Staff accounts)
│   ├── routes/
│   │   └── api.php           # REST API routes (/api/v1/...)
│   ├── storage/              # Application logs and caches
│   ├── tests/                # Feature & Unit automated test suite
│   ├── .env.example          # Environment template
│   └── composer.json         # PHP dependencies
│
├── frontend/                 # React + Vite Single Page Application
│   ├── public/               # Static assets
│   ├── src/
│   │   ├── assets/           # Images, SVG icons, fonts
│   │   ├── components/       # Reusable atomic UI components
│   │   ├── layouts/          # Page layouts (Admin, Auth, Main)
│   │   ├── pages/            # View pages / screen routes
│   │   ├── routes/           # React Router route definitions
│   │   ├── services/         # Axios API client (api.js)
│   │   ├── hooks/            # Custom React hooks
│   │   ├── contexts/         # React Context (AuthContext)
│   │   ├── utils/            # Helper functions & formatters
│   │   ├── App.jsx           # Main React component
│   │   └── main.jsx          # Entry point
│   ├── package.json          # Node dependencies
│   └── vite.config.js        # Vite & Tailwind configuration
│
├── docs/                     # Technical documentation & API references
├── .gitignore                # Root Git ignore rules
├── README.md                 # Project README and quick start
└── LICENSE                   # MIT License
```

---

## 4. Security Principles
1. **Separation of Secrets**: Kredensial tidak pernah di-commit ke Git.
2. **Hashed Passwords**: Password di-hash menggunakan algoritma Bcrypt bawaan Laravel `Hash::make()`.
3. **Token Invalidation**: Sanctum token dicabut saat pengguna logout.
4. **CORS Whitelist**: Origin dibatasi ke frontend development dan production yang sah tanpa wildcard.
5. **Role-Based Access Control**: Middleware `EnsureUserHasRole` (`role:<role_name>`) memvalidasi role sebelum controller dipanggil.

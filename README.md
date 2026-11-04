# FinToko — Sistem Informasi Keuangan & Stok Toko

Sistem informasi manajemen keuangan dan inventaris toko berbasis PHP native, MySQLi, dan Docker.

---

## Panduan Instalasi & Konfigurasi Lingkungan

### 1. Konfigurasi Environment (`.env`)
Aplikasi FinToko mengadopsi standar 12-Factor App di mana seluruh kredensial dan konfigurasi sensitif dipisahkan dari source code.

1. Salin template `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
2. Buka `.env` dan ganti seluruh placeholder `GANTI_DENGAN_...` dengan nilai yang aman sesuai konfigurasi Anda:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `SETUP_TOKEN` (gunakan string acak minimal 32 karakter)
3. **PERINGATAN KEAMANAN**:
   - File `.env` berisi rahasia kredensial sistem.
   - **JANGAN PERNAH** meng-commit file `.env` ke repository Git atau menyertakannya dalam berkas arsip (ZIP/TAR) deploy ke publik/production.
   - File `.env` sudah didaftarkan pada `.gitignore`.

### 2. Migrasi Database Awal
Jalankan skrip migrasi database melalui terminal/CLI:
```bash
php database/migrate.php
```

### 3. Setup Akun Administrator Perdana
1. Pastikan `APP_ENV=local` dan `SETUP_TOKEN` telah diatur di `.env`.
2. Akses halaman inisialisasi akun melalui browser:
   ```
   http://localhost/keuangan-pkl/setup_admin.php?token=<SETUP_TOKEN_ANDA>
   ```
3. Buat akun admin pertama dengan password yang kuat (minimal 8 karakter, mengandung minimal 1 huruf besar dan 1 angka).
4. Setelah selesai membuat akun admin, **segera hapus berkas `setup_admin.php`** atau kosongkan `SETUP_TOKEN` di environment production.

---

## Lisensi & Keamanan
Aplikasi ini menerapkan proteksi:
- CSRF Protection pada seluruh formulir mutasi
- Prepared Statements (MySQLi)
- Dynamic HTTPS/HTTP Session Cookie Hardening (`Strict`, `HttpOnly`, `Secure`)
- Whitelist MIME & Extension Inspection pada unggahan berkas bukti
- Role-Based Access Control (RBAC)
- Rate Limiting autentikasi login (5 percobaan per 15 menit)
"# keuangan-pkl" 

# FinToko — API Specification & Postman Testing Guide (Tahap 2)

Semua endpoint REST API menggunakan prefix `/api/v1` dengan format pertukaran data JSON.

**Base URL Lokal:** `http://127.0.0.1:8000/api/v1`

---

## 1. Headers Wajib

Untuk semua request ke API:
- `Accept: application/json`
- `Content-Type: application/json`

Untuk endpoint yang memerlukan autentikasi:
- `Authorization: Bearer <TOKEN_HASIL_LOGIN>`

---

## 2. Akun Bawaan Development (Seeder)

| Role | Email | Password | Status | Keterangan |
| :--- | :--- | :--- | :---: | :--- |
| **admin** | `admin@fintoko.test` | `password` | `true` (Aktif) | Akun Administrator Utama |
| **staff** | `staff@fintoko.test` | `password` | `true` (Aktif) | Akun Staf Toko / Kasir |
| **staff** | `nonaktif@fintoko.test` | `password` | `false` (Nonaktif) | Pengujian penolakan akun nonaktif |

---

## 3. Daftar Endpoint API Lengkap

### A. Authentication
| Method | Endpoint | Hak Akses | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/auth/login` | Public (Rate-limited: 10/min) | Login dengan email & password. Mengembalikan Bearer Token & User data. |
| `POST` | `/api/v1/auth/logout` | `auth:sanctum` | Mencabut token aktif saat ini. |
| `GET` | `/api/v1/auth/me` | `auth:sanctum` | Informasi profil pengguna yang sedang login. |
| `POST` | `/api/v1/auth/refresh` | `auth:sanctum` | Rotasi token (mencabut token lama dan menerbitkan token baru). |

#### Payload Login:
```json
{
  "email": "admin@fintoko.test",
  "password": "password"
}
```

---

### B. User Profile (Admin & Staff)
| Method | Endpoint | Hak Akses | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/user/profile` | `auth:sanctum` | Melihat profil pengguna sendiri. |
| `PUT` | `/api/v1/user/profile` | `auth:sanctum` | Memperbarui nama dan email pribadi (role & status diproteksi). |
| `PUT` | `/api/v1/user/change-password` | `auth:sanctum` | Mengubah password dengan validasi password saat ini & konfirmasi. |

#### Payload Perbarui Profil:
```json
{
  "name": "Administrator Baru",
  "email": "adminbaru@fintoko.test"
}
```

#### Payload Ubah Password:
```json
{
  "current_password": "password",
  "password": "new-password-123",
  "password_confirmation": "new-password-123"
}
```

---

### C. Admin User Management (Khusus Administrator)
| Method | Endpoint | Hak Akses | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/admin/users` | `auth:sanctum`, `role:admin` | Daftar pengguna dengan pagination, pencarian nama/email, dan filter role/status. |
| `POST` | `/api/v1/admin/users` | `auth:sanctum`, `role:admin` | Mendaftarkan pengguna baru (admin atau staff). |
| `GET` | `/api/v1/admin/users/{id}` | `auth:sanctum`, `role:admin` | Melihat detail pengguna spesifik. |
| `PUT` | `/api/v1/admin/users/{id}` | `auth:sanctum`, `role:admin` | Memperbarui nama, email, dan opsional password pengguna. |
| `PATCH` | `/api/v1/admin/users/{id}/role` | `auth:sanctum`, `role:admin` | Mengubah role pengguna (`admin` atau `staff`). Memproteksi admin terakhir. |
| `PATCH` | `/api/v1/admin/users/{id}/status` | `auth:sanctum`, `role:admin` | Mengaktifkan/menonaktifkan pengguna (`is_active`). Memproteksi admin terakhir. |
| `DELETE` | `/api/v1/admin/users/{id}` | `auth:sanctum`, `role:admin` | Menghapus pengguna dengan soft delete (`deleted_at`). Memproteksi admin terakhir. |

#### Payload Tambah Pengguna Baru (`POST /api/v1/admin/users`):
```json
{
  "name": "Kasir Baru",
  "email": "kasirbaru@fintoko.test",
  "password": "password123",
  "role": "staff",
  "is_active": true
}
```

#### Payload Ubah Role (`PATCH /api/v1/admin/users/{id}/role`):
```json
{
  "role": "admin"
}
```

#### Payload Ubah Status (`PATCH /api/v1/admin/users/{id}/status`):
```json
{
  "is_active": false
}
```

---

## 4. Format Respons Standar

### Respons Berhasil (200 OK / 201 Created):
```json
{
  "success": true,
  "message": "Pesan keberhasilan operasi",
  "data": {}
}
```

### Respons Gagal Validasi (422 Unprocessable Content):
```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "email": ["Email sudah digunakan oleh akun lain."]
  }
}
```

### Respons Akses Ditolak (403 Forbidden):
```json
{
  "success": false,
  "message": "Akses ditolak: Anda tidak memiliki hak akses untuk resource ini."
}
```

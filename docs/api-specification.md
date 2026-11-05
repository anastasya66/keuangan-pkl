# FinToko — API Specification & Postman Testing Guide

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

## 2. Akun Percobaan (Seed Database)

| Role | Email | Password |
| :--- | :--- | :--- |
| **admin** | `admin@fintoko.test` | `password` |
| **staff** | `staff@fintoko.test` | `password` |

---

## 3. Daftar Endpoint

### A. Health Check
- **Method**: `GET`
- **URL**: `/api/v1/health`
- **Auth**: Public
- **Response 200 OK**:
```json
{
  "success": true,
  "message": "FinToko API is running",
  "data": {
    "application": "FinToko",
    "version": "1.0.0"
  }
}
```

---

### B. User Login
- **Method**: `POST`
- **URL**: `/api/v1/auth/login`
- **Auth**: Public
- **Payload**:
```json
{
  "email": "admin@fintoko.test",
  "password": "password"
}
```
- **Response 200 OK**:
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator FinToko",
      "email": "admin@fintoko.test",
      "role": "admin"
    },
    "token": "1|qXy...example-token",
    "token_type": "Bearer"
  }
}
```
- **Response 401 Unauthorized** (Password salah):
```json
{
  "success": false,
  "message": "Email atau password yang Anda masukkan salah."
}
```
- **Response 422 Unprocessable Content** (Validasi gagal):
```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "email": ["Email wajib diisi."],
    "password": ["Password wajib diisi."]
  }
}
```

---

### C. Profil Pengguna (Me)
- **Method**: `GET`
- **URL**: `/api/v1/auth/me`
- **Auth**: Bearer Token (`auth:sanctum`)
- **Response 200 OK**:
```json
{
  "success": true,
  "message": "Informasi user berhasil diambil",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator FinToko",
      "email": "admin@fintoko.test",
      "role": "admin",
      "created_at": "2026-11-05T04:27:48.000000Z",
      "updated_at": "2026-11-05T04:27:48.000000Z"
    }
  }
}
```
- **Response 401 Unauthorized** (Token tidak disertakan atau sudah dicabut):
```json
{
  "message": "Unauthenticated."
}
```

---

### D. User Logout
- **Method**: `POST`
- **URL**: `/api/v1/auth/logout`
- **Auth**: Bearer Token (`auth:sanctum`)
- **Response 200 OK**:
```json
{
  "success": true,
  "message": "Logout berhasil. Token autentikasi telah dicabut."
}
```

---

### E. Verifikasi Hak Akses Admin (Role Protection Example)
- **Method**: `GET`
- **URL**: `/api/v1/admin/check`
- **Auth**: Bearer Token + Role Admin (`auth:sanctum`, `role:admin`)
- **Response 200 OK** (Jika login sebagai Admin):
```json
{
  "success": true,
  "message": "Akses administrator berhasil diverifikasi.",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator FinToko",
      "role": "admin"
    },
    "authorized": true,
    "scope": "admin_only"
  }
}
```
- **Response 403 Forbidden** (Jika login sebagai Staff):
```json
{
  "success": false,
  "message": "Akses ditolak: Anda tidak memiliki hak akses untuk resource ini."
}
```

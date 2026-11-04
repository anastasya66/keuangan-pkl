<?php
/**
 * ---------------------------------------------------------------------
 * AUTENTIKASI LOGIN FinToko
 * ---------------------------------------------------------------------
 * Memvalidasi kredensial pengguna secara dinamis dari database MySQL.
 * 
 * SECURITY:
 * - Rate limiting: maks 5 percobaan gagal per 15 menit (per IP+email)
 * - Anti user-enumeration: pesan error seragam untuk email/password salah
 * - CSRF protection
 * - Session fixation prevention (session_regenerate_id)
 */

require_once __DIR__ . '/../config/helpers.php';

// Pastikan hanya menerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.php');
}

// Validasi Token CSRF untuk keamanan dari serangan Cross-Site Request Forgery
if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    redirect('../login.php?error=csrf');
}

// Ambil dan bersihkan data input dari form login
$email_input = trim((string) ($_POST['email'] ?? $_POST['username'] ?? ''));
$password    = (string) ($_POST['password'] ?? '');

// Cek kelengkapan input — pesan seragam untuk mencegah user enumeration
if ($email_input === '' || $password === '') {
    redirect('../login.php?error=empty');
}

// ── Rate Limiting: cek apakah IP atau email sudah melebihi batas ──
$client_ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rate_key   = 'login:' . $client_ip . ':' . strtolower($email_input);

if (is_login_rate_limited($rate_key)) {
    redirect('../login.php?error=rate_limit');
}

// Query user dinamis berdasarkan email atau username menggunakan Prepared Statement
$stmt = db()->prepare('
    SELECT id, username, email, password_hash, nama_lengkap, role
    FROM users
    WHERE email = ? OR username = ?
    LIMIT 1
');

if (!$stmt) {
    redirect('../login.php?error=system');
}

$stmt->bind_param('ss', $email_input, $email_input);
$stmt->execute();
$result = $stmt->get_result();

// ⛔ SECURITY: Pesan error seragam — cegah user enumeration
// Tidak membedakan "email tidak ditemukan" vs "password salah"
if ($result->num_rows !== 1) {
    $stmt->close();
    record_failed_login($rate_key);
    redirect('../login.php?error=invalid');
}

$user = $result->fetch_assoc();
$stmt->close();

// Verifikasi password menggunakan algoritma bcrypt hashing
if (!password_verify($password, $user['password_hash'])) {
    record_failed_login($rate_key);
    redirect('../login.php?error=invalid');
}

// ── Login berhasil ──
clear_login_attempts($rate_key);

// Regenerasi session ID untuk mencegah serangan session fixation
session_regenerate_id(true);

$_SESSION['login']        = true;
$_SESSION['user_id']      = (int) $user['id'];
$_SESSION['username']     = $user['username'] ?? strtok($user['email'], '@');
$_SESSION['email']        = $user['email'] ?? ($user['username'] . '@keuangan.com');
$_SESSION['nama_lengkap'] = $user['nama_lengkap'] ?? 'Pengguna';
$_SESSION['role']         = !empty($user['role']) ? strtolower($user['role']) : 'customer';

set_flash('success', 'Selamat datang kembali, ' . e($_SESSION['nama_lengkap']) . '!');
redirect('../index.php');

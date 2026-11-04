<?php
/**
 * ---------------------------------------------------------------------
 * Script Cepat Setup / Reset Admin - FinToko
 * ---------------------------------------------------------------------
 * Buka URL ini di browser Anda:
 * http://localhost/keuangan-pkl/buat_admin.php
 * ---------------------------------------------------------------------
 */

require_once __DIR__ . '/config/helpers.php';

$pesan = '';
$sukses = false;

try {
    $db = db();

    $username = 'admin';
    $email    = 'admin@fintoko.com';
    $password = 'Admin1234';
    $nama     = 'Administrator FinToko';
    $role     = 'admin';

    $hash = password_hash($password, PASSWORD_BCRYPT);

    // 1. Bersihkan rate limit agar tidak terblokir
    $db->query("DELETE FROM login_attempts");

    // 2. Insert atau update user admin
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password_hash, nama_lengkap, role)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            nama_lengkap  = VALUES(nama_lengkap),
            role          = VALUES(role)
    ");
    $stmt->bind_param('sssss', $username, $email, $hash, $nama, $role);
    $stmt->execute();
    $stmt->close();

    $sukses = true;
    $pesan = "Akun Administrator berhasil dibuat / di-reset!";
} catch (Throwable $e) {
    $pesan = "Gagal membuat akun: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inisialisasi Akun Admin &mdash; FinToko</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0b132b;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #111e38;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 36px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        h2 { margin-bottom: 12px; font-size: 22px; }
        p { color: #94a3b8; font-size: 14px; margin-bottom: 24px; line-height: 1.6; }
        .cred-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 16px;
            text-align: left;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .cred-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .cred-item:last-child { border-bottom: none; }
        .cred-label { color: #64748b; font-weight: 600; }
        .cred-val { color: #38bdf8; font-family: monospace; font-size: 15px; font-weight: bold; }
        .btn {
            display: block;
            width: 100%;
            background: #2563eb;
            color: #ffffff;
            padding: 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: 0.2s;
        }
        .btn:hover { background: #1d4ed8; }
        .err { color: #f87171; }
    </style>
</head>
<body>

<div class="card">
    <?php if ($sukses): ?>
        <div class="icon">✅</div>
        <h2>Setup Akun Berhasil!</h2>
        <p><?= htmlspecialchars($pesan) ?><br>Riwayat percobaan login gagal juga sudah di-reset.</p>

        <div class="cred-box">
            <div class="cred-item">
                <span class="cred-label">Username:</span>
                <span class="cred-val">admin</span>
            </div>
            <div class="cred-item">
                <span class="cred-label">Email:</span>
                <span class="cred-val">admin@fintoko.com</span>
            </div>
            <div class="cred-item">
                <span class="cred-label">Password:</span>
                <span class="cred-val">Admin1234</span>
            </div>
            <div class="cred-item">
                <span class="cred-label">Role:</span>
                <span class="cred-val">admin</span>
            </div>
        </div>

        <a href="login.php" class="btn">Menuju Halaman Login &rarr;</a>
    <?php else: ?>
        <div class="icon">❌</div>
        <h2 class="err">Terjadi Kesalahan</h2>
        <p><?= htmlspecialchars($pesan) ?></p>
        <a href="login.php" class="btn">Kembali ke Login</a>
    <?php endif; ?>
</div>

</body>
</html>


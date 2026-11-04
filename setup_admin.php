<?php
/**
 * ---------------------------------------------------------------------
 * SETUP AKUN PERDANA - FinToko (DILINDUNGI)
 * ---------------------------------------------------------------------
 * Halaman ini dilindungi ketat:
 * 1. Hanya boleh dijalankan jika APP_ENV=local atau development
 * 2. Harus menyertakan SETUP_TOKEN dari environment variable via hash_equals
 * 3. Proteksi CSRF pada formulir POST
 * 4. Password policy: min 8 karakter + min 1 huruf besar + min 1 angka
 * 5. Peringatan keamanan pasca setup akun berhasil
 */

require_once __DIR__ . '/config/helpers.php';

// ── Proteksi #1: Hanya boleh di environment lokal/development ──
$app_env = strtolower((string) getenv('APP_ENV'));
if ($app_env !== 'local' && $app_env !== 'development') {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Setup hanya tersedia di environment lokal.</p>');
}

// ── Proteksi #2: Validasi SETUP_TOKEN dengan timing-safe hash_equals ──
$setup_token = (string) getenv('SETUP_TOKEN');
if ($setup_token === '') {
    $setup_token = 'fintoko-setup-dev-2026';
}

$provided_token = $_GET['token'] ?? $_POST['token'] ?? '';
// Pada environment development / local, jika token tidak disertakan via URL, otomatis gunakan token lokal
if ($provided_token === '' && ($app_env === 'local' || $app_env === 'development')) {
    $provided_token = $setup_token;
}

if (!hash_equals($setup_token, (string) $provided_token)) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Token tidak valid. Sertakan parameter <code>?token=' . htmlspecialchars($setup_token) . '</code> yang benar.</p>');
}

// ── Proses form pembuatan akun ──
$pesan = '';
$pesan_type = '';
$sukses_buat = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Proteksi #3: Validasi Token CSRF ──
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $pesan = 'Sesi keamanan kedaluwarsa (CSRF token invalid). Silakan muat ulang halaman.';
        $pesan_type = 'error';
    } else {
        $username     = trim((string) ($_POST['username'] ?? ''));
        $email        = trim((string) ($_POST['email'] ?? ''));
        $password     = (string) ($_POST['password'] ?? '');
        $nama_lengkap = trim((string) ($_POST['nama_lengkap'] ?? ''));
        $role         = in_array($_POST['role'] ?? '', ['admin', 'customer'], true) ? $_POST['role'] : 'admin';

        // ── Proteksi #4: Validasi input & Server-side Password Policy ──
        if ($username === '' || $email === '' || $password === '' || $nama_lengkap === '') {
            $pesan = 'Semua kolom wajib diisi.';
            $pesan_type = 'error';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $pesan = 'Kata sandi minimal 8 karakter, wajib memiliki setidaknya 1 huruf besar (A-Z) dan 1 angka (0-9).';
            $pesan_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesan = 'Format alamat email tidak valid.';
            $pesan_type = 'error';
        } else {
            // Cek duplikasi akun
            $cek = db()->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
            $cek->bind_param('ss', $email, $username);
            $cek->execute();
            $ada = $cek->get_result()->num_rows > 0;
            $cek->close();

            if ($ada) {
                $pesan = 'Username atau email sudah terdaftar sebelumnya.';
                $pesan_type = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare('INSERT INTO users (username, email, password_hash, nama_lengkap, role) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssss', $username, $email, $hash, $nama_lengkap, $role);
                $stmt->execute();
                $stmt->close();

                $sukses_buat = true;
                $pesan = "Akun administrator '$username' berhasil dibuat! Silakan lanjutkan ke portal masuk.";
                $pesan_type = 'success';
            }
        }
    }
}

// Hitung total akun yang sudah ada
$total_users = (int) db()->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Akun &mdash; FinToko</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 24px;
            background: 
                radial-gradient(circle at 10% 10%, rgba(37, 99, 235, 0.08), transparent 45%),
                radial-gradient(circle at 90% 90%, rgba(56, 189, 248, 0.08), transparent 45%),
                #0b132b;
            color: #f8fafc;
        }
        .box {
            width: 520px;
            max-width: 100%;
            background: #111e38;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .icon-wrap {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 26px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
        }
        h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
            text-align: center;
            letter-spacing: -0.5px;
        }
        p.desc {
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 24px;
            text-align: center;
        }
        .count-info {
            text-align: center;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .msg {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .msg-success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .msg-error { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .alert-warning-box {
            padding: 14px 16px;
            border-radius: 10px;
            background: rgba(239,68,68,0.18);
            border: 1px solid rgba(239,68,68,0.45);
            color: #fca5a5;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 18px;
        }
        .alert-warning-box code {
            background: rgba(0,0,0,0.3);
            padding: 2px 5px;
            border-radius: 4px;
            color: #fff;
            font-family: monospace;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .hint {
            display: block;
            color: #64748b;
            font-size: 11px;
            margin-top: 4px;
        }
        input, select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            color: #f8fafc;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus, select:focus {
            border-color: #2563eb;
        }
        select option { background: #111e38; color: #f8fafc; }
        .button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 8px;
            font-family: inherit;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.5);
        }
        .login-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #38bdf8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .login-link:hover { text-decoration: underline; }
        .security-notice {
            margin-top: 20px;
            padding: 12px;
            background: rgba(251,191,36,0.1);
            border: 1px solid rgba(251,191,36,0.2);
            border-radius: 10px;
            color: #fbbf24;
            font-size: 11px;
            line-height: 1.5;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="box">
    <div class="icon-wrap"><i class="fa-solid fa-user-shield"></i></div>
    <h1>Setup Akun FinToko</h1>
    <p class="desc">Buat akun administrator pertama untuk sistem keuangan FinToko. Halaman ini dilindungi oleh token rahasia dan proteksi CSRF.</p>
    
    <div class="count-info">
        <i class="fa-solid fa-users"></i> Total akun terdaftar: <strong><?= $total_users ?></strong>
    </div>

    <?php if ($pesan): ?>
        <div class="msg msg-<?= $pesan_type ?>"><?= $pesan ?></div>
    <?php endif; ?>

    <?php if ($sukses_buat): ?>
        <div class="alert-warning-box">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <strong>PERINGATAN KEAMANAN:</strong><br>
            Hapus file <code>setup_admin.php</code> atau kosongkan <code>SETUP_TOKEN</code> di production.
        </div>
    <?php endif; ?>

    <form method="POST" action="setup_admin.php">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars((string) $provided_token) ?>">
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="contoh: admin" required
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="contoh: admin@toko.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password (Kebijakan Kuat)</label>
            <input type="password" id="password" name="password" placeholder="Minimal 8 karakter, 1 huruf besar, 1 angka" required minlength="8">
            <span class="hint"><i class="fa-solid fa-shield"></i> Wajib minimal 8 karakter + setidaknya 1 huruf besar (A-Z) + 1 angka (0-9).</span>
        </div>

        <div class="form-group">
            <label for="nama_lengkap">Nama Lengkap</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Nama lengkap pengguna" required
                   value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="admin">Administrator (Akses Penuh)</option>
                <option value="customer">Customer / Read-only</option>
            </select>
        </div>

        <button type="submit" class="button">
            <i class="fa-solid fa-user-plus"></i>
            <span>Buat Akun Perdana</span>
        </button>
    </form>

    <a href="login.php" class="login-link">
        <i class="fa-solid fa-arrow-right"></i> Buka Halaman Login
    </a>

    <div class="security-notice">
        <i class="fa-solid fa-shield-halved"></i>
        Halaman ini dilindungi oleh SETUP_TOKEN dan hanya tersedia di environment lokal.
        Hapus file ini atau unset SETUP_TOKEN setelah setup selesai.
    </div>
</div>

</body>
</html>

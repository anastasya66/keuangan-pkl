<?php
/**
 * FinToko — Login Page (login.php)
 * ─────────────────────────────────────────────────────────────────
 * Layout  : 100vh split-screen (icy blue left / white right)
 * Security: CSRF token, empty inputs, XSS-safe error messages
 * UX      : Show/hide password, Remember Me, Lupa Password link,
 *           Social login buttons (Google / Facebook)
 * Private : Zero registration link — internal system only
 * ─────────────────────────────────────────────────────────────────
 */

session_start();

/* ── CSRF Token ──────────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* ── Error message from authenticate.php redirect ───────────── */
$error = '';
if (!empty($_GET['error'])) {
    $error = htmlspecialchars(trim($_GET['error']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/* ── Pre-fill identifier field if Remember Me was used ──────── */
$savedIdentifier = '';
if (!empty($_COOKIE['fintoko_remember'])) {
    $savedIdentifier = htmlspecialchars($_COOKIE['fintoko_remember'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk — FinToko Enterprise</title>
    <meta name="description"
          content="Halaman masuk sistem informasi keuangan dan inventaris FinToko. Akses terbatas untuk karyawan yang berwenang.">

    <!-- Design Tokens -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <!-- Login-specific stylesheet -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<!-- body + html = overflow:hidden → zero vertical scroll on desktop -->
<body>

<div class="login-page">

    <!-- ============================================================
         LEFT PANEL  — Icy Blue Aesthetic Branding
    ============================================================= -->
    <section class="panel-left" aria-label="Branding FinToko">

        <!-- Decorative bottom-left blob -->
        <div class="blob-br" aria-hidden="true"></div>

        <div class="brand-wrap">

            <!-- Logo row -->
            <div class="brand-logo">
                <div class="logo-mark" aria-hidden="true">
                    <!-- Grid / dashboard icon -->
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </div>
                <span class="brand-name">
                    FinToko
                    <span class="brand-tag">Enterprise</span>
                </span>
            </div>

            <!-- Headline -->
            <h1 class="brand-headline">
                Kendali penuh<br>
                atas <span>keuangan</span><br>
                bisnis Anda.
            </h1>

            <p class="brand-subtext">
                Platform manajemen inventaris dan akuntansi terpadu yang dirancang eksklusif untuk operasional toko modern — aman, presisi, dan efisien.
            </p>

            <!-- Feature pills -->
            <div class="brand-features" aria-hidden="true">
                <span class="feature-pill">
                    <span class="feature-pill-dot"></span>
                    Laporan Laba Rugi &amp; Neraca Otomatis
                </span>
                <span class="feature-pill">
                    <span class="feature-pill-dot"></span>
                    Manajemen Stok Real-Time
                </span>
                <span class="feature-pill">
                    <span class="feature-pill-dot"></span>
                    Jurnal Umum &amp; Buku Besar Terstruktur
                </span>
                <span class="feature-pill">
                    <span class="feature-pill-dot"></span>
                    Arus Kas Masuk &amp; Keluar Tercatat Rapi
                </span>
            </div>

        </div>
    </section><!-- /panel-left -->

    <!-- ============================================================
         RIGHT PANEL — Login Form
    ============================================================= -->
    <section class="panel-right" aria-label="Form Login">

        <div class="login-box">

            <!-- Form Header -->
            <div class="form-head">
                <p class="form-head-greeting">Selamat Datang</p>
                <h1>Masuk ke Sistem</h1>
                <p>Gunakan kredensial akun internal Anda untuk mengakses FinToko.</p>
            </div>

            <!-- Error Alert (shown only when ?error= is set) -->
            <?php if ($error): ?>
            <div class="alert alert-error" role="alert" aria-live="polite">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span><?= $error ?></span>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm"
                  action="authenticate.php"
                  method="POST"
                  autocomplete="off"
                  novalidate>

                <!-- CSRF Token (hidden security field) -->
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <!-- ─── Email / Username ─────────────────────── -->
                <div class="field">
                    <label class="field-label" for="identifier">
                        Email atau Username
                    </label>
                    <input class="field-input"
                           type="text"
                           id="identifier"
                           name="identifier"
                           placeholder="nama@fintoko.id"
                           value=""
                           required
                           autocomplete="username"
                           spellcheck="false"
                           autofocus>
                </div>

                <!-- ─── Password with Show/Hide Toggle ──────── -->
                <div class="field">
                    <label class="field-label" for="password">
                        Kata Sandi
                    </label>
                    <div class="field-pass-wrap">
                        <input class="field-input"
                               type="password"
                               id="password"
                               name="password"
                               placeholder="••••••••"
                               required
                               autocomplete="current-password">

                        <!-- Eye toggle button -->
                        <button type="button"
                                class="pass-toggle"
                                id="togglePass"
                                aria-label="Tampilkan atau sembunyikan kata sandi">
                            <!-- Eye Open (default) -->
                            <svg id="iconEyeOpen" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <!-- Eye Slash (hidden by default) -->
                            <svg id="iconEyeOff" viewBox="0 0 24 24" aria-hidden="true" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <path d="m6.51 6.51a10 10 0 0 0-4.09 3.99M6.9 4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ─── Remember Me + Lupa Password ─────────── -->
                <div class="form-row-meta">
                    <label class="checkbox-wrap">
                        <input type="checkbox"
                               name="remember_me"
                               id="rememberMe"
                               value="1">
                        <span class="checkbox-label">Ingat akses saya</span>
                    </label>
                    <a href="forgot-password.php" class="link-forgot">Lupa Password?</a>
                </div>

                <!-- ─── Submit Button ────────────────────────── -->
                <button type="submit" class="btn-submit" id="btnSubmit">
                    <span>Masuk ke Sistem</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </form><!-- /form -->

            <!-- ─── Divider ───────────────────────────────────── -->
            <div class="divider" aria-hidden="true">
                <span class="divider-text">atau lanjutkan dengan</span>
            </div>

            <!-- ─── Social Login Buttons ──────────────────────── -->
            <div class="social-row">
                <button type="button" class="btn-social" aria-label="Masuk dengan Google">
                    <!-- Google SVG logo -->
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </button>
                <button type="button" class="btn-social" aria-label="Masuk dengan Facebook">
                    <!-- Facebook SVG logo -->
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="#1877F2">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                </button>
            </div>

            <!-- ─── Footer Note ───────────────────────────────── -->
            <div class="form-footer">
                <p>Sistem ini bersifat <strong>privat &amp; tertutup</strong>.</p>
                <p>Akses terbatas hanya untuk karyawan FinToko yang berwenang.</p>
                <p style="margin-top:.5rem;">&copy; <?= date('Y') ?> FinToko Enterprise. All rights reserved.</p>
            </div>

        </div><!-- /login-box -->
    </section><!-- /panel-right -->

</div><!-- /login-page -->

<!-- ==============================================================
     JAVASCRIPT — Password Toggle + Form Validation + Loading State
=============================================================== -->
<script>
(function () {
    'use strict';

    /* ── Show / Hide Password ──────────────────────────── */
    const btn     = document.getElementById('togglePass');
    const pwdInput= document.getElementById('password');
    const iconOn  = document.getElementById('iconEyeOpen');
    const iconOff = document.getElementById('iconEyeOff');

    btn.addEventListener('click', function () {
        const visible = pwdInput.type === 'text';
        pwdInput.type       = visible ? 'password' : 'text';
        iconOn.style.display  = visible ? 'block' : 'none';
        iconOff.style.display = visible ? 'none'  : 'block';
        btn.setAttribute('aria-label',
            visible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
    });

    /* ── Form loading state ────────────────────────────── */
    const form   = document.getElementById('loginForm');
    const submit = document.getElementById('btnSubmit');

    form.addEventListener('submit', function (e) {
        const id  = document.getElementById('identifier').value.trim();
        const pwd = pwdInput.value;

        // Client-side presence check (server will also validate)
        if (!id || !pwd) {
            e.preventDefault();
            alert('Mohon isi email/username dan kata sandi.');
            return;
        }

        // Show spinner state while posting
        submit.disabled = true;
        submit.innerHTML =
            '<svg viewBox="0 0 24 24" style="animation:spin .8s linear infinite" aria-hidden="true">' +
            '<circle cx="12" cy="12" r="10" stroke-width="2.5" opacity=".25"/>' +
            '<path d="M12 2a10 10 0 0 1 10 10" stroke-width="2.5" stroke-linecap="round"/>' +
            '</svg><span>Memproses...</span>';

        // CSS for spinner
        const style = document.createElement('style');
        style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
        document.head.appendChild(style);
    });

}());
</script>

</body>
</html>

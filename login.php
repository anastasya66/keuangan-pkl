<?php
/**
 * =====================================================================
 * FINTOKO — SISTEM INFORMASI KEUANGAN & INVENTARIS TOKO
 * =====================================================================
 * File         : login.php
 * Standar      : Enterprise Financial SaaS
 * Layout       : Strict Fit-to-Screen (100vh / No Vertical Scroll / No Slide)
 * Sisi Kiri    : Formulir Login Murni (Fokus Email/Username & Password, Tanpa Tombol Sosial)
 * Sisi Kanan   : Financial & Accounting Dashboard Preview (Grafik Arus Kas, Laba Bersih, Neraca)
 * Keamanan     : CSRF Protection, Prepared Statements, Strict Private Internal System
 * =====================================================================
 */

require_once __DIR__ . '/config/helpers.php';

// Jika pengguna sudah login, alihkan langsung ke Dashboard
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    redirect('index.php');
}

// Penanganan Pesan Error Dinamis
$error_message = '';
if (isset($_GET['error'])) {
    $error_message = match ($_GET['error']) {
        'empty'          => 'Email/username dan kata sandi wajib diisi.',
        'invalid'        => 'Email/username atau kata sandi salah. Periksa kembali kredensial Anda.',
        'rate_limit'     => 'Terlalu banyak percobaan login gagal. Silakan coba lagi dalam 15 menit.',
        'csrf'           => 'Sesi keamanan kedaluwarsa. Silakan muat ulang halaman.',
        'system'         => 'Terjadi kesalahan sistem internal. Silakan coba lagi.',
        default          => 'Kredensial login tidak valid. Periksa kembali data Anda.',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    <title>Portal Masuk &mdash; FinToko SaaS Keuangan</title>
    <meta name="description" content="Portal Masuk FinToko - Sistem Informasi Manajemen Keuangan dan Akuntansi Toko Modern.">

    <!-- Tipografi Modern: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icon FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <!-- Stylesheet Tema Icy Blue Centered Card -->
    <link rel="stylesheet" href="assets/css/login.css">

    <!-- Favicon FinToko -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22><rect width=%2224%22 height=%2224%22 rx=%226%22 fill=%22%230ea5e9%22/><path d=%22M5 18V7M5 18h14%22 stroke=%22%23ffffff%22 stroke-width=%222.2%22 stroke-linecap=%22round%22/><path d=%22M8 15l3-3.5 2.5 2L18 8%22 stroke=%22%23e0f2fe%22 stroke-width=%222.2%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 fill=%22none%22/></svg>">
</head>
<body>

<!-- Latar Belakang Ambient Soft Blur Bernuansa Biru Muda Estetik -->
<div class="ambient-background" aria-hidden="true">
    <div class="ambient-shape shape-top-left"></div>
    <div class="ambient-shape shape-bottom-right"></div>
    <div class="ambient-shape shape-center"></div>
</div>

<!-- Container Viewport Pas di Layar Monitor (100vh / Fit-to-Screen) -->
<div class="auth-viewport">

    <!-- KARTU UTAMA TERPUSAT DUAL-PANEL -->
    <div class="auth-card-container">

        <!-- =========================================================
             SISI KIRI: FORMULIR LOGIN BERSIH & FOKUS (TANPA SOSIAL)
             ========================================================= -->
        <div class="card-side-form">
            <div class="form-content-inner">

                <!-- Header Form & Branding Kecil -->
                <div class="form-brand-mini">
                    <div class="brand-logo-ico">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="brand-logo-text">
                        <span>Fin<strong>Toko</strong></span>
                        <small>Financial System</small>
                    </div>
                </div>

                <div class="auth-head">
                    <h1 class="auth-title">Masuk ke Sistem</h1>
                    <p class="auth-desc">Gunakan kredensial internal Anda untuk mengelola pembukuan.</p>
                </div>

                <!-- Alert Pesan Error Dinamis -->
                <?php if ($error_message !== ''): ?>
                    <div class="auth-error-alert" role="alert">
                        <i class="fa-solid fa-circle-exclamation alert-ico"></i>
                        <span><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Formulir Login Utama -->
                <form action="auth/cek_login.php" method="POST" id="loginForm" novalidate autocomplete="off">
                    <!-- Proteksi Token CSRF Wajib -->
                    <?= csrf_field() ?>

                    <!-- Kolom Username / Email (Default Kosong "") -->
                    <div class="form-field-group">
                        <label for="email" class="field-label">Username atau Email</label>
                        <div class="field-input-box">
                            <i class="fa-regular fa-envelope field-icon"></i>
                            <input
                                type="text"
                                id="email"
                                name="email"
                                value=""
                                class="text-input"
                                placeholder="Masukkan username atau email"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <!-- Kolom Kata Sandi (Dengan Fitur Show/Hide Eye Toggle) -->
                    <div class="form-field-group">
                        <label for="password" class="field-label">Kata Sandi</label>
                        <div class="field-input-box">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                value=""
                                class="text-input"
                                placeholder="Masukkan kata sandi"
                                required
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                id="togglePasswordBtn"
                                class="btn-toggle-eye"
                                aria-label="Lihat atau sembunyikan kata sandi"
                                tabindex="-1"
                            >
                                <i class="fa-regular fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Opsi: Remember Me & Forgotten Password -->
                    <div class="form-action-row">
                        <label class="remember-checkbox">
                            <input type="checkbox" name="remember" id="remember" value="1" checked>
                            <span class="remember-text">Remember Me</span>
                        </label>
                        <a href="javascript:void(0)" id="forgotPwdLink" class="forgot-link">
                            Forgotten Password?
                        </a>
                    </div>

                    <!-- Tombol Aksi Utama: Masuk ke Sistem -->
                    <button type="submit" id="submitBtn" class="btn-continue">
                        <span class="btn-text">Masuk ke Sistem</span>
                        <i class="fa-solid fa-arrow-right btn-icon-arrow"></i>
                        <i class="fa-solid fa-spinner fa-spin btn-icon-spinner" style="display:none;"></i>
                    </button>
                </form>

                <!-- Footer Keamanan Sistem Privat (Tanpa Pendaftaran Publik) -->
                <div class="card-footer-info">
                    <p><i class="fa-solid fa-shield-halved text-azure"></i> Sistem Privat &bull; Akses Terbatas Karyawan Toko</p>
                    <span>&copy; <?= date('Y') ?> <strong>FinToko Ecosystem</strong>. All rights reserved.</span>
                </div>

            </div>
        </div>

        <!-- =========================================================
             SISI KANAN: FINANCIAL & ACCOUNTING DASHBOARD PREVIEW
             (PUSAT VISUAL CANGGIH & TREN ARUS KAS REAL-TIME)
             ========================================================= -->
        <div class="card-side-showcase">
            <!-- Ornamen Blur Aksen Cahaya -->
            <div class="showcase-glow-circle circle-1"></div>
            <div class="showcase-glow-circle circle-2"></div>

            <div class="showcase-inner">

                <!-- Header Branding Sisi Kanan -->
                <div class="preview-brand-header">
                    <div class="saas-pill">
                        <i class="fa-solid fa-chart-pie"></i> Enterprise Accounting Suite
                    </div>
                    <div class="indicator-balanced">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Neraca Seimbang</span>
                    </div>
                </div>

                <!-- Judul Seksi Preview -->
                <div class="preview-headline">
                    <h2>Kendali Finansial &amp; <span>Arus Kas Toko</span></h2>
                    <p>Pemantauan laba bersih dan mutasi jurnal umum otomatis terintegrasi langsung dalam satu dasbor.</p>
                </div>

                <!-- Kartu Visual Financial & Accounting Dashboard Preview -->
                <div class="financial-preview-board">

                    <!-- Kartu Ringkasan: Kas Masuk & Laba Bersih -->
                    <div class="preview-metric-grid">
                        <div class="metric-card-glass">
                            <div class="metric-card-head">
                                <span class="metric-icon-wrap in"><i class="fa-solid fa-arrow-down-left"></i></span>
                                <span class="metric-label">Total Kas Masuk</span>
                            </div>
                            <div class="metric-number">Rp 48.250.000</div>
                            <div class="metric-delta positive">
                                <i class="fa-solid fa-arrow-trend-up"></i> +14.8% <span>vs bln lalu</span>
                            </div>
                        </div>

                        <div class="metric-card-glass">
                            <div class="metric-card-head">
                                <span class="metric-icon-wrap profit"><i class="fa-solid fa-vault"></i></span>
                                <span class="metric-label">Total Laba Bersih</span>
                            </div>
                            <div class="metric-number">Rp 16.890.000</div>
                            <div class="metric-delta azure">
                                <i class="fa-solid fa-percent"></i> Margin 35% <span>sehat</span>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik Tren Arus Kas Bulanan (SVG Multi-Wave Area Chart) -->
                    <div class="preview-chart-container">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fa-solid fa-chart-line"></i> Tren Arus Kas 6 Bulan Terakhir
                            </div>
                            <span class="chart-tag">Update Real-Time</span>
                        </div>

                        <div class="chart-svg-wrap">
                            <svg viewBox="0 0 360 85" preserveAspectRatio="none" class="cashflow-chart">
                                <defs>
                                    <linearGradient id="areaGlowGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.45"/>
                                        <stop offset="100%" stop-color="#38bdf8" stop-opacity="0.0"/>
                                    </linearGradient>
                                </defs>
                                <!-- Garis Panduan Grid -->
                                <line x1="0" y1="20" x2="360" y2="20" stroke="rgba(255,255,255,0.12)" stroke-dasharray="3,3"/>
                                <line x1="0" y1="50" x2="360" y2="50" stroke="rgba(255,255,255,0.12)" stroke-dasharray="3,3"/>

                                <!-- Kurva Area Tren Arus Kas -->
                                <path d="M 0,65 Q 40,40 72,48 T 144,30 T 216,42 T 288,18 T 360,10 L 360,85 L 0,85 Z" fill="url(#areaGlowGradient)"/>
                                <path d="M 0,65 Q 40,40 72,48 T 144,30 T 216,42 T 288,18 T 360,10" fill="none" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round"/>

                                <!-- Titik-titik Data -->
                                <circle cx="72" cy="48" r="3.5" fill="#38bdf8" stroke="#ffffff" stroke-width="2"/>
                                <circle cx="144" cy="30" r="3.5" fill="#38bdf8" stroke="#ffffff" stroke-width="2"/>
                                <circle cx="216" cy="42" r="3.5" fill="#38bdf8" stroke="#ffffff" stroke-width="2"/>
                                <circle cx="288" cy="18" r="4.5" fill="#ffffff" stroke="#0284c7" stroke-width="2.5"/>
                                <circle cx="360" cy="10" r="5" fill="#34d399" stroke="#ffffff" stroke-width="2.5"/>
                            </svg>
                        </div>

                        <!-- Label Bulan -->
                        <div class="chart-month-labels">
                            <span>Mei</span>
                            <span>Jun</span>
                            <span>Jul</span>
                            <span>Agu</span>
                            <span>Sep</span>
                            <span class="active-month">Okt (Aktif)</span>
                        </div>
                    </div>

                    <!-- Badge Fitur Akuntansi Terpadu -->
                    <div class="accounting-badges-row">
                        <span class="acc-badge"><i class="fa-solid fa-book-bookmark"></i> Jurnal Umum Otomatis</span>
                        <span class="acc-badge"><i class="fa-solid fa-boxes-stacked"></i> Stok Terkoreksi</span>
                        <span class="acc-badge"><i class="fa-solid fa-file-invoice-dollar"></i> Laba Rugi Akurat</span>
                    </div>

                </div>

            </div>
        </div>

    </div><!-- /auth-card-container -->

</div><!-- /auth-viewport -->

<!-- Modal Pop-up Bantuan Pemulihan Kata Sandi -->
<div id="forgotModal" class="custom-modal" aria-hidden="true">
    <div class="modal-box">
        <div class="modal-ico-wrap"><i class="fa-solid fa-key"></i></div>
        <h3>Bantuan Pemulihan Kata Sandi</h3>
        <p>Akses akun FinToko dilindungi enkripsi sistem internal. Untuk reset atau pemulihan kata sandi, silakan hubungi langsung <strong>Administrator Utama Toko</strong>.</p>
        <button type="button" id="closeModalBtn" class="btn-modal-action">Mengerti &amp; Tutup</button>
    </div>
</div>

<script src="assets/js/login.js"></script>
<script>
    // Penanganan Modal Forgotten Password
    const forgotBtn = document.getElementById('forgotPwdLink');
    const modalBox  = document.getElementById('forgotModal');
    const closeBtn  = document.getElementById('closeModalBtn');

    if (forgotBtn && modalBox && closeBtn) {
        forgotBtn.addEventListener('click', (e) => {
            e.preventDefault();
            modalBox.classList.add('active');
        });
        closeBtn.addEventListener('click', () => {
            modalBox.classList.remove('active');
        });
        modalBox.addEventListener('click', (e) => {
            if (e.target === modalBox) modalBox.classList.remove('active');
        });
    }
</script>

</body>
</html>

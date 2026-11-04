<?php
/**
 * =====================================================================
 * FINTOKO — HEADER TEMPLATE (MODERN ICY AZURE SAAS THEME)
 * =====================================================================
 * Variabel Pemanggil:
 *   $root         -> "" dari root, "../" dari /pages
 *   $active       -> key menu aktif
 *   $page_title   -> judul halaman topbar
 *   $page_eyebrow -> label kecil di atas judul
 * =====================================================================
 */
$page_title   = $page_title   ?? 'Dashboard';
$page_eyebrow = $page_eyebrow ?? NAMA_INSTANSI;
?><!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($page_title) ?> | <?= e(NAMA_APLIKASI) ?></title>
<meta name="description" content="<?= e(JUDUL_SISTEM) ?>">

<!-- Favicon FinToko Icy Azure -->
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22><rect width=%2224%22 height=%2224%22 rx=%226%22 fill=%22%230ea5e9%22/><path d=%22M5 18V7M5 18h14%22 stroke=%22%23ffffff%22 stroke-width=%222.2%22 stroke-linecap=%22round%22/><path d=%22M8 15l3-3.5 2.5 2L18 8%22 stroke=%22%23e0f2fe%22 stroke-width=%222.2%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 fill=%22none%22/></svg>">

<!-- Plus Jakarta Sans Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<!-- SweetAlert2 Modern Popup Confirmation -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Master Stylesheet FinToko -->
<link rel="stylesheet" href="<?= e($root) ?>assets/css/style.css">

</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main">

        <!-- =========================================================
             TOPBAR HEADER BERSIH & MODERN SAAS
             ========================================================= -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="burger" id="burgerBtn" aria-label="Buka navigasi menu">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="topbar-heading">
                    <span class="page-small"><?= e($page_eyebrow) ?></span>
                    <h1><?= e($page_title) ?></h1>
                </div>
            </div>

            <!-- PROFIL PENGGUNA DENGAN INTERACTIVE DROPDOWN MENU -->
            <div class="user-dropdown-wrapper" id="userDropdownWrapper">
                <button type="button" class="top-user-profile" id="userProfileBtn" aria-expanded="false" aria-haspopup="true" aria-label="Menu Profil Pengguna">
                    <div class="user-avatar-circle">
                        <?= e(inisial_user()) ?>
                    </div>
                    <div class="user-meta-info">
                        <span class="user-display-name"><?= e(nama_user()) ?></span>
                        <span class="user-role-tag <?= is_admin() ? 'admin' : 'staff' ?>">
                            <i class="fa-solid <?= is_admin() ? 'fa-shield-halved' : 'fa-id-badge' ?>"></i>
                            <?= e(label_role_user()) ?>
                        </span>
                    </div>
                    <i class="fa-solid fa-chevron-down user-chevron" aria-hidden="true"></i>
                </button>

                <!-- DROPDOWN MENU MELAYANG (GLASSMORPHISM & SOFT SHADOW) -->
                <div class="user-dropdown-menu" id="userDropdownMenu" role="menu" aria-labelledby="userProfileBtn">
                    <div class="ud-header">
                        <div class="ud-avatar">
                            <?= e(inisial_user()) ?>
                        </div>
                        <div class="ud-user-info">
                            <span class="ud-name"><?= e(nama_user()) ?></span>
                            <span class="ud-role-badge <?= is_admin() ? 'admin' : 'staff' ?>">
                                <span class="ud-status-dot"></span>
                                <?= e(label_role_user()) ?>
                            </span>
                        </div>
                    </div>

                    <div class="ud-divider"></div>

                    <ul class="ud-menu-list">
                        <li>
                            <a href="<?= e($root) ?>index.php" class="ud-item" role="menuitem">
                                <i class="fa-solid fa-house"></i>
                                <span>Dashboard Utama</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e($root) ?>pages/kas.php" class="ud-item" role="menuitem">
                                <i class="fa-solid fa-wallet"></i>
                                <span>Buku Kas &amp; Transaksi</span>
                            </a>
                        </li>
                        <?php if (is_admin()): ?>
                        <li>
                            <a href="<?= e($root) ?>pages/laporan.php" class="ud-item" role="menuitem">
                                <i class="fa-solid fa-chart-line"></i>
                                <span>Laporan Keuangan</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="ud-divider"></div>

                    <!-- TOMBOL LOGOUT MERAH ELEGAN -->
                    <div class="ud-footer">
                        <a href="<?= e($root) ?>logout.php" class="ud-item ud-logout" role="menuitem">
                            <div class="ud-icon-danger">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </div>
                            <span class="ud-logout-txt">Keluar dari Sistem</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-content">

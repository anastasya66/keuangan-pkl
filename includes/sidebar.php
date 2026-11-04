<?php
/**
 * =====================================================================
 * FINTOKO — SIDEBAR NAVIGASI (MODERN ICY AZURE SAAS THEME)
 * =====================================================================
 * Membutuhkan variabel layout:
 *   $root   -> path relatif ("" dari root, "../" dari /pages)
 *   $active -> identifier menu aktif
 * =====================================================================
 */
$root   = $root ?? '';
$active = $active ?? '';

$menu_utama = [
    'dashboard'    => ['icon' => 'fa-house',              'label' => 'Dashboard',        'href' => $root . 'index.php'],
    'kas'          => ['icon' => 'fa-wallet',             'label' => 'Kas',              'href' => $root . 'pages/kas.php'],
    'pemasukan'    => ['icon' => 'fa-arrow-trend-up',     'label' => 'Pemasukan',        'href' => $root . 'pages/pemasukan.php'],
    'pengeluaran'  => ['icon' => 'fa-arrow-trend-down',   'label' => 'Pengeluaran',      'href' => $root . 'pages/pengeluaran.php'],
];

$menu_inventori = [
    'stok'         => ['icon' => 'fa-boxes-stacked',      'label' => 'Stok Barang',      'href' => $root . 'pages/stok.php'],
    'mutasi'       => ['icon' => 'fa-right-left',         'label' => 'Mutasi Stok',      'href' => $root . 'pages/mutasi_stok.php'],
];

$menu_akuntansi = [
    'jurnal'       => ['icon' => 'fa-book',               'label' => 'Jurnal Umum',      'href' => $root . 'pages/jurnal.php'],
    'buku_besar'   => ['icon' => 'fa-book-open',          'label' => 'Buku Besar',       'href' => $root . 'pages/buku_besar.php'],
];

$menu_laporan = [
    'laporan'      => ['icon' => 'fa-file-invoice',       'label' => 'Laporan Keuangan', 'href' => $root . 'pages/laporan.php'],
    'laba_rugi'    => ['icon' => 'fa-chart-column',       'label' => 'Laba Rugi',        'href' => $root . 'pages/laba_rugi.php'],
    'neraca'       => ['icon' => 'fa-scale-balanced',     'label' => 'Neraca',           'href' => $root . 'pages/neraca.php'],
];
?>
<aside class="sidebar" id="sidebar">

    <!-- Identitas Brand Sistem -->
    <div class="sidebar-brand">
        <div class="brand-symbol"><i class="fa-solid fa-store"></i></div>
        <div class="brand-info">
            <strong>FinToko</strong>
            <span>Sistem Informasi Keuangan</span>
        </div>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup navigasi">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Navigasi Menu Sidebar Terpadu -->
    <nav class="menu">
        <div class="menu-label">Menu Utama</div>
        <?php foreach ($menu_utama as $key => $item): ?>
            <a href="<?= e($item['href']) ?>"
               class="menu-item <?= $active === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>

        <div class="menu-label">Inventori</div>
        <?php foreach ($menu_inventori as $key => $item): ?>
            <?php if ($key === 'mutasi' && !is_admin()) continue; ?>
            <a href="<?= e($item['href']) ?>"
               class="menu-item <?= $active === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>

        <?php if (is_admin()): ?>
        <div class="menu-label">Akuntansi</div>
        <?php foreach ($menu_akuntansi as $key => $item): ?>
            <a href="<?= e($item['href']) ?>"
               class="menu-item <?= $active === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>

        <div class="menu-label">Laporan</div>
        <?php foreach ($menu_laporan as $key => $item): ?>
            <a href="<?= e($item['href']) ?>"
               class="menu-item <?= $active === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </nav>

</aside>

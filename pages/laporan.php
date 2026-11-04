<?php
/**
 * =====================================================================
 * FINTOKO — SISTEM INFORMASI KEUANGAN & SIKLUS AKUNTANSI
 * =====================================================================
 * File    : pages/laporan.php (v8.0 — Clean Cash Flow Card Edition)
 * Halaman : Laporan Arus Kas (Cash Flow Statement)
 * Desain  : Container Kartu Putih Elegan, Tema Biru Muda Estetik (Icy Azure)
 *           Format Bersih & Rapi, Tanpa Area Tanda Tangan
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');
require_admin('../');

/* ═══════════════════════════════════════════════════════════════════════════
   1. FILTER BULAN & NAVIGASI PERIODE
   ═══════════════════════════════════════════════════════════════════════════ */
$bulan = $_GET['bulan'] ?? date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    $bulan = date('Y-m');
}

[$tahun_f, $bulan_f] = explode('-', $bulan);

$ts_aktif         = strtotime($bulan . '-01');
$bulan_sebelumnya = date('Y-m', strtotime('-1 month', $ts_aktif));
$bulan_berikutnya = date('Y-m', strtotime('+1 month', $ts_aktif));
$bulan_ini        = date('Y-m');
$awal_bulan       = $bulan . '-01';
$akhir_bulan      = date('Y-m-t', $ts_aktif);

/* ═══════════════════════════════════════════════════════════════════════════
   2. QUERY DATA ARUS KAS DARI DATABASE SECARA AKURAT
   ═══════════════════════════════════════════════════════════════════════════ */

// A. Saldo Kas Awal (Akumulasi transaksi sebelum tanggal awal bulan ini)
$stmt = db()->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN jenis = 'Pemasukan' THEN nominal ELSE 0 END), 0)
        - COALESCE(SUM(CASE WHEN jenis = 'Pengeluaran' THEN nominal ELSE 0 END), 0) AS saldo_awal
    FROM transaksi
    WHERE tanggal < ?
");
$stmt->bind_param('s', $awal_bulan);
$stmt->execute();
$saldo_awal = (float) $stmt->get_result()->fetch_assoc()['saldo_awal'];
$stmt->close();

// B. Rincian Kas Masuk (Pemasukan) bulan berjalan per kategori
$stmt = db()->prepare("
    SELECT
        kategori,
        COUNT(*) AS jml_transaksi,
        COALESCE(SUM(nominal), 0) AS total_kategori
    FROM transaksi
    WHERE jenis = 'Pemasukan' AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
    GROUP BY kategori
    ORDER BY total_kategori DESC
");
$stmt->bind_param('ii', $tahun_f, $bulan_f);
$stmt->execute();
$rekap_masuk = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// C. Rincian Kas Keluar (Pengeluaran) bulan berjalan per kategori
$stmt = db()->prepare("
    SELECT
        kategori,
        COUNT(*) AS jml_transaksi,
        COALESCE(SUM(nominal), 0) AS total_kategori
    FROM transaksi
    WHERE jenis = 'Pengeluaran' AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
    GROUP BY kategori
    ORDER BY total_kategori DESC
");
$stmt->bind_param('ii', $tahun_f, $bulan_f);
$stmt->execute();
$rekap_keluar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// D. Perhitungan Total Arus Kas
$total_kas_masuk     = array_sum(array_column($rekap_masuk, 'total_kategori'));
$total_kas_keluar    = array_sum(array_column($rekap_keluar, 'total_kategori'));
$total_trx_masuk     = array_sum(array_column($rekap_masuk, 'jml_transaksi'));
$total_trx_keluar    = array_sum(array_column($rekap_keluar, 'jml_transaksi'));

// Kenaikan / (Penurunan) Kas Bersih
$perubahan_kas_bersih = $total_kas_masuk - $total_kas_keluar;

// Saldo Kas Akhir Bersih
$saldo_akhir_kas     = $saldo_awal + $perubahan_kas_bersih;

/* ═══════════════════════════════════════════════════════════════════════════
   2.5 HELPER RENDER PARSIAL (SSR & AJAX LIVE REFRESH)
   ═══════════════════════════════════════════════════════════════════════════ */
function render_laporan_stats($saldo_awal, $total_kas_masuk, $total_trx_masuk, $total_kas_keluar, $total_trx_keluar, $saldo_akhir_kas, $bulan) {
    ob_start();
    ?>
    <!-- Saldo Kas Awal -->
    <div class="cf-stat-card">
        <div class="cf-stat-label">
            <i class="fa-solid fa-vault" style="color:var(--az-600);"></i> Saldo Kas Awal
        </div>
        <div class="cf-stat-value" style="color:var(--sl-800);">
            <?= rupiah($saldo_awal) ?>
        </div>
        <div class="cf-stat-note">Per 1 <?= bulan_tahun_id($bulan) ?></div>
    </div>

    <!-- Total Kas Masuk -->
    <div class="cf-stat-card">
        <div class="cf-stat-label">
            <i class="fa-solid fa-arrow-down" style="color:#059669;"></i> Total Kas Masuk
        </div>
        <div class="cf-stat-value" style="color:#047857;">
            + <?= rupiah($total_kas_masuk) ?>
        </div>
        <div class="cf-stat-note"><?= (int)$total_trx_masuk ?> Transaksi Penerimaan</div>
    </div>

    <!-- Total Kas Keluar -->
    <div class="cf-stat-card">
        <div class="cf-stat-label">
            <i class="fa-solid fa-arrow-up" style="color:#dc2626;"></i> Total Kas Keluar
        </div>
        <div class="cf-stat-value" style="color:#dc2626;">
            - <?= rupiah($total_kas_keluar) ?>
        </div>
        <div class="cf-stat-note"><?= (int)$total_trx_keluar ?> Transaksi Pengeluaran</div>
    </div>

    <!-- Saldo Kas Akhir Bersih -->
    <div class="cf-stat-card" style="background:var(--az-50); border-color:var(--az-200);">
        <div class="cf-stat-label" style="color:var(--az-800);">
            <i class="fa-solid fa-wallet" style="color:var(--az-600);"></i> Saldo Kas Akhir
        </div>
        <div class="cf-stat-value" style="color:var(--az-700);">
            <?= rupiah($saldo_akhir_kas) ?>
        </div>
        <div class="cf-stat-note" style="color:var(--az-700); font-weight:600;">
            Likuiditas Bersih Berjalan
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function render_laporan_rows($saldo_awal, $rekap_masuk, $total_trx_masuk, $total_kas_masuk, $rekap_keluar, $total_trx_keluar, $total_kas_keluar, $perubahan_kas_bersih, $saldo_akhir_kas) {
    ob_start();
    ?>
    <!-- SECTION I: SALDO KAS AWAL -->
    <tr class="cf-header-section">
        <td colspan="2">I. Saldo Kas pada Awal Periode</td>
        <td class="cf-col-amount" style="color:var(--az-800); font-weight:800;">
            <?= rupiah($saldo_awal) ?>
        </td>
    </tr>

    <!-- SECTION II: TOTAL KAS MASUK (PEMASUKAN) -->
    <tr class="cf-header-section">
        <td colspan="3">II. Arus Kas Masuk (Penerimaan Kas)</td>
    </tr>
    <?php if (count($rekap_masuk) > 0): ?>
        <?php foreach ($rekap_masuk as $in): ?>
            <tr class="cf-row-item">
                <td>
                    <i class="fa-solid fa-arrow-down" style="color:#059669; font-size:11px; margin-right:6px;"></i>
                    <?= e($in['kategori']) ?>
                </td>
                <td style="text-align:center; color:var(--sl-600);">
                    <?= (int)$in['jml_transaksi'] ?> Transaksi
                </td>
                <td class="cf-col-amount" style="color:#047857;">
                    + <?= rupiah($in['total_kategori']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr class="cf-row-item">
            <td colspan="3" style="font-style:italic; color:var(--sl-500); padding-left:28px;">
                Tidak ada catatan kas masuk pada periode ini.
            </td>
        </tr>
    <?php endif; ?>
    <tr class="cf-row-subtotal">
        <td><strong>Total Kas Masuk</strong></td>
        <td style="text-align:center;"><strong><?= (int)$total_trx_masuk ?> Transaksi</strong></td>
        <td class="cf-col-amount" style="color:#047857; font-weight:800;">
            + <?= rupiah($total_kas_masuk) ?>
        </td>
    </tr>

    <!-- SECTION III: TOTAL KAS KELUAR (PENGELUARAN) -->
    <tr class="cf-header-section">
        <td colspan="3">III. Arus Kas Keluar (Pengeluaran Kas)</td>
    </tr>
    <?php if (count($rekap_keluar) > 0): ?>
        <?php foreach ($rekap_keluar as $out): ?>
            <tr class="cf-row-item">
                <td>
                    <i class="fa-solid fa-arrow-up" style="color:#dc2626; font-size:11px; margin-right:6px;"></i>
                    <?= e($out['kategori']) ?>
                </td>
                <td style="text-align:center; color:var(--sl-600);">
                    <?= (int)$out['jml_transaksi'] ?> Transaksi
                </td>
                <td class="cf-col-amount" style="color:#dc2626;">
                    - <?= rupiah($out['total_kategori']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr class="cf-row-item">
            <td colspan="3" style="font-style:italic; color:var(--sl-500); padding-left:28px;">
                Tidak ada catatan kas keluar pada periode ini.
            </td>
        </tr>
    <?php endif; ?>
    <tr class="cf-row-subtotal">
        <td><strong>Total Kas Keluar</strong></td>
        <td style="text-align:center;"><strong><?= (int)$total_trx_keluar ?> Transaksi</strong></td>
        <td class="cf-col-amount" style="color:#dc2626; font-weight:800;">
            - <?= rupiah($total_kas_keluar) ?>
        </td>
    </tr>

    <!-- SECTION IV: REKONSILIASI DAN SALDO KAS AKHIR BERSIH -->
    <tr class="cf-row-net">
        <td colspan="2">
            <strong>Kenaikan / (Penurunan) Kas Bersih</strong>
            <div style="font-size:11.5px; font-weight:500; color:var(--sl-600);">
                Total Kas Masuk dikurangi Total Kas Keluar
            </div>
        </td>
        <td class="cf-col-amount" style="color:<?= $perubahan_kas_bersih >= 0 ? '#047857' : '#dc2626' ?>; font-size:14.5px;">
            <?= $perubahan_kas_bersih >= 0 ? '+ ' : '' ?><?= rupiah($perubahan_kas_bersih) ?>
        </td>
    </tr>

    <tr>
        <td colspan="2" style="padding-left:22px; color:var(--sl-700);">
            Saldo Kas Awal Periode
        </td>
        <td class="cf-col-amount" style="color:var(--sl-800);">
            <?= rupiah($saldo_awal) ?>
        </td>
    </tr>

    <tr class="cf-row-final">
        <td colspan="2">
            <strong>SALDO KAS AKHIR BERSIH</strong>
            <div style="font-size:11.5px; font-weight:500; color:var(--sl-600);">
                Saldo Kas Awal + Kenaikan / (Penurunan) Kas Bersih
            </div>
        </td>
        <td class="cf-col-amount" style="color:var(--az-800); font-size:16px;">
            <strong><?= rupiah($saldo_akhir_kas) ?></strong>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

function render_laporan_nav($bulan, $bulan_sebelumnya, $bulan_ini, $bulan_berikutnya) {
    ob_start();
    ?>
    <a href="?bulan=<?= $bulan_sebelumnya ?>" class="cf-pill-btn cf-pill-nav" data-bulan="<?= $bulan_sebelumnya ?>" title="Bulan Sebelumnya">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <a href="?bulan=<?= $bulan_ini ?>" class="cf-pill-btn cf-pill-nav <?= ($bulan === $bulan_ini) ? 'cf-pill-btn--active' : '' ?>" data-bulan="<?= $bulan_ini ?>">
        Bulan Ini
    </a>
    <a href="?bulan=<?= $bulan_berikutnya ?>" class="cf-pill-btn cf-pill-nav" data-bulan="<?= $bulan_berikutnya ?>" title="Bulan Berikutnya">
        <i class="fa-solid fa-chevron-right"></i>
    </a>
    <?php
    return ob_get_clean();
}

/* ═══════════════════════════════════════════════════════════════════════════
   2.6 AJAX JSON ENDPOINT (REAL-TIME FILTER TANPA RELOAD)
   ═══════════════════════════════════════════════════════════════════════════ */
if ((isset($_GET['ajax']) && $_GET['ajax'] == '1') || 
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'       => 'success',
        'stats_html'   => render_laporan_stats($saldo_awal, $total_kas_masuk, $total_trx_masuk, $total_kas_keluar, $total_trx_keluar, $saldo_akhir_kas, $bulan),
        'rows_html'    => render_laporan_rows($saldo_awal, $rekap_masuk, $total_trx_masuk, $total_kas_masuk, $rekap_keluar, $total_trx_keluar, $total_kas_keluar, $perubahan_kas_bersih, $saldo_akhir_kas),
        'nav_html'     => render_laporan_nav($bulan, $bulan_sebelumnya, $bulan_ini, $bulan_berikutnya),
        'period_text'  => 'Periode: <strong>' . tanggal_panjang_id($awal_bulan) . '</strong> s/d <strong>' . tanggal_panjang_id($akhir_bulan) . '</strong> &bull; <span style="color:var(--az-700); font-weight:600;">' . e(NAMA_INSTANSI) . '</span>',
        'footer_date'  => tanggal_panjang_id($akhir_bulan),
        'bulan'        => $bulan,
        'export_excel' => '?bulan=' . urlencode($bulan) . '&export=excel'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   3. EKSPOR EXCEL / CSV UTF-8
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'csv'], true)) {
    $filename = 'Laporan_Arus_Kas_' . $bulan . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

    fputcsv($out, [NAMA_INSTANSI], ';');
    fputcsv($out, ['RINGKASAN LAPORAN ARUS KAS'], ';');
    fputcsv($out, ['Periode: ' . tanggal_panjang_id($awal_bulan) . ' s/d ' . tanggal_panjang_id($akhir_bulan)], ';');
    fputcsv($out, ['Dicetak pada: ' . date('d/m/Y H:i:s')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['I. SALDO KAS AWAL', number_format($saldo_awal, 2, ',', '.')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['II. TOTAL KAS MASUK (PEMASUKAN)', 'TRANSAKSI', 'NILAI (RP)'], ';');
    foreach ($rekap_masuk as $r) {
        fputcsv($out, ['  - ' . $r['kategori'], $r['jml_transaksi'], number_format($r['total_kategori'], 2, ',', '.')], ';');
    }
    fputcsv($out, ['SUBTOTAL KAS MASUK', $total_trx_masuk, number_format($total_kas_masuk, 2, ',', '.')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['III. TOTAL KAS KELUAR (PENGELUARAN)', 'TRANSAKSI', 'NILAI (RP)'], ';');
    foreach ($rekap_keluar as $r) {
        fputcsv($out, ['  - ' . $r['kategori'], $r['jml_transaksi'], number_format($r['total_kategori'], 2, ',', '.')], ';');
    }
    fputcsv($out, ['SUBTOTAL KAS KELUAR', $total_trx_keluar, number_format($total_kas_keluar, 2, ',', '.')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['IV. IKHTISAR SALDO KAS BERSIH', 'NILAI (RP)'], ';');
    fputcsv($out, ['Kenaikan / (Penurunan) Kas Bersih', number_format($perubahan_kas_bersih, 2, ',', '.')], ';');
    fputcsv($out, ['Saldo Kas Awal', number_format($saldo_awal, 2, ',', '.')], ';');
    fputcsv($out, ['SALDO KAS AKHIR BERSIH', number_format($saldo_akhir_kas, 2, ',', '.')], ';');

    fclose($out);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   4. TEMPLATE META & HEADER
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Laporan Arus Kas';
$page_eyebrow = NAMA_INSTANSI . ' · Laporan Keuangan';
$root         = '../';
$active       = 'laporan';

include __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
   TEMA ESTETIK BIRU MUDA (ICY AZURE) — RINGKASAN ARUS KAS MODERN
   ═══════════════════════════════════════════════════════════════════════════ */
:root {
    --az-50:  #f0f9ff;
    --az-100: #e0f2fe;
    --az-200: #bae6fd;
    --az-300: #7dd3fc;
    --az-400: #38bdf8;
    --az-500: #0ea5e9;
    --az-600: #0284c7;
    --az-700: #0369a1;
    --az-800: #075985;
    --sl-50:  #f8fafc;
    --sl-100: #f1f5f9;
    --sl-200: #e2e8f0;
    --sl-300: #cbd5e1;
    --sl-500: #64748b;
    --sl-600: #475569;
    --sl-700: #334155;
    --sl-800: #1e293b;
    --sl-900: #0f172a;
}

/* Container Kartu Putih Elegan */
.cf-card-container {
    background: #ffffff;
    border: 1px solid var(--az-200);
    border-radius: 20px;
    box-shadow: 
        0 8px 30px rgba(14, 165, 233, 0.06),
        0 2px 6px rgba(0, 0, 0, 0.02);
    padding: 32px 36px;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
}

/* Top Accent Line */
.cf-card-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--az-500), var(--az-300));
}

/* Header Kartu: Judul Utama & Filter Periode */
.cf-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--sl-200);
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.cf-card-title-group {
    flex: 1;
    min-width: 260px;
}

.cf-card-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: var(--az-600);
    background: var(--az-50);
    border: 1px solid var(--az-200);
    padding: 3px 10px;
    border-radius: 999px;
    margin-bottom: 8px;
}

.cf-card-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--sl-900);
    margin: 0 0 4px 0;
    letter-spacing: -0.3px;
}

.cf-card-subtitle {
    font-size: 13px;
    color: var(--sl-600);
    margin: 0;
}

/* Toolbar Filter & Tombol Cetak / Ekspor */
.cf-controls-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.cf-filter-box {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--sl-50);
    border: 1px solid var(--sl-300);
    border-radius: 10px;
    padding: 5px 10px;
    transition: all 0.2s ease;
}

.cf-filter-box:focus-within {
    border-color: var(--az-500);
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

.cf-filter-box label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--sl-600);
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin: 0;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 5px;
}

.cf-input-month {
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: var(--sl-800);
    outline: none;
    cursor: pointer;
    padding: 2px 4px;
}

.cf-nav-pills {
    display: flex;
    align-items: center;
    gap: 4px;
}

.cf-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 11px;
    font-size: 12px;
    font-weight: 600;
    color: var(--sl-700);
    background: var(--sl-100);
    border: 1px solid var(--sl-200);
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.18s ease;
}

.cf-pill-btn:hover {
    background: var(--az-100);
    color: var(--az-700);
    border-color: var(--az-200);
}

.cf-pill-btn--active {
    background: var(--az-100);
    color: var(--az-700);
    border-color: var(--az-300);
    font-weight: 700;
}

.cf-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 15px;
    font-size: 12.5px;
    font-weight: 700;
    border-radius: 9px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.cf-action-btn--print {
    background: linear-gradient(135deg, var(--az-500) 0%, var(--az-600) 100%);
    color: #ffffff;
    box-shadow: 0 3px 8px rgba(14, 165, 233, 0.25);
}

.cf-action-btn--print:hover {
    background: linear-gradient(135deg, var(--az-600) 0%, var(--az-700) 100%);
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(14, 165, 233, 0.35);
    color: #ffffff;
}

.cf-action-btn--excel {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
}

.cf-action-btn--excel:hover {
    background: #dcfce7;
    border-color: #86efac;
    color: #14532d;
    transform: translateY(-1px);
}

/* 4 Kartu Ringkasan Metrik Cepat (Quick Stat Bar) */
.cf-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 26px;
}

.cf-stat-card {
    background: var(--sl-50);
    border: 1px solid var(--az-100);
    border-radius: 14px;
    padding: 16px 18px;
    transition: all 0.22s ease;
}

.cf-stat-card:hover {
    background: #ffffff;
    border-color: var(--az-300);
    box-shadow: 0 6px 16px rgba(14, 165, 233, 0.08);
    transform: translateY(-2px);
}

.cf-stat-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--sl-500);
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cf-stat-value {
    font-size: 18px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    line-height: 1.25;
}

.cf-stat-note {
    font-size: 11px;
    color: var(--sl-500);
    margin-top: 4px;
}

/* Tabel Lembar Kerja Arus Kas Bersih & Rapi */
.cf-report-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 13px;
}

.cf-report-table th,
.cf-report-table td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--sl-200);
}

.cf-table-head {
    background: var(--sl-100);
    border-top: 1px solid var(--sl-300);
    border-bottom: 2px solid var(--az-500);
}

.cf-table-head th {
    font-size: 12px;
    font-weight: 800;
    color: var(--sl-800);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.cf-header-section {
    background: var(--az-50);
    border-top: 1px solid var(--az-200);
    border-bottom: 1px solid var(--az-200) !important;
}

.cf-header-section td {
    padding: 11px 14px;
    font-size: 13px;
    font-weight: 800;
    color: var(--az-800);
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.cf-row-item td:first-child {
    padding-left: 28px;
    color: var(--sl-700);
}

.cf-col-amount {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    font-weight: 600;
}

.cf-row-subtotal td {
    background: #fafafa;
    border-top: 1px solid var(--sl-300);
    border-bottom: 1px solid var(--sl-300);
    font-weight: 700;
    color: var(--sl-900);
}

.cf-row-net {
    background: var(--az-50);
    border-top: 2px solid var(--az-400);
    border-bottom: 2px solid var(--az-400);
}

.cf-row-net td {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--az-800);
    padding: 12px 14px;
}

.cf-row-final td {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--sl-900);
    background: #ffffff;
    border-top: 1px solid var(--sl-800);
    border-bottom: 3px double var(--sl-800) !important;
    padding: 13px 14px;
}

/* Footer Catatan Verifikasi Bersih */
.cf-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--sl-200);
    font-size: 12px;
    color: var(--sl-500);
    flex-wrap: wrap;
}

.cf-badge-verified {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #047857;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    padding: 3px 10px;
    border-radius: 999px;
}

@media screen and (max-width: 1024px) {
    .cf-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media screen and (max-width: 768px) {
    .cf-card-container {
        padding: 16px 14px;
        border-radius: var(--r-md);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .cf-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .cf-card-header {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
    }

    .cf-controls-group {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .cf-controls-group form {
        width: 100%;
    }
    .cf-filter-box {
        width: 100%;
        justify-content: space-between;
        min-height: 42px;
    }
    .cf-action-btn {
        width: 100%;
        justify-content: center;
        min-height: 42px;
    }

    .cf-report-table {
        min-width: 580px;
    }
}

@media screen and (max-width: 480px) {
    .cf-stats-grid {
        grid-template-columns: 1fr;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   PRINT / PDF MEDIA QUERY — STANDAR LAPORAN ARUS KAS RESMI
   ═══════════════════════════════════════════════════════════════════════════ */
@media print {
    @page {
        size: A4 portrait;
        margin: 8mm 10mm;
    }

    html, body {
        background: #ffffff !important;
        color: #0f172a !important;
        font-size: 11px !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .sidebar,
    #sidebar,
    aside.sidebar,
    .sidebar-overlay,
    #sidebarOverlay,
    .topbar,
    header.topbar,
    .burger,
    #burgerBtn,
    .app-footer,
    footer,
    .cf-controls-group,
    .cf-action-btn,
    .cf-pill-btn,
    .alert,
    .modal,
    .modal-overlay {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        width: 0 !important;
    }

    .app-layout, .main, .page-content {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Sembunyikan ikon dekoratif */
    i.fa-solid,
    i.fa-regular,
    .cf-card-header i,
    .cf-stat-card i,
    .cf-card-footer i {
        display: none !important;
    }

    * {
        box-shadow: none !important;
        text-shadow: none !important;
    }

    .cf-card-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    .cf-card-container::before {
        display: none !important;
    }

    .cf-card-header {
        display: block !important;
        padding: 0 0 6px 0 !important;
        margin-bottom: 8px !important;
        border-bottom: 1.5px solid #0f172a !important;
    }

    .cf-card-eyebrow {
        font-size: 9px !important;
        color: #475569 !important;
    }

    .cf-card-title {
        font-size: 16px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        margin: 0 0 2px 0 !important;
    }

    .cf-card-subtitle {
        font-size: 10px !important;
        color: #334155 !important;
        margin: 0 !important;
    }

    .cf-stats-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 6px !important;
        margin-bottom: 10px !important;
    }

    .cf-stat-card {
        border: 1px solid #cbd5e1 !important;
        background: #f8fafc !important;
        border-radius: 4px !important;
        padding: 6px 8px !important;
    }

    .cf-stat-label {
        font-size: 9px !important;
        font-weight: 700 !important;
        color: #475569 !important;
    }

    .cf-stat-val {
        font-size: 11.5px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }

    .cf-header-section {
        background: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 800 !important;
        border-top: 1px solid #94a3b8 !important;
        border-bottom: 1px solid #94a3b8 !important;
    }

    .cf-table {
        font-size: 10px !important;
    }

    .cf-table th, .cf-table td {
        padding: 4px 6px !important;
    }

    .cf-row-final td {
        border-bottom: 3px double #000000 !important;
        font-weight: 800 !important;
        font-size: 11px !important;
    }

    .cf-card-footer {
        padding-top: 6px !important;
        margin-top: 6px !important;
        border-top: 1px solid #cbd5e1 !important;
        font-size: 9px !important;
    }
}
</style>

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     CONTAINER KARTU PUTIH ELEGAN: RINGKASAN ARUS KAS
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="cf-card-container">

    <!-- 1. HEADER KARTU: JUDUL UTAMA LAPORAN & FILTER PERIODE DI ATASNYA -->
    <div class="cf-card-header">
        <div class="cf-card-title-group">
            <span class="cf-card-eyebrow">
                <i class="fa-solid fa-file-invoice-dollar"></i> LAPORAN AKUNTANSI &bull; KAS &amp; BANK
            </span>
            <h2 class="cf-card-title">Ringkasan Laporan Arus Kas</h2>
            <p class="cf-card-subtitle" id="cfCardSubtitle">
                Periode: <strong><?= tanggal_panjang_id($awal_bulan) ?></strong> s/d <strong><?= tanggal_panjang_id($akhir_bulan) ?></strong>
                &bull; <span style="color:var(--az-700); font-weight:600;"><?= e(NAMA_INSTANSI) ?></span>
            </p>
        </div>

        <!-- Filter Periode & Aksi Cetak/Ekspor -->
        <div class="cf-controls-group">
            <form method="GET" action="" id="cfFilterForm" onsubmit="return false;" style="display:flex; align-items:center; gap:6px; margin:0;">
                <div class="cf-filter-box">
                    <label for="bulan"><i class="fa-regular fa-calendar" style="color:var(--az-500);"></i> Periode:</label>
                    <input type="month" id="bulan" name="bulan" value="<?= e($bulan) ?>" class="cf-input-month">
                </div>
            </form>

            <div class="cf-nav-pills" id="cfNavPills">
                <?= render_laporan_nav($bulan, $bulan_sebelumnya, $bulan_ini, $bulan_berikutnya) ?>
            </div>

            <a href="?bulan=<?= urlencode($bulan) ?>&export=excel" id="cfExportBtn" class="cf-action-btn cf-action-btn--excel" title="Unduh CSV">
                <i class="fa-solid fa-file-excel"></i> Ekspor CSV
            </a>

            <button type="button" class="cf-action-btn cf-action-btn--print" onclick="window.print()" title="Cetak atau Simpan PDF">
                <i class="fa-solid fa-print"></i> Cetak / PDF
            </button>
        </div>
    </div>

    <!-- 2. KARTU METRIK CEPAT: SALDO AWAL, MASUK, KELUAR, SALDO AKHIR -->
    <div class="cf-stats-grid" id="cfStatsGrid">
        <?= render_laporan_stats($saldo_awal, $total_kas_masuk, $total_trx_masuk, $total_kas_keluar, $total_trx_keluar, $saldo_akhir_kas, $bulan) ?>
    </div>

    <!-- 3. TABEL FORMAT LAPORAN ARUS KAS BERSIH & FORMAL -->
    <table class="cf-report-table">
        <thead>
            <tr class="cf-table-head">
                <th style="text-align:left;">Komponen Arus Kas</th>
                <th style="text-align:center; width:130px;">Jumlah Transaksi</th>
                <th style="text-align:right; width:220px;">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody id="cfTableBody">
            <?= render_laporan_rows($saldo_awal, $rekap_masuk, $total_trx_masuk, $total_kas_masuk, $rekap_keluar, $total_trx_keluar, $total_kas_keluar, $perubahan_kas_bersih, $saldo_akhir_kas) ?>
        </tbody>
    </table>

    <!-- 4. FOOTER INFORMASI DOKUMEN (TANPA AREA TANDA TANGAN) -->
    <div class="cf-card-footer">
        <div id="cfFooterInfo">
            <i class="fa-solid fa-circle-info" style="color:var(--az-500); margin-right:4px;"></i>
            Data arus kas dihitung otomatis berbasis transaksi riil (<em>Cash-Basis</em>) per <strong id="cfFooterDate"><?= tanggal_panjang_id($akhir_bulan) ?></strong>.
        </div>
        <div class="cf-badge-verified">
            <i class="fa-solid fa-circle-check"></i> Rekonsiliasi Kas Terverifikasi
        </div>
    </div>

</div>

<script>
(function() {
    let reportAbortController = null;

    function fetchReportAjax(targetMonth) {
        if (!targetMonth) return;

        if (reportAbortController) {
            reportAbortController.abort();
        }
        reportAbortController = new AbortController();

        const tableBody = document.getElementById('cfTableBody');
        const statsGrid = document.getElementById('cfStatsGrid');
        if (tableBody) tableBody.style.opacity = '0.5';
        if (statsGrid) statsGrid.style.opacity = '0.5';

        const params = new URLSearchParams();
        params.set('bulan', targetMonth);
        params.set('ajax', '1');

        fetch('laporan.php?' + params.toString(), {
            signal: reportAbortController.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (statsGrid && data.stats_html) {
                    statsGrid.innerHTML = data.stats_html;
                    statsGrid.classList.remove('ajax-fade-in');
                    void statsGrid.offsetWidth;
                    statsGrid.classList.add('ajax-fade-in');
                }
                if (tableBody && data.rows_html) {
                    tableBody.innerHTML = data.rows_html;
                    tableBody.classList.remove('ajax-fade-in');
                    void tableBody.offsetWidth;
                    tableBody.classList.add('ajax-fade-in');
                }
                const subtitle = document.getElementById('cfCardSubtitle');
                if (subtitle && data.period_text) {
                    subtitle.innerHTML = data.period_text;
                }
                const footerDate = document.getElementById('cfFooterDate');
                if (footerDate && data.footer_date) {
                    footerDate.textContent = data.footer_date;
                }
                const navPills = document.getElementById('cfNavPills');
                if (navPills && data.nav_html) {
                    navPills.innerHTML = data.nav_html;
                    bindNavPills();
                }
                const exportBtn = document.getElementById('cfExportBtn');
                if (exportBtn && data.export_excel) {
                    exportBtn.href = data.export_excel;
                }
                const inputMonth = document.getElementById('bulan');
                if (inputMonth && data.bulan) {
                    inputMonth.value = data.bulan;
                }

                const cleanUrl = 'laporan.php?bulan=' + encodeURIComponent(data.bulan);
                window.history.replaceState({ bulan: data.bulan }, '', cleanUrl);
            }
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                console.error('AJAX Error:', err);
            }
        })
        .finally(() => {
            if (tableBody) tableBody.style.opacity = '1';
            if (statsGrid) statsGrid.style.opacity = '1';
        });
    }

    function bindNavPills() {
        const pills = document.querySelectorAll('.cf-pill-nav');
        pills.forEach(pill => {
            pill.addEventListener('click', function(e) {
                e.preventDefault();
                const m = this.getAttribute('data-bulan');
                if (m) fetchReportAjax(m);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputMonth = document.getElementById('bulan');
        if (inputMonth) {
            inputMonth.addEventListener('change', function() {
                fetchReportAjax(this.value);
            });
        }
        bindNavPills();

        window.addEventListener('popstate', function(e) {
            const urlParams = new URLSearchParams(window.location.search);
            const m = urlParams.get('bulan') || '';
            if (m) {
                fetchReportAjax(m);
            }
        });
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * =====================================================================
 * FINTOKO — SISTEM INFORMASI KEUANGAN & SIKLUS AKUNTANSI
 * =====================================================================
 * File    : pages/laba_rugi.php (v6.0 — Formal Income Statement Edition)
 * Halaman : Laporan Laba Rugi (Income Statement)
 * Desain  : Lembar Laporan Akuntansi Vertikal Formal, Bersih & Modern
 *           Tema Biru Muda Estetik (Icy Azure), Tanpa Area Tanda Tangan
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');
require_admin('../');

/* ═══════════════════════════════════════════════════════════════════════════
   1. FILTER BULAN & NAVIGASI WAKTU
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
   2. REKAP PENDAPATAN & BEBAN DARI DATABASE
   ═══════════════════════════════════════════════════════════════════════════ */

// A. Rekap Pendapatan per Kategori bulan ini
$stmt = db()->prepare("
    SELECT
        kategori,
        COUNT(*) AS jml_transaksi,
        COALESCE(SUM(nominal), 0) AS total
    FROM transaksi
    WHERE jenis = 'Pemasukan' AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
    GROUP BY kategori
    ORDER BY total DESC
");
$stmt->bind_param('ii', $tahun_f, $bulan_f);
$stmt->execute();
$rekap_pendapatan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// B. Rekap Beban per Kategori bulan ini
$stmt = db()->prepare("
    SELECT
        kategori,
        COUNT(*) AS jml_transaksi,
        COALESCE(SUM(nominal), 0) AS total
    FROM transaksi
    WHERE jenis = 'Pengeluaran' AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
    GROUP BY kategori
    ORDER BY total DESC
");
$stmt->bind_param('ii', $tahun_f, $bulan_f);
$stmt->execute();
$rekap_beban = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// C. Perhitungan Subtotal, Total, dan Laba Bersih
$total_pendapatan = array_sum(array_column($rekap_pendapatan, 'total'));
$total_beban      = array_sum(array_column($rekap_beban, 'total'));
$total_trx_in     = array_sum(array_column($rekap_pendapatan, 'jml_transaksi'));
$total_trx_out    = array_sum(array_column($rekap_beban, 'jml_transaksi'));

$laba_bersih      = $total_pendapatan - $total_beban;
$margin_laba      = $total_pendapatan > 0 ? ($laba_bersih / $total_pendapatan) * 100 : 0.0;

/* ═══════════════════════════════════════════════════════════════════════════
   3. EKSPOR EXCEL / CSV UTF-8 (LAPORAN LABA RUGI FORMAL)
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'csv'], true)) {
    $filename = 'Laporan_Laba_Rugi_' . $bulan . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

    fputcsv($out, [NAMA_INSTANSI], ';');
    fputcsv($out, ['LAPORAN LABA RUGI (INCOME STATEMENT)'], ';');
    fputcsv($out, ['Periode: ' . tanggal_panjang_id($awal_bulan) . ' s/d ' . tanggal_panjang_id($akhir_bulan)], ';');
    fputcsv($out, ['Dicetak pada: ' . date('d/m/Y H:i:s')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['I. PENDAPATAN OPERASIONAL', 'TRANSAKSI', 'NILAI (RP)'], ';');
    foreach ($rekap_pendapatan as $r) {
        fputcsv($out, ['  - ' . $r['kategori'], $r['jml_transaksi'], number_format($r['total'], 2, ',', '.')], ';');
    }
    fputcsv($out, ['TOTAL PENDAPATAN OPERASIONAL', $total_trx_in, number_format($total_pendapatan, 2, ',', '.')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['II. BEBAN OPERASIONAL', 'TRANSAKSI', 'NILAI (RP)'], ';');
    foreach ($rekap_beban as $r) {
        fputcsv($out, ['  - ' . $r['kategori'], $r['jml_transaksi'], number_format($r['total'], 2, ',', '.')], ';');
    }
    fputcsv($out, ['TOTAL BEBAN OPERASIONAL', $total_trx_out, number_format($total_beban, 2, ',', '.')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['III. HASIL USAHA BERSIH', 'NILAI (RP)'], ';');
    fputcsv($out, [$laba_bersih >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH', number_format(abs($laba_bersih), 2, ',', '.')], ';');
    fputcsv($out, ['Margin Keuntungan', number_format($margin_laba, 1, ',', '.') . '%'], ';');

    fclose($out);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   4. TEMPLATE META & HEADER
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Laba Rugi';
$page_eyebrow = NAMA_INSTANSI . ' · Laporan Keuangan';
$root         = '../';
$active       = 'laba_rugi';

include __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
   TEMA ESTETIK BIRU MUDA (ICY AZURE) — LEMBAR LAPORAN LABA RUGI FORMAL
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

/* Container Lembar Laporan Kartu Putih Elegan */
.is-card-container {
    background: #ffffff;
    border: 1px solid var(--az-200);
    border-radius: 20px;
    box-shadow: 
        0 8px 30px rgba(14, 165, 233, 0.06),
        0 2px 6px rgba(0, 0, 0, 0.02);
    padding: 34px 40px;
    max-width: 960px;
    margin: 0 auto 36px auto;
    position: relative;
    overflow: hidden;
}

/* Garis Aksen Atas Biru Muda */
.is-card-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--az-500), #10b981);
}

/* Header Kartu: Judul Utama & Filter Periode */
.is-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--sl-200);
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.is-card-title-group {
    flex: 1;
    min-width: 260px;
}

.is-card-eyebrow {
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

.is-card-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--sl-900);
    margin: 0 0 4px 0;
    letter-spacing: -0.3px;
}

.is-card-subtitle {
    font-size: 13px;
    color: var(--sl-600);
    margin: 0;
}

/* Toolbar Kontrol & Filter */
.is-controls-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.is-filter-box {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--sl-50);
    border: 1px solid var(--sl-300);
    border-radius: 10px;
    padding: 5px 10px;
    transition: all 0.2s ease;
}

.is-filter-box:focus-within {
    border-color: var(--az-500);
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

.is-filter-box label {
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

.is-input-month {
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

.is-nav-pills {
    display: flex;
    align-items: center;
    gap: 4px;
}

.is-pill-btn {
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

.is-pill-btn:hover {
    background: var(--az-100);
    color: var(--az-700);
    border-color: var(--az-200);
}

.is-pill-btn--active {
    background: var(--az-100);
    color: var(--az-700);
    border-color: var(--az-300);
    font-weight: 700;
}

.is-action-btn {
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

.is-action-btn--print {
    background: linear-gradient(135deg, var(--az-500) 0%, var(--az-600) 100%);
    color: #ffffff;
    box-shadow: 0 3px 8px rgba(14, 165, 233, 0.25);
}

.is-action-btn--print:hover {
    background: linear-gradient(135deg, var(--az-600) 0%, var(--az-700) 100%);
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(14, 165, 233, 0.35);
    color: #ffffff;
}

.is-action-btn--excel {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
}

.is-action-btn--excel:hover {
    background: #dcfce7;
    border-color: #86efac;
    color: #14532d;
    transform: translateY(-1px);
}

/* 3 Kartu Ringkasan Metrik Kinerja */
.is-metrics-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 26px;
}

.is-metric-box {
    background: var(--sl-50);
    border: 1px solid var(--az-100);
    border-radius: 14px;
    padding: 16px 20px;
    transition: all 0.22s ease;
}

.is-metric-box:hover {
    background: #ffffff;
    border-color: var(--az-300);
    box-shadow: 0 6px 16px rgba(14, 165, 233, 0.08);
    transform: translateY(-2px);
}

.is-metric-label {
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

.is-metric-value {
    font-size: 20px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    line-height: 1.25;
}

.is-metric-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    margin-top: 5px;
    padding: 2px 8px;
    border-radius: 999px;
}

/* Tabel Lembar Kerja Formal Vertikal */
.is-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
    font-size: 13.5px;
}

.is-table th,
.is-table td {
    padding: 10px 16px;
    border-bottom: 1px solid var(--sl-200);
}

.is-table-head {
    background: var(--sl-100);
    border-top: 1px solid var(--sl-300);
    border-bottom: 2px solid var(--az-500);
}

.is-table-head th {
    font-size: 12px;
    font-weight: 800;
    color: var(--sl-800);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Section Header Bar */
.is-section-title {
    background: var(--az-50);
    border-top: 1px solid var(--az-200);
    border-bottom: 1px solid var(--az-200) !important;
}

.is-section-title td {
    padding: 11px 16px;
    font-size: 13px;
    font-weight: 800;
    color: var(--az-800);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Baris Akun & Indentasi */
.is-row-item td:first-child {
    padding-left: 32px;
    color: var(--sl-700);
}

.is-col-nominal {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    font-weight: 600;
}

/* Baris Subtotal (Garis Tunggal di Bawahnya) */
.is-row-subtotal td {
    background: #fafafa;
    border-top: 1px solid var(--sl-300);
    border-bottom: 1px solid var(--sl-400) !important;
    font-weight: 700;
    color: var(--sl-900);
}

/* Hasil Akhir Bersih Bergaris Ganda Akuntansi (Double Underline) */
.is-row-final td {
    font-size: 15px;
    font-weight: 800;
    color: var(--sl-900);
    background: #ffffff;
    border-top: 1px solid var(--sl-800);
    border-bottom: 3px double var(--sl-800) !important;
    padding: 14px 16px;
}

/* Footer Catatan Resmi */
.is-card-footer {
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

.is-badge-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
}

@media screen and (max-width: 768px) {
    .is-card-container {
        padding: 16px 14px;
        border-radius: var(--r-md);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .is-card-header {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
    }

    .is-controls-group {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .is-controls-group form {
        width: 100%;
    }
    .is-filter-box {
        width: 100%;
        justify-content: space-between;
        min-height: 42px;
    }
    .is-action-btn {
        width: 100%;
        justify-content: center;
        min-height: 42px;
    }

    .is-metrics-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .is-table {
        min-width: 580px;
    }
}

@media screen and (max-width: 480px) {
    .is-metrics-grid {
        grid-template-columns: 1fr;
    }
}

/* ═══════════════════════════════════════════════════════════════════════
   PRINT / PDF MEDIA QUERY — STANDAR LAPORAN KEUANGAN RESMI
   ═══════════════════════════════════════════════════════════════════════ */
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
    .is-controls-group,
    .is-action-btn,
    .is-pill-btn,
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
    .is-card-header i,
    .is-metric-box i,
    .is-card-footer i {
        display: none !important;
    }

    * {
        box-shadow: none !important;
        text-shadow: none !important;
    }

    .is-card-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    .is-card-container::before {
        display: none !important;
    }

    .is-card-header {
        display: block !important;
        padding: 0 0 6px 0 !important;
        margin-bottom: 8px !important;
        border-bottom: 1.5px solid #0f172a !important;
    }

    .is-card-eyebrow {
        font-size: 9px !important;
        color: #475569 !important;
    }

    .is-card-title {
        font-size: 16px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        margin: 0 0 2px 0 !important;
    }

    .is-card-subtitle {
        font-size: 10px !important;
        color: #334155 !important;
        margin: 0 !important;
    }

    .is-metrics-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 6px !important;
        margin-bottom: 10px !important;
    }

    .is-metric-box {
        border: 1px solid #cbd5e1 !important;
        background: #f8fafc !important;
        border-radius: 4px !important;
        padding: 6px 8px !important;
    }

    .is-metric-lbl {
        font-size: 9px !important;
        font-weight: 700 !important;
        color: #475569 !important;
    }

    .is-metric-val {
        font-size: 11.5px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }

    .is-section-title {
        background: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 800 !important;
        border-top: 1px solid #94a3b8 !important;
        border-bottom: 1px solid #94a3b8 !important;
    }

    .is-table {
        font-size: 10px !important;
    }

    .is-table th, .is-table td {
        padding: 4px 6px !important;
    }

    .is-row-final td {
        border-bottom: 3px double #000000 !important;
        font-weight: 800 !important;
        font-size: 11px !important;
    }

    .is-card-footer {
        padding-top: 6px !important;
        margin-top: 6px !important;
        border-top: 1px solid #cbd5e1 !important;
        font-size: 9px !important;
    }
}
</style>

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     CONTAINER KARTU PUTIH ELEGAN: LAPORAN LABA RUGI FORMAL
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="is-card-container">

    <!-- 1. HEADER KARTU: JUDUL UTAMA & FILTER PERIODE DI ATASNYA -->
    <div class="is-card-header">
        <div class="is-card-title-group">
            <span class="is-card-eyebrow">
                <i class="fa-solid fa-chart-line"></i> LAPORAN KINERJA &bull; SIKLUS AKUNTANSI
            </span>
            <h2 class="is-card-title">Laporan Laba Rugi</h2>
            <p class="is-card-subtitle">
                Periode: <strong><?= tanggal_panjang_id($awal_bulan) ?></strong> s/d <strong><?= tanggal_panjang_id($akhir_bulan) ?></strong>
                &bull; <span style="color:var(--az-700); font-weight:600;"><?= e(NAMA_INSTANSI) ?></span>
            </p>
        </div>

        <!-- Toolbar Kontrol: Filter Bulan, Cetak & Ekspor -->
        <div class="is-controls-group">
            <form method="GET" action="" style="display:flex; align-items:center; gap:6px; margin:0;">
                <div class="is-filter-box">
                    <label for="bulan"><i class="fa-regular fa-calendar" style="color:var(--az-500);"></i> Periode:</label>
                    <input type="month" id="bulan" name="bulan" value="<?= e($bulan) ?>" class="is-input-month" onchange="this.form.submit()">
                </div>
            </form>

            <div class="is-nav-pills">
                <a href="?bulan=<?= $bulan_sebelumnya ?>" class="is-pill-btn" title="Bulan Sebelumnya">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a href="?bulan=<?= $bulan_ini ?>" class="is-pill-btn <?= ($bulan === $bulan_ini) ? 'is-pill-btn--active' : '' ?>">
                    Bulan Ini
                </a>
                <a href="?bulan=<?= $bulan_berikutnya ?>" class="is-pill-btn" title="Bulan Berikutnya">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>

            <a href="?bulan=<?= urlencode($bulan) ?>&export=excel" class="is-action-btn is-action-btn--excel" title="Unduh CSV">
                <i class="fa-solid fa-file-excel"></i> Ekspor CSV
            </a>

            <button type="button" class="is-action-btn is-action-btn--print" onclick="window.print()" title="Cetak atau Simpan PDF">
                <i class="fa-solid fa-print"></i> Cetak / PDF
            </button>
        </div>
    </div>

    <!-- 2. KARTU METRIK RINGKASAN KINERJA (3 PILAR UTAMA) -->
    <div class="is-metrics-grid">
        <!-- Card 1: Total Pendapatan -->
        <div class="is-metric-box">
            <div class="is-metric-label">
                <i class="fa-solid fa-arrow-trend-up" style="color:#059669;"></i> Total Pendapatan
            </div>
            <div class="is-metric-value" style="color:#047857;">
                <?= rupiah($total_pendapatan) ?>
            </div>
            <div>
                <span class="is-metric-badge" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;">
                    <i class="fa-solid fa-receipt"></i> <?= $total_trx_in ?> Transaksi Masuk
                </span>
            </div>
        </div>

        <!-- Card 2: Total Beban -->
        <div class="is-metric-box">
            <div class="is-metric-label">
                <i class="fa-solid fa-arrow-trend-down" style="color:#dc2626;"></i> Total Beban
            </div>
            <div class="is-metric-value" style="color:#dc2626;">
                <?= rupiah($total_beban) ?>
            </div>
            <div>
                <span class="is-metric-badge" style="background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;">
                    <i class="fa-solid fa-receipt"></i> <?= $total_trx_out ?> Transaksi Beban
                </span>
            </div>
        </div>

        <!-- Card 3: Hasil Bersih (Laba / Rugi) -->
        <div class="is-metric-box" style="background:var(--az-50); border-color:var(--az-200);">
            <div class="is-metric-label" style="color:var(--az-800);">
                <i class="fa-solid <?= $laba_bersih >= 0 ? 'fa-chart-pie' : 'fa-triangle-exclamation' ?>" style="color:var(--az-600);"></i>
                <?= $laba_bersih >= 0 ? 'Laba Bersih' : 'Rugi Bersih' ?>
            </div>
            <div class="is-metric-value" style="color:<?= $laba_bersih >= 0 ? 'var(--az-700)' : '#dc2626' ?>;">
                <?= rupiah(abs($laba_bersih)) ?>
            </div>
            <div>
                <span class="is-metric-badge" style="background:<?= $laba_bersih >= 0 ? '#eff6ff' : '#fef2f2' ?>; color:<?= $laba_bersih >= 0 ? '#1d4ed8' : '#b91c1c' ?>; border:1px solid <?= $laba_bersih >= 0 ? '#bfdbfe' : '#fecaca' ?>;">
                    <i class="fa-solid fa-percent"></i> Margin: <?= number_format($margin_laba, 1, ',', '.') ?>%
                </span>
            </div>
        </div>
    </div>

    <!-- 3. TABEL VERTIKAL STANDAR AKUNTANSI -->
    <table class="is-table">
        <thead>
            <tr class="is-table-head">
                <th style="text-align:left;">Pos Akun / Kategori Transaksi</th>
                <th style="text-align:center; width:130px;">Jumlah Transaksi</th>
                <th style="text-align:right; width:220px;">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>

            <!-- SECTION I: PENDAPATAN OPERASIONAL -->
            <tr class="is-section-title">
                <td colspan="3">I. PENDAPATAN OPERASIONAL (PENJUALAN)</td>
            </tr>
            <?php if (count($rekap_pendapatan) > 0): ?>
                <?php foreach ($rekap_pendapatan as $in): ?>
                    <tr class="is-row-item">
                        <td>
                            <i class="fa-solid fa-coins" style="color:#059669; font-size:11px; margin-right:6px;"></i>
                            Pendapatan: <?= e($in['kategori']) ?>
                        </td>
                        <td style="text-align:center; color:var(--sl-600);">
                            <?= (int)$in['jml_transaksi'] ?> Transaksi
                        </td>
                        <td class="is-col-nominal" style="color:#047857;">
                            <?= rupiah($in['total']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="is-row-item">
                    <td colspan="3" style="font-style:italic; color:var(--sl-500); padding-left:32px;">
                        Tidak ada catatan pendapatan pada periode ini.
                    </td>
                </tr>
            <?php endif; ?>
            <tr class="is-row-subtotal">
                <td><strong>TOTAL PENDAPATAN OPERASIONAL</strong></td>
                <td style="text-align:center;"><strong><?= $total_trx_in ?> Transaksi</strong></td>
                <td class="is-col-nominal" style="color:#047857; font-weight:800;">
                    <?= rupiah($total_pendapatan) ?>
                </td>
            </tr>

            <!-- SECTION II: BEBAN OPERASIONAL -->
            <tr class="is-section-title">
                <td colspan="3">II. BEBAN OPERASIONAL &amp; BIAYA USAHA</td>
            </tr>
            <?php if (count($rekap_beban) > 0): ?>
                <?php foreach ($rekap_beban as $out): ?>
                    <tr class="is-row-item">
                        <td>
                            <i class="fa-solid fa-money-bill-wave" style="color:#dc2626; font-size:11px; margin-right:6px;"></i>
                            Beban: <?= e($out['kategori']) ?>
                        </td>
                        <td style="text-align:center; color:var(--sl-600);">
                            <?= (int)$out['jml_transaksi'] ?> Transaksi
                        </td>
                        <td class="is-col-nominal" style="color:#dc2626;">
                            <?= rupiah($out['total']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="is-row-item">
                    <td colspan="3" style="font-style:italic; color:var(--sl-500); padding-left:32px;">
                        Tidak ada catatan beban operasional pada periode ini.
                    </td>
                </tr>
            <?php endif; ?>
            <tr class="is-row-subtotal">
                <td><strong>TOTAL BEBAN OPERASIONAL</strong></td>
                <td style="text-align:center;"><strong><?= $total_trx_out ?> Transaksi</strong></td>
                <td class="is-col-nominal" style="color:#dc2626; font-weight:800;">
                    (<?= rupiah($total_beban) ?>)
                </td>
            </tr>

            <!-- SECTION III: LABA / RUGI BERSIH (DOUBLE UNDERLINE) -->
            <tr class="is-row-final">
                <td colspan="2">
                    <strong style="color:<?= $laba_bersih >= 0 ? '#047857' : '#dc2626' ?>;">
                        <?= $laba_bersih >= 0 ? 'LABA BERSIH PERIODE BERJALAN (SURPLUS)' : 'RUGI BERSIH PERIODE BERJALAN (DEFISIT)' ?>
                    </strong>
                    <div style="font-size:11.5px; font-weight:500; color:var(--sl-600);">
                        Total Pendapatan Usaha dikurangi Seluruh Beban Operasional
                    </div>
                </td>
                <td class="is-col-nominal" style="color:<?= $laba_bersih >= 0 ? '#047857' : '#dc2626' ?>; font-size:16px;">
                    <strong><?= $laba_bersih >= 0 ? '' : '(' ?><?= rupiah(abs($laba_bersih)) ?><?= $laba_bersih >= 0 ? '' : ')' ?></strong>
                </td>
            </tr>

        </tbody>
    </table>

    <!-- 4. FOOTER INFORMASI DOKUMEN (TANPA AREA TANDA TANGAN) -->
    <div class="is-card-footer">
        <div>
            <i class="fa-solid fa-circle-info" style="color:var(--az-500); margin-right:4px;"></i>
            Laporan disusun berdasarkan mutasi pembukuan kas riil per <strong><?= tanggal_panjang_id($akhir_bulan) ?></strong>.
        </div>
        <div class="is-badge-status" style="background:<?= $laba_bersih >= 0 ? '#ecfdf5' : '#fef2f2' ?>; color:<?= $laba_bersih >= 0 ? '#047857' : '#b91c1c' ?>; border:1px solid <?= $laba_bersih >= 0 ? '#a7f3d0' : '#fecaca' ?>;">
            <i class="fa-solid <?= $laba_bersih >= 0 ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            Status: <?= $laba_bersih >= 0 ? 'Surplus Operasional' : 'Defisit Operasional' ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

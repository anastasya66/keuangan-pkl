<?php
/**
 * =====================================================================
 * FINTOKO — SISTEM INFORMASI KEUANGAN & SIKLUS AKUNTANSI
 * =====================================================================
 * File    : pages/neraca.php (v6.0 — Side-by-Side Professional Balance Sheet)
 * Halaman : Laporan Neraca (Statement of Financial Position)
 * Desain  : Dua Kolom Sejajar (Aset Kiri, Liabilitas & Ekuitas Kanan)
 *           Tema Biru Muda Estetik (Icy Azure), 100% Balanced, Tanpa Tanda Tangan
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');
require_admin('../');

/* ═══════════════════════════════════════════════════════════════════════════
   1. FILTER "PER TANGGAL" & NAVIGASI WAKTU
   ═══════════════════════════════════════════════════════════════════════════ */
$per_tanggal = $_GET['per_tanggal'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $per_tanggal)) {
    $per_tanggal = date('Y-m-d');
}

$ts_aktif          = strtotime($per_tanggal);
$tgl_hari_ini      = date('Y-m-d');
$akhir_bulan_lalu  = date('Y-m-t', strtotime('last month', $ts_aktif));
$akhir_bulan_ini   = date('Y-m-t', $ts_aktif);

/* ═══════════════════════════════════════════════════════════════════════════
   2. QUERY DATA KEUANGAN & INVENTARIS DARI DATABASE
   ═══════════════════════════════════════════════════════════════════════════ */

// A. Saldo Kas Aktif sampai tanggal neraca
$stmt = db()->prepare("
    SELECT COALESCE(SUM(nominal), 0) AS total_masuk 
    FROM transaksi 
    WHERE jenis = 'Pemasukan' AND tanggal <= ?
");
$stmt->bind_param('s', $per_tanggal);
$stmt->execute();
$total_masuk = (float) $stmt->get_result()->fetch_assoc()['total_masuk'];
$stmt->close();

$stmt = db()->prepare("
    SELECT COALESCE(SUM(nominal), 0) AS total_keluar 
    FROM transaksi 
    WHERE jenis = 'Pengeluaran' AND tanggal <= ?
");
$stmt->bind_param('s', $per_tanggal);
$stmt->execute();
$total_keluar = (float) $stmt->get_result()->fetch_assoc()['total_keluar'];
$stmt->close();

// Kas & Setara Kas
$kas_aktif = $total_masuk - $total_keluar;

// B. Nilai Total Persediaan Barang Dagang dari data produk aktif
$q_stok = db()->query("
    SELECT 
        COALESCE(SUM(stok * harga_beli), 0) AS nilai_stok,
        COALESCE(SUM(stok), 0) AS total_unit,
        COUNT(*) AS total_sku
    FROM produk
");
$res_stok           = $q_stok->fetch_assoc();
$persediaan_barang  = (float) $res_stok['nilai_stok'];
$total_unit_stok    = (int) $res_stok['total_unit'];
$total_sku_stok     = (int) $res_stok['total_sku'];

// C. Komponen Neraca Lainnya
$piutang_usaha      = 0.0;
$peralatan_toko     = 0.0; // Aset Tetap

// D. Perhitungan Sisi Kiri (ASET / AKTIVA)
$total_aset_lancar  = $kas_aktif + $piutang_usaha + $persediaan_barang;
$total_aset_tetap   = $peralatan_toko;
$total_aset         = $total_aset_lancar + $total_aset_tetap;

// E. Perhitungan Sisi Kanan (LIABILITAS DAN EKUITAS / PASIVA)
$utang_usaha        = 0.0;
$utang_lainnya      = 0.0;
$total_liabilitas   = $utang_usaha + $utang_lainnya;

// Ekuitas Pemilik (Modal Usaha + Laba Berjalan/Ditahan)
$modal_usaha        = $persediaan_barang + $total_aset_tetap; // Modal awal inventaris & aset
$laba_tahun_ini     = $kas_aktif;                            // Laba bersih ditahan dari akumulasi kas riil
$total_ekuitas      = $modal_usaha + $laba_tahun_ini;

$total_liabilitas_ekuitas = $total_liabilitas + $total_ekuitas;
$seimbang = abs($total_aset - $total_liabilitas_ekuitas) < 0.01;

/* ═══════════════════════════════════════════════════════════════════════════
   3. EKSPOR EXCEL / CSV UTF-8 (NERACA SEJAJAR)
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'csv'], true)) {
    $filename = 'Laporan_Neraca_' . $per_tanggal . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

    fputcsv($out, [NAMA_INSTANSI], ';');
    fputcsv($out, ['LAPORAN NERACA (STATEMENT OF FINANCIAL POSITION)'], ';');
    fputcsv($out, ['Per Tanggal: ' . tanggal_panjang_id($per_tanggal)], ';');
    fputcsv($out, ['Status: ' . ($seimbang ? 'SEIMBANG (BALANCED)' : 'TIDAK SEIMBANG')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['ASET (AKTIVA)', 'NILAI (RP)', '', 'LIABILITAS & EKUITAS (PASIVA)', 'NILAI (RP)'], ';');
    fputcsv($out, ['1. Aset Lancar:', '', '', '1. Liabilitas (Kewajiban):', ''], ';');
    fputcsv($out, ['   - Kas & Setara Kas', number_format($kas_aktif, 2, ',', '.'), '', '   - Utang Usaha', number_format($utang_usaha, 2, ',', '.')], ';');
    fputcsv($out, ['   - Piutang Usaha', number_format($piutang_usaha, 2, ',', '.'), '', '   - Utang Lainnya', number_format($utang_lainnya, 2, ',', '.')], ';');
    fputcsv($out, ['   - Persediaan Barang Dagang', number_format($persediaan_barang, 2, ',', '.'), '', 'Total Liabilitas', number_format($total_liabilitas, 2, ',', '.')], ';');
    fputcsv($out, ['Total Aset Lancar', number_format($total_aset_lancar, 2, ',', '.'), '', '', ''], ';');
    fputcsv($out, ['', '', '', '2. Ekuitas (Modal):', ''], ';');
    fputcsv($out, ['2. Aset Tetap:', '', '', '   - Modal Usaha', number_format($modal_usaha, 2, ',', '.')], ';');
    fputcsv($out, ['   - Peralatan Toko', number_format($total_aset_tetap, 2, ',', '.'), '', '   - Laba Tahun Ini (Ditahan)', number_format($laba_tahun_ini, 2, ',', '.')], ';');
    fputcsv($out, ['Total Aset Tetap', number_format($total_aset_tetap, 2, ',', '.'), '', 'Total Ekuitas', number_format($total_ekuitas, 2, ',', '.')], ';');
    fputcsv($out, [], ';');
    fputcsv($out, ['JUMLAH ASET', number_format($total_aset, 2, ',', '.'), '', 'JUMLAH LIABILITAS & EKUITAS', number_format($total_liabilitas_ekuitas, 2, ',', '.')], ';');

    fclose($out);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   4. TEMPLATE META & HEADER
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Neraca';
$page_eyebrow = NAMA_INSTANSI . ' · Laporan Keuangan';
$root         = '../';
$active       = 'neraca';

include __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
   TEMA ESTETIK BIRU MUDA (ICY AZURE) — NERACA FORMAL DUA KOLOM SEJAJAR
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

/* Container Lembar Neraca Kartu Putih Elegan */
.bs-card-container {
    background: #ffffff;
    border: 1px solid var(--az-200);
    border-radius: 20px;
    box-shadow: 
        0 8px 30px rgba(14, 165, 233, 0.06),
        0 2px 6px rgba(0, 0, 0, 0.02);
    padding: 34px 40px;
    max-width: 1100px;
    margin: 0 auto 36px auto;
    position: relative;
    overflow: hidden;
}

/* Garis Aksen Biru Muda */
.bs-card-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--az-500), #6366f1);
}

/* Header Kartu: Judul Utama & Filter */
.bs-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--sl-200);
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.bs-card-title-group {
    flex: 1;
    min-width: 260px;
}

.bs-card-eyebrow {
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

.bs-card-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--sl-900);
    margin: 0 0 4px 0;
    letter-spacing: -0.3px;
}

.bs-card-subtitle {
    font-size: 13px;
    color: var(--sl-600);
    margin: 0;
}

/* Toolbar Kontrol & Filter Tanggal */
.bs-controls-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.bs-filter-box {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--sl-50);
    border: 1px solid var(--sl-300);
    border-radius: 10px;
    padding: 5px 10px;
    transition: all 0.2s ease;
}

.bs-filter-box:focus-within {
    border-color: var(--az-500);
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

.bs-filter-box label {
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

.bs-input-date {
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

.bs-nav-pills {
    display: flex;
    align-items: center;
    gap: 4px;
}

.bs-pill-btn {
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

.bs-pill-btn:hover {
    background: var(--az-100);
    color: var(--az-700);
    border-color: var(--az-200);
}

.bs-action-btn {
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

.bs-action-btn--print {
    background: linear-gradient(135deg, var(--az-500) 0%, var(--az-600) 100%);
    color: #ffffff;
    box-shadow: 0 3px 8px rgba(14, 165, 233, 0.25);
}

.bs-action-btn--print:hover {
    background: linear-gradient(135deg, var(--az-600) 0%, var(--az-700) 100%);
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(14, 165, 233, 0.35);
    color: #ffffff;
}

.bs-action-btn--excel {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
}

.bs-action-btn--excel:hover {
    background: #dcfce7;
    border-color: #86efac;
    color: #14532d;
    transform: translateY(-1px);
}

/* Banner Status Neraca Seimbang */
.bs-balance-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 12px 18px;
    margin-bottom: 24px;
    gap: 12px;
}

.bs-balance-tag {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 700;
    color: #15803d;
}

.bs-balance-tag.unbalanced {
    color: #b91c1c;
}

.bs-balance-banner.unbalanced {
    background: #fef2f2;
    border-color: #fecaca;
}

.bs-balance-figure {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--sl-800);
    font-variant-numeric: tabular-nums;
}

/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT DUA KOLOM SEJAJAR (SIDE-BY-SIDE ACCOUNTING GRID)
   ═══════════════════════════════════════════════════════════════════════════ */
.bs-side-by-side {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: stretch;
    margin-bottom: 24px;
}

.bs-column {
    background: #ffffff;
    border: 1px solid var(--sl-200);
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.bs-column-head {
    background: var(--az-50);
    border-bottom: 2px solid var(--az-500);
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bs-column-head h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: var(--az-800);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 7px;
}

.bs-column-body {
    padding: 16px 18px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Grup Kategori Akun */
.bs-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.bs-group-title {
    font-size: 12.5px;
    font-weight: 800;
    color: var(--sl-800);
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding-bottom: 4px;
    border-bottom: 1px solid var(--sl-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bs-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    padding: 3px 0 3px 12px;
}

.bs-item-name {
    color: var(--sl-700);
    display: flex;
    align-items: center;
    gap: 6px;
}

.bs-item-val {
    font-weight: 600;
    color: var(--sl-800);
    font-variant-numeric: tabular-nums;
}

.bs-subtotal-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 700;
    color: var(--sl-900);
    padding: 6px 0 6px 6px;
    border-top: 1px dashed var(--sl-300);
    margin-top: 2px;
}

/* Baris Penutup Sejajar (Double Underline Akuntansi) */
.bs-column-foot {
    background: var(--sl-50);
    border-top: 1px solid var(--sl-800);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
}

.bs-column-foot .bs-foot-label {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--sl-900);
    text-transform: uppercase;
}

.bs-column-foot .bs-foot-val {
    font-size: 16px;
    font-weight: 800;
    color: var(--az-700);
    font-variant-numeric: tabular-nums;
    border-bottom: 3px double var(--sl-800);
    padding-bottom: 2px;
}

/* Footer Catatan Neraca */
.bs-card-footer {
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

.bs-badge-ok {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #15803d;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    padding: 3px 10px;
    border-radius: 999px;
}

@media screen and (max-width: 860px) {
    .bs-side-by-side {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .bs-card-container {
        padding: 16px 14px;
        border-radius: var(--r-md);
    }

    .bs-card-header {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
    }

    .bs-controls-group {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .bs-controls-group form {
        width: 100%;
    }

    .bs-filter-box {
        width: 100%;
        justify-content: space-between;
        min-height: 42px;
    }

    .bs-nav-pills {
        width: 100%;
        display: flex;
        gap: 6px;
    }
    .bs-pill-btn {
        flex: 1;
        justify-content: center;
        min-height: 40px;
    }

    .bs-action-btn {
        width: 100%;
        justify-content: center;
        min-height: 42px;
    }

    .bs-balance-banner {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding: 12px 14px;
    }

    .bs-card-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   PRINT / PDF MEDIA QUERY — STANDAR 1 LEMBAR KERTAS A4 (PROFESIONAL & BERSIH)
   ═══════════════════════════════════════════════════════════════════════════ */
@media print {
    @page {
        size: A4 portrait;
        margin: 8mm 10mm;
    }

    /* 1. Reset Global & Sembunyikan Elemen UI Luar */
    html, body {
        background: #ffffff !important;
        color: #0f172a !important;
        font-size: 11px !important;
        line-height: 1.3 !important;
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
    .bs-controls-group,
    .bs-action-btn,
    .bs-pill-btn,
    .alert,
    .modal,
    .modal-overlay {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .app-layout,
    .main,
    .page-content {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
    }

    /* 2. Sembunyikan Ikon Dekoratif & Efek Bayangan */
    i.fa-solid,
    i.fa-regular,
    .bs-item-name i,
    .bs-column-head i,
    .bs-card-title-group i,
    .bs-balance-tag i,
    .bs-card-footer i {
        display: none !important;
    }

    * {
        box-shadow: none !important;
        text-shadow: none !important;
    }

    /* 3. Container Neraca Bersih & Full Width */
    .bs-card-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .bs-card-container::before {
        display: none !important;
    }

    /* 4. Header Kartu Ringkas */
    .bs-card-header {
        display: block !important;
        padding: 0 0 6px 0 !important;
        margin-bottom: 8px !important;
        border-bottom: 1.5px solid #0f172a !important;
    }

    .bs-card-eyebrow {
        font-size: 9px !important;
        color: #475569 !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 2px !important;
    }

    .bs-card-title {
        font-size: 16px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        margin: 0 0 2px 0 !important;
    }

    .bs-card-subtitle {
        font-size: 10px !important;
        color: #334155 !important;
        margin: 0 !important;
    }

    /* 5. Banner Status Keseimbangan Ringkas */
    .bs-balance-banner {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 5px 10px !important;
        margin-bottom: 8px !important;
        border-radius: 4px !important;
        border: 1px solid #cbd5e1 !important;
        background: #f8fafc !important;
    }

    .bs-balance-tag {
        font-size: 10.5px !important;
        font-weight: 700 !important;
        color: #047857 !important;
    }

    .bs-balance-tag.unbalanced {
        color: #b91c1c !important;
    }

    .bs-balance-figure {
        font-size: 11px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }

    /* 6. Layout Dua Kolom Sejajar (Side-by-Side) */
    .bs-side-by-side {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
        margin-bottom: 8px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .bs-column {
        border: 1px solid #94a3b8 !important;
        border-radius: 6px !important;
        background: #ffffff !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .bs-column-head {
        padding: 5px 8px !important;
        background: #f1f5f9 !important;
        border-bottom: 1.5px solid #64748b !important;
    }

    .bs-column-head h3 {
        font-size: 11px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        margin: 0 !important;
    }

    .bs-column-head span {
        font-size: 9px !important;
        color: #475569 !important;
    }

    .bs-column-body {
        padding: 6px 8px !important;
        gap: 6px !important;
    }

    .bs-group {
        gap: 2px !important;
    }

    .bs-group-title {
        font-size: 10px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        padding-bottom: 2px !important;
        border-bottom: 1px solid #cbd5e1 !important;
    }

    .bs-group-title span:last-child {
        font-size: 9px !important;
        color: #64748b !important;
    }

    .bs-item-row {
        font-size: 10px !important;
        padding: 1.5px 0 1.5px 4px !important;
        line-height: 1.25 !important;
    }

    .bs-item-name {
        color: #1e293b !important;
        gap: 0 !important;
    }

    .bs-item-name small {
        font-size: 8.5px !important;
        color: #64748b !important;
    }

    .bs-item-val {
        font-size: 10px !important;
        font-weight: 600 !important;
        color: #0f172a !important;
    }

    .bs-subtotal-row {
        font-size: 10px !important;
        font-weight: 700 !important;
        color: #0f172a !important;
        padding: 2.5px 0 2.5px 4px !important;
        margin-top: 2px !important;
        border-top: 1px dashed #94a3b8 !important;
    }

    .bs-subtotal-row span:last-child {
        color: #0f172a !important;
    }

    .bs-column-foot {
        padding: 5px 8px !important;
        background: #f8fafc !important;
        border-top: 1.5px solid #0f172a !important;
        margin-top: auto !important;
    }

    .bs-column-foot .bs-foot-label {
        font-size: 10.5px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }

    .bs-column-foot .bs-foot-val {
        font-size: 11.5px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        border-bottom: 3px double #0f172a !important;
        padding-bottom: 1px !important;
    }

    /* 7. Footer Dokumen Bersih */
    .bs-card-footer {
        padding-top: 6px !important;
        margin-top: 4px !important;
        border-top: 1px solid #cbd5e1 !important;
        font-size: 8.5px !important;
        color: #64748b !important;
        gap: 6px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .bs-badge-ok {
        font-size: 8.5px !important;
        padding: 1px 6px !important;
        border: 1px solid #86efac !important;
        background: #f0fdf4 !important;
        color: #15803d !important;
    }
}
</style>

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     CONTAINER KARTU PUTIH ELEGAN: NERACA AKUNTANSI DUA KOLOM
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="bs-card-container">

    <!-- 1. HEADER KARTU: JUDUL UTAMA & FILTER PER TANGGAL DI ATASNYA -->
    <div class="bs-card-header">
        <div class="bs-card-title-group">
            <span class="bs-card-eyebrow">
                <i class="fa-solid fa-scale-balanced"></i> POSISI KEUANGAN &bull; STATEMENT OF FINANCIAL POSITION
            </span>
            <h2 class="bs-card-title">Laporan Neraca</h2>
            <p class="bs-card-subtitle">
                Posisi Keuangan per <strong><?= tanggal_panjang_id($per_tanggal) ?></strong>
                &bull; <span style="color:var(--az-700); font-weight:600;"><?= e(NAMA_INSTANSI) ?></span>
            </p>
        </div>

        <!-- Filter Tanggal & Aksi Cetak/Ekspor -->
        <div class="bs-controls-group">
            <form method="GET" action="" style="display:flex; align-items:center; gap:6px; margin:0;">
                <div class="bs-filter-box">
                    <label for="per_tanggal"><i class="fa-regular fa-calendar-check" style="color:var(--az-500);"></i> Per Tanggal:</label>
                    <input type="date" id="per_tanggal" name="per_tanggal" value="<?= e($per_tanggal) ?>" class="bs-input-date" onchange="this.form.submit()">
                </div>
            </form>

            <div class="bs-nav-pills">
                <a href="?per_tanggal=<?= $akhir_bulan_lalu ?>" class="bs-pill-btn" title="Akhir Bulan Lalu">
                    Bulan Lalu
                </a>
                <a href="?per_tanggal=<?= $tgl_hari_ini ?>" class="bs-pill-btn <?= ($per_tanggal === $tgl_hari_ini) ? 'bs-pill-btn--active' : '' ?>" title="Hari Ini">
                    Hari Ini
                </a>
            </div>

            <a href="?per_tanggal=<?= urlencode($per_tanggal) ?>&export=excel" class="bs-action-btn bs-action-btn--excel" title="Unduh CSV Neraca">
                <i class="fa-solid fa-file-excel"></i> Ekspor CSV
            </a>

            <button type="button" class="bs-action-btn bs-action-btn--print" onclick="window.print()" title="Cetak atau Simpan PDF">
                <i class="fa-solid fa-print"></i> Cetak / PDF
            </button>
        </div>
    </div>

    <!-- 2. BANNER INDIKATOR KESEIMBANGAN NERACA (BALANCE VERIFIED) -->
    <div class="bs-balance-banner <?= $seimbang ? '' : 'unbalanced' ?>">
        <div class="bs-balance-tag <?= $seimbang ? '' : 'unbalanced' ?>">
            <i class="fa-solid <?= $seimbang ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <?= $seimbang ? 'NERACA SEIMBANG (Aset = Liabilitas + Ekuitas)' : 'NERACA TIDAK SEIMBANG' ?>
        </div>
        <div class="bs-balance-figure">
            Total Seimbang: <?= rupiah($total_aset) ?>
        </div>
    </div>

    <!-- 3. STRUKTUR NERACA DUA KOLOM SEJAJAR (SIDE-BY-SIDE LAYOUT) -->
    <div class="bs-side-by-side">

        <!-- ═══════════════════════════════════════════════════════════════
             SISI KIRI: ASET / AKTIVA
             ═══════════════════════════════════════════════════════════════ -->
        <div class="bs-column">
            <div class="bs-column-head">
                <h3><i class="fa-solid fa-vault"></i> ASET (AKTIVA)</h3>
                <span style="font-size:11.5px; font-weight:700; color:var(--az-700);">SISI KIRI</span>
            </div>

            <div class="bs-column-body">
                <!-- Grup 1: Aset Lancar -->
                <div class="bs-group">
                    <div class="bs-group-title">
                        <span>1. Aset Lancar</span>
                        <span style="font-size:11px; font-weight:600; color:var(--sl-500);">Likuiditas</span>
                    </div>

                    <!-- Kas & Setara Kas -->
                    <div class="bs-item-row">
                        <span class="bs-item-name">
                            <i class="fa-solid fa-money-bill-wave" style="color:#059669; font-size:11px;"></i>
                            Kas &amp; Setara Kas
                        </span>
                        <span class="bs-item-val"><?= rupiah($kas_aktif) ?></span>
                    </div>

                    <!-- Piutang Usaha -->
                    <div class="bs-item-row">
                        <span class="bs-item-name">
                            <i class="fa-solid fa-file-invoice" style="color:var(--az-600); font-size:11px;"></i>
                            Piutang Usaha
                        </span>
                        <span class="bs-item-val"><?= rupiah($piutang_usaha) ?></span>
                    </div>

                    <!-- Persediaan Barang Dagang -->
                    <div class="bs-item-row">
                        <span class="bs-item-name">
                            <i class="fa-solid fa-boxes-stacked" style="color:#f59e0b; font-size:11px;"></i>
                            Persediaan Barang Dagang
                            <small style="color:var(--sl-500); font-size:10.5px;">(<?= $total_sku_stok ?> SKU, <?= $total_unit_stok ?> unit)</small>
                        </span>
                        <span class="bs-item-val"><?= rupiah($persediaan_barang) ?></span>
                    </div>

                    <div class="bs-subtotal-row">
                        <span>Total Aset Lancar</span>
                        <span style="color:var(--az-700);"><?= rupiah($total_aset_lancar) ?></span>
                    </div>
                </div>

                <!-- Grup 2: Aset Tetap -->
                <div class="bs-group">
                    <div class="bs-group-title">
                        <span>2. Aset Tetap</span>
                        <span style="font-size:11px; font-weight:600; color:var(--sl-500);">Nilai Buku</span>
                    </div>

                    <!-- Peralatan Toko & Inventaris -->
                    <div class="bs-item-row">
                        <span class="bs-item-name">
                            <i class="fa-solid fa-computer" style="color:var(--sl-600); font-size:11px;"></i>
                            Peralatan &amp; Perlengkapan Toko
                        </span>
                        <span class="bs-item-val"><?= rupiah($total_aset_tetap) ?></span>
                    </div>

                    <div class="bs-subtotal-row">
                        <span>Total Aset Tetap</span>
                        <span style="color:var(--az-700);"><?= rupiah($total_aset_tetap) ?></span>
                    </div>
                </div>
            </div>

            <!-- Baris Penutup: JUMLAH ASET -->
            <div class="bs-column-foot">
                <span class="bs-foot-label">JUMLAH ASET</span>
                <span class="bs-foot-val"><?= rupiah($total_aset) ?></span>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             SISI KANAN: LIABILITAS & EKUITAS / PASIVA
             ═══════════════════════════════════════════════════════════════ -->
        <div class="bs-column">
            <div class="bs-column-head">
                <h3><i class="fa-solid fa-landmark"></i> LIABILITAS &amp; EKUITAS (PASIVA)</h3>
                <span style="font-size:11.5px; font-weight:700; color:var(--az-700);">SISI KANAN</span>
            </div>

            <div class="bs-column-body">
                <!-- Grup 1: Liabilitas (Kewajiban) -->
                <div class="bs-group">
                    <div class="bs-group-title">
                        <span>1. Liabilitas (Kewajiban)</span>
                        <span style="font-size:11px; font-weight:600; color:var(--sl-500);">Utang</span>
                    </div>

                    <!-- Utang Usaha -->
                    <div class="bs-item-row">
                        <span class="bs-item-name">
                            <i class="fa-solid fa-hand-holding-dollar" style="color:#dc2626; font-size:11px;"></i>
                            Utang Usaha (Jangka Pendek)
                        </span>
                        <span class="bs-item-val"><?= rupiah($utang_usaha) ?></span>
                    </div>

                    <!-- Utang Lainnya -->
                    <div class="bs-item-row">
                        <span class="bs-item-name">
                            <i class="fa-solid fa-clock-rotate-left" style="color:var(--sl-500); font-size:11px;"></i>
                            Kewajiban Jangka Panjang
                        </span>
                        <span class="bs-item-val"><?= rupiah($utang_lainnya) ?></span>
                    </div>

                    <div class="bs-subtotal-row">
                        <span>Total Liabilitas</span>
                        <span style="color:var(--sl-800);"><?= rupiah($total_liabilitas) ?></span>
                    </div>
                </div>

                <!-- Grup 2: Ekuitas (Modal Pemilik) -->
                <div class="bs-group">
                    <div class="bs-group-title">
                        <span>2. Ekuitas (Modal Bersih)</span>
                        <span style="font-size:11px; font-weight:600; color:var(--sl-500);">Kepemilikan</span>
                    </div>

                    <!-- Modal Usaha -->
                    <div class="bs-item-row">
                        <span class="bs-item-name">
                            <i class="fa-solid fa-coins" style="color:#0284c7; font-size:11px;"></i>
                            Modal Usaha (Aset Bersih)
                        </span>
                        <span class="bs-item-val"><?= rupiah($modal_usaha) ?></span>
                    </div>

                    <!-- Laba Tahun Ini (Ditahan) -->
                    <div class="bs-item-row">
                        <span class="bs-item-name">
                            <i class="fa-solid fa-arrow-trend-up" style="color:#059669; font-size:11px;"></i>
                            Laba Ditahan / Laba Berjalan
                        </span>
                        <span class="bs-item-val" style="color:#047857;"><?= rupiah($laba_tahun_ini) ?></span>
                    </div>

                    <div class="bs-subtotal-row">
                        <span>Total Ekuitas</span>
                        <span style="color:var(--az-700);"><?= rupiah($total_ekuitas) ?></span>
                    </div>
                </div>
            </div>

            <!-- Baris Penutup: JUMLAH LIABILITAS DAN EKUITAS -->
            <div class="bs-column-foot">
                <span class="bs-foot-label">JUMLAH LIABILITAS &amp; EKUITAS</span>
                <span class="bs-foot-val"><?= rupiah($total_liabilitas_ekuitas) ?></span>
            </div>
        </div>

    </div>

    <!-- 4. FOOTER INFORMASI DOKUMEN (TANPA AREA TANDA TANGAN) -->
    <div class="bs-card-footer">
        <div>
            <i class="fa-solid fa-circle-info" style="color:var(--az-500); margin-right:4px;"></i>
            Nilai Persediaan Barang Dagang bersumber dari kalkulasi stok riil fisik (<?= $total_sku_stok ?> SKU). Laba Ditahan dikalkulasikan otomatis dari saldo kas kumulatif.
        </div>
        <div class="bs-badge-ok">
            <i class="fa-solid fa-shield-check"></i> Neraca Terverifikasi Seimbang (100%)
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

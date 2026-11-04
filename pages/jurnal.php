<?php
/**
 * =====================================================================
 * FINTOKO — SISTEM INFORMASI KEUANGAN & SIKLUS AKUNTANSI
 * =====================================================================
 * File    : pages/jurnal.php (v7.0 — Real-Time AJAX Month Filtering)
 * Halaman : Jurnal Umum (General Journal - Double-Entry Bookkeeping)
 * Fitur   : 1. Live Filter Bulan & Navigasi Cepat berbasis AJAX (Zero Page Reload)
 *           2. Endpoint Asinkron JSON dengan partial HTML render
 *           3. Professional Accounting Sheet Container (Lembar Kerja Putih)
 *           4. Kop Judul Perusahaan Formal & Terstruktur
 *           5. 4 Metric Summary Cards Glassmorphism
 *           6. Clean Double-Entry Table (Indent Kredit, Kolom Presisi)
 *           7. Validasi Total Jurnal & Audit Keseimbangan (Balanced Check)
 *           8. Standar Cetak Kertas A4 Otomatis (@media print)
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

// Navigasi cepat bulan sebelumnya, bulan ini, dan bulan berikutnya
$ts_aktif          = strtotime($bulan . '-01');
$bulan_sebelumnya  = date('Y-m', strtotime('-1 month', $ts_aktif));
$bulan_berikutnya  = date('Y-m', strtotime('+1 month', $ts_aktif));
$bulan_ini         = date('Y-m');

/* ═══════════════════════════════════════════════════════════════════════════
   2. QUERY DATA TRANSAKSI JURNAL UMUM
   ═══════════════════════════════════════════════════════════════════════════ */
$stmt = db()->prepare('
    SELECT * FROM transaksi
    WHERE YEAR(tanggal) = ? AND MONTH(tanggal) = ?
    ORDER BY tanggal ASC, id ASC
');
$stmt->bind_param('ii', $tahun_f, $bulan_f);
$stmt->execute();
$data_jurnal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ═══════════════════════════════════════════════════════════════════════════
   3. EKSPOR EXCEL / CSV UTF-8 (JURNAL UMUM BERPASANGAN)
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'csv'], true)) {
    $filename = 'Jurnal_Umum_' . $bulan . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, [NAMA_INSTANSI], ';');
    fputcsv($out, ['LAPORAN JURNAL UMUM (GENERAL JOURNAL)'], ';');
    fputcsv($out, ['Periode: ' . bulan_tahun_id($bulan)], ';');
    fputcsv($out, ['Dicetak pada: ' . date('d/m/Y H:i:s')], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['Tanggal', 'No. Bukti', 'Keterangan & Nama Akun', 'Ref', 'Debit (Rp)', 'Kredit (Rp)'], ';');

    $exp_debit  = 0.0;
    $exp_kredit = 0.0;

    foreach ($data_jurnal as $t) {
        $nominal = (float) $t['nominal'];
        $ref_trx = 'TRX-' . str_pad($t['id'], 4, '0', STR_PAD_LEFT);
        $tgl     = tanggal_id($t['tanggal']);
        $memo    = $t['keterangan'] ?: ('Transaksi ' . $t['jenis'] . ' ' . $t['kategori']);

        if ($t['jenis'] === 'Pemasukan') {
            fputcsv($out, [$tgl, $ref_trx, 'Kas', '101', number_format($nominal, 2, ',', '.'), '0,00'], ';');
            fputcsv($out, ['', '', '    Pendapatan - ' . $t['kategori'] . ' (' . $memo . ')', '401', '0,00', number_format($nominal, 2, ',', '.')], ';');
            $exp_debit  += $nominal;
            $exp_kredit += $nominal;
        } else {
            fputcsv($out, [$tgl, $ref_trx, 'Beban - ' . $t['kategori'], '501', number_format($nominal, 2, ',', '.'), '0,00'], ';');
            fputcsv($out, ['', '', '    Kas (' . $memo . ')', '101', '0,00', number_format($nominal, 2, ',', '.')], ';');
            $exp_debit  += $nominal;
            $exp_kredit += $nominal;
        }
    }

    fputcsv($out, [], ';');
    fputcsv($out, ['TOTAL JURNAL', '', '', '', number_format($exp_debit, 2, ',', '.'), number_format($exp_kredit, 2, ',', '.')], ';');
    fclose($out);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   4. KALKULASI TOTAL DEBIT, KREDIT & VALIDASI AUDIT
   ═══════════════════════════════════════════════════════════════════════════ */
$total_debit  = 0.0;
$total_kredit = 0.0;

foreach ($data_jurnal as $t) {
    $nom = (float) $t['nominal'];
    if ($t['jenis'] === 'Pemasukan') {
        $total_debit  += $nom;
        $total_kredit += $nom;
    } else {
        $total_debit  += $nom;
        $total_kredit += $nom;
    }
}

$selisih     = abs($total_debit - $total_kredit);
$is_balanced = $selisih < 0.01;
$total_trx   = count($data_jurnal);

/* ═══════════════════════════════════════════════════════════════════════════
   HELPER RENDER PARSIAL (Digunakan SSR dan Endpoint AJAX)
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Render 4 kartu metrik jurnal umum
 */
function render_jurnal_metrics(int $total_trx, float $total_debit, float $total_kredit, bool $is_balanced, string $bulan): string
{
    ob_start();
    ?>
    <!-- Card 1: Total Transaksi -->
    <div class="jrn-metric-card jrn-metric-card--primary">
        <div class="jrn-metric-icon">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="jrn-metric-body">
            <div class="jrn-metric-label">Total Transaksi</div>
            <div class="jrn-metric-value"><?= number_format($total_trx, 0, ',', '.') ?> <span style="font-size:13px; font-weight:600; color:#64748b;">Entri</span></div>
            <div class="jrn-metric-note">
                <i class="fa-solid fa-calendar-day"></i> Periode <?= bulan_tahun_id($bulan) ?>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Debit -->
    <div class="jrn-metric-card jrn-metric-card--debit">
        <div class="jrn-metric-icon">
            <i class="fa-solid fa-arrow-down-left"></i>
        </div>
        <div class="jrn-metric-body">
            <div class="jrn-metric-label">Total Debit</div>
            <div class="jrn-metric-value" style="color:#047857;"><?= rupiah($total_debit) ?></div>
            <div class="jrn-metric-note">
                <span class="jrn-badge-pill jrn-badge-pill--success"><i class="fa-solid fa-plus"></i> Arus Debit Masuk</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Kredit -->
    <div class="jrn-metric-card jrn-metric-card--kredit">
        <div class="jrn-metric-icon">
            <i class="fa-solid fa-arrow-up-right"></i>
        </div>
        <div class="jrn-metric-body">
            <div class="jrn-metric-label">Total Kredit</div>
            <div class="jrn-metric-value" style="color:#0369a1;"><?= rupiah($total_kredit) ?></div>
            <div class="jrn-metric-note">
                <span class="jrn-badge-pill" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;"><i class="fa-solid fa-arrow-right"></i> Arus Kredit Keluar</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Keseimbangan Debit vs Kredit -->
    <div class="jrn-metric-card jrn-metric-card--balance">
        <div class="jrn-metric-icon">
            <i class="fa-solid fa-scale-balanced"></i>
        </div>
        <div class="jrn-metric-body">
            <div class="jrn-metric-label">Status Keseimbangan</div>
            <div class="jrn-metric-value" style="font-size:16px;">
                <?php if ($is_balanced): ?>
                    <span style="color:#059669;"><i class="fa-solid fa-circle-check"></i> 100% Balanced</span>
                <?php else: ?>
                    <span style="color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i> Tidak Seimbang</span>
                <?php endif; ?>
            </div>
            <div class="jrn-metric-note">
                <?php if ($is_balanced): ?>
                    <span class="jrn-badge-pill jrn-badge-pill--success">Selisih: Rp 0</span>
                <?php else: ?>
                    <span class="jrn-badge-pill jrn-badge-pill--danger">Selisih: <?= rupiah(abs($total_debit - $total_kredit)) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render lembar kerja jurnal umum (#jrnSheet)
 */
function render_jurnal_sheet(string $bulan, string $tahun_f, string $bulan_f, array $data_jurnal, float $total_debit, float $total_kredit, bool $is_balanced, int $total_trx): string
{
    ob_start();
    ?>
    <!-- KOP PERUSAHAAN RESMI -->
    <div class="jrn-letterhead">
        <div class="jrn-letterhead__brand">
            <div class="jrn-brand-logo">
                <i class="fa-solid fa-vault"></i>
            </div>
            <div class="jrn-brand-info">
                <h1 class="jrn-company-name"><?= e(NAMA_INSTANSI) ?></h1>
                <div class="jrn-company-tagline">Sistem Informasi Akuntansi &amp; Keuangan Toko</div>
                <div class="jrn-company-address">
                    <i class="fa-solid fa-location-dot" style="color:var(--az-500); margin-right:4px;"></i>
                    <?= e(ALAMAT_INSTANSI) ?>
                </div>
            </div>
        </div>

        <div class="jrn-doc-meta">
            <div class="jrn-doc-ref">No. Ref Jurnal</div>
            <div class="jrn-doc-ref-code">JU/<?= $tahun_f ?>/<?= $bulan_f ?></div>
            <div class="jrn-doc-date">
                <i class="fa-regular fa-clock" style="margin-right:4px;"></i>
                Dicetak: <?= tanggal_panjang_id() ?>
            </div>
        </div>
    </div>

    <div class="jrn-kop-divider">
        <div class="jrn-kop-divider__thick"></div>
        <div class="jrn-kop-divider__thin"></div>
    </div>

    <div class="jrn-title-banner">
        <h2 class="jrn-title-main">JURNAL UMUM</h2>
        <div class="jrn-title-en">GENERAL JOURNAL</div>
        <div>
            <span class="jrn-title-period">
                <i class="fa-regular fa-calendar-check" style="color:var(--az-600);"></i>
                Periode: <?= bulan_tahun_id($bulan) ?>
            </span>
        </div>
    </div>

    <div class="jrn-table-container">
        <table class="jrn-table">
            <thead>
                <tr>
                    <th class="col-date">Tanggal</th>
                    <th class="col-ref">No. Bukti</th>
                    <th class="col-account">Keterangan Akun &amp; Transaksi</th>
                    <th class="col-pos">Ref</th>
                    <th class="col-debit">Debit (Rp)</th>
                    <th class="col-kredit">Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_trx > 0): ?>
                    <?php foreach ($data_jurnal as $idx => $t): ?>
                        <?php
                        $nominal = (float) $t['nominal'];
                        $ref_trx = 'TRX-' . str_pad($t['id'], 4, '0', STR_PAD_LEFT);
                        $memo    = $t['keterangan'] ?: ('Transaksi ' . $t['jenis'] . ' ' . $t['kategori']);

                        if ($t['jenis'] === 'Pemasukan') {
                            $akun_debit  = 'Kas';
                            $ref_debit   = '101';
                            $akun_kredit = 'Pendapatan - ' . $t['kategori'];
                            $ref_kredit  = '401';
                        } else {
                            $akun_debit  = 'Beban - ' . $t['kategori'];
                            $ref_debit   = '501';
                            $akun_kredit = 'Kas';
                            $ref_kredit  = '101';
                        }
                        ?>
                        <tr class="trx-row-first">
                            <td rowspan="2" class="cell-spanned">
                                <span class="jrn-date-badge"><?= tanggal_id($t['tanggal']) ?></span>
                            </td>
                            <td rowspan="2" class="cell-spanned">
                                <span class="jrn-ref-badge"><?= e($ref_trx) ?></span>
                            </td>
                            <td>
                                <div class="jrn-account-debit">
                                    <i class="fa-solid fa-circle-dot"></i>
                                    <span><?= e($akun_debit) ?></span>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span class="jrn-pos-badge"><?= e($ref_debit) ?></span>
                            </td>
                            <td class="jrn-amount jrn-amount--debit">
                                <?= rupiah($nominal) ?>
                            </td>
                            <td>
                                <span class="jrn-amount--dash">&mdash;</span>
                            </td>
                        </tr>

                        <tr class="trx-row-last">
                            <td>
                                <div class="jrn-account-kredit">
                                    <span><?= e($akun_kredit) ?></span>
                                </div>
                                <?php if (!empty($memo)): ?>
                                    <div class="jrn-memo">
                                        (<?= e($memo) ?>)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <span class="jrn-pos-badge"><?= e($ref_kredit) ?></span>
                            </td>
                            <td>
                                <span class="jrn-amount--dash">&mdash;</span>
                            </td>
                            <td class="jrn-amount jrn-amount--kredit">
                                <?= rupiah($nominal) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="jrn-empty-state">
                            <i class="fa-solid fa-book-open"></i>
                            <p>Tidak Ada Transaksi di Periode Ini</p>
                            <span>Belum ada transaksi kas yang dicatat untuk bulan <?= bulan_tahun_id($bulan) ?>. Silakan pilih bulan lain atau catat transaksi kas baru.</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="jrn-total-label">
                        <i class="fa-solid fa-calculator" style="margin-right:6px;"></i> TOTAL JURNAL
                    </td>
                    <td class="jrn-total-value jrn-total-value--debit">
                        <?= rupiah($total_debit) ?>
                    </td>
                    <td class="jrn-total-value jrn-total-value--kredit">
                        <?= rupiah($total_kredit) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="jrn-audit-banner <?= $is_balanced ? 'jrn-audit-banner--balanced' : 'jrn-audit-banner--unbalanced' ?>">
        <div class="jrn-audit-status">
            <?php if ($is_balanced): ?>
                <i class="fa-solid fa-circle-check"></i>
                <span>JURNAL SEIMBANG (BALANCED)</span>
            <?php else: ?>
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>JURNAL TIDAK SEIMBANG (UNBALANCED)</span>
            <?php endif; ?>
        </div>
        <div class="jrn-audit-desc">
            <?php if ($is_balanced): ?>
                Total sisi Debit dan sisi Kredit bernilai sama (selisih Rp 0). Prinsip akuntansi berpasangan terpenuhi sempurna.
            <?php else: ?>
                Terdapat selisih sebesar <strong><?= rupiah($selisih) ?></strong> antara total debit dan total kredit. Mohon verifikasi transaksi.
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render navigasi bulan cepat
 */
function render_jurnal_quick_nav(string $bulan, string $bulan_ini, string $bulan_sebelumnya, string $bulan_berikutnya): string
{
    ob_start();
    ?>
    <a href="?bulan=<?= $bulan_sebelumnya ?>" class="jrn-btn-nav" data-bulan="<?= $bulan_sebelumnya ?>" title="Bulan Sebelumnya">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <a href="?bulan=<?= $bulan_ini ?>" class="jrn-btn-nav <?= ($bulan === $bulan_ini) ? 'jrn-btn-nav--active' : '' ?>" data-bulan="<?= $bulan_ini ?>">
        Bulan Ini
    </a>
    <a href="?bulan=<?= $bulan_berikutnya ?>" class="jrn-btn-nav" data-bulan="<?= $bulan_berikutnya ?>" title="Bulan Berikutnya">
        <i class="fa-solid fa-chevron-right"></i>
    </a>
    <?php
    return ob_get_clean();
}

/* ═══════════════════════════════════════════════════════════════════════════
   [FITUR AJAX] ENDPOINT ASINKRON TANPA REFRESH
   ═══════════════════════════════════════════════════════════════════════════ */
$is_ajax = (isset($_GET['ajax']) && $_GET['ajax'] == '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($is_ajax) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    echo json_encode([
        'status'         => 'success',
        'bulan'          => $bulan,
        'html_metrics'   => render_jurnal_metrics($total_trx, $total_debit, $total_kredit, $is_balanced, $bulan),
        'html_sheet'     => render_jurnal_sheet($bulan, $tahun_f, $bulan_f, $data_jurnal, $total_debit, $total_kredit, $is_balanced, $total_trx),
        'html_quick_nav' => render_jurnal_quick_nav($bulan, $bulan_ini, $bulan_sebelumnya, $bulan_berikutnya),
        'export_url'     => '?bulan=' . urlencode($bulan) . '&export=excel',
    ]);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   TEMPLATE META & HEADER
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Jurnal Umum';
$page_eyebrow = NAMA_INSTANSI . ' · Siklus Akuntansi';
$root         = '../';
$active       = 'jurnal';

include __DIR__ . '/../includes/header.php';
?>

<!-- Stylesheet Terdedikasi Jurnal Umum v7.0 — Icy Azure Enterprise -->
<link rel="stylesheet" href="../assets/css/jurnal.css">

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     HERO BANNER SIKLUS AKUNTANSI
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="jrn-hero" id="jrnHero">
    <div class="jrn-hero__left">
        <div class="jrn-eyebrow">
            <i class="fa-solid fa-book-journal-whills"></i> SIKLUS AKUNTANSI KEUANGAN
        </div>
        <h2 class="jrn-hero__title">Jurnal Umum (General Journal)</h2>
        <p class="jrn-hero__sub">
            Pencatatan berpasangan (<em>double-entry bookkeeping</em>) seluruh transaksi kas FinToko periode 
            <strong id="heroPeriodeText"><?= bulan_tahun_id($bulan) ?></strong> dengan pemisahan debit dan kredit standar akuntansi.
        </p>
    </div>
    <div class="jrn-hero__actions">
        <a href="?bulan=<?= urlencode($bulan) ?>&export=excel" class="jrn-btn jrn-btn--excel" id="btnExportJurnal" title="Unduh Spreadsheet Excel/CSV">
            <i class="fa-solid fa-file-excel"></i> Ekspor CSV
        </a>
        <button type="button" class="jrn-btn jrn-btn--primary" onclick="window.print()" title="Cetak Lembar Jurnal Umum">
            <i class="fa-solid fa-print"></i> Cetak Jurnal
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     4 KARTU METRIK RINGKASAN AKUNTANSI (METRIC CARDS)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="jrn-metrics" id="jrnMetrics">
    <?= render_jurnal_metrics($total_trx, $total_debit, $total_kredit, $is_balanced, $bulan) ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     SMART TOOLBAR (FILTER BULAN & QUICK SHORTCUTS)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="jrn-toolbar">
    <div class="jrn-toolbar__left">
        <form method="GET" class="jrn-filter-form" id="jrnFilterForm" action="" onsubmit="return false;">
            <div class="jrn-input-group">
                <label for="bulan"><i class="fa-regular fa-calendar"></i> Periode:</label>
                <input type="month" id="bulan" name="bulan" value="<?= e($bulan) ?>" class="jrn-input-month">
            </div>
            <button type="button" class="jrn-btn jrn-btn--outline" id="btnApplyMonthJrn" title="Tampilkan Jurnal Bulan Ini">
                <i class="fa-solid fa-filter"></i> Tampilkan
            </button>
        </form>

        <div class="jrn-quick-nav" id="jrnQuickNav">
            <?= render_jurnal_quick_nav($bulan, $bulan_ini, $bulan_sebelumnya, $bulan_berikutnya) ?>
        </div>
    </div>

    <div class="jrn-toolbar__right">
        <a href="?bulan=<?= urlencode($bulan) ?>&export=excel" class="jrn-btn jrn-btn--excel" id="btnExportJurnalToolbar" title="Ekspor data jurnal ke berkas spreadsheet">
            <i class="fa-solid fa-file-csv"></i> Unduh CSV
        </a>
        <button type="button" class="jrn-btn jrn-btn--primary" onclick="window.print()" title="Cetak atau Simpan PDF Lembar Jurnal Umum">
            <i class="fa-solid fa-print"></i> Cetak Jurnal
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     WADAH LEMBAR KERJA AKUNTANSI (PROFESSIONAL ACCOUNTING SHEET)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="jrn-sheet-container" id="jrnSheet">
    <?= render_jurnal_sheet($bulan, $tahun_f, $bulan_f, $data_jurnal, $total_debit, $total_kredit, $is_balanced, $total_trx) ?>
</div>

<?php
/* ═══════════════════════════════════════════════════════════════════════════
   JAVASCRIPT HALAMAN JURNAL UMUM: REAL-TIME AJAX FILTERING
   ═══════════════════════════════════════════════════════════════════════════ */
$extra_scripts = '
<script>
(function() {
    var inputBulan    = document.getElementById("bulan");
    var btnApply      = document.getElementById("btnApplyMonthJrn");
    var metricsWrap   = document.getElementById("jrnMetrics");
    var sheetWrap     = document.getElementById("jrnSheet");
    var quickNavWrap  = document.getElementById("jrnQuickNav");
    var exportHero    = document.getElementById("btnExportJurnal");
    var exportToolbar = document.getElementById("btnExportJurnalToolbar");
    var abortCtrl     = null;

    function showProgress() {
        var bar = document.getElementById("fintokoAjaxLoader");
        if (bar) {
            bar.classList.add("active");
            bar.style.width = "40%";
            bar.style.opacity = "1";
        }
        if (sheetWrap) {
            sheetWrap.classList.add("ajax-loading-panel");
        }
    }

    function hideProgress() {
        var bar = document.getElementById("fintokoAjaxLoader");
        if (bar) {
            bar.style.width = "100%";
            setTimeout(function() {
                bar.classList.remove("active");
                bar.style.opacity = "0";
                setTimeout(function() { bar.style.width = "0%"; }, 250);
            }, 120);
        }
        if (sheetWrap) {
            sheetWrap.classList.remove("ajax-loading-panel");
        }
    }

    function executeAjaxJurnal(bln, pushHistory) {
        if (!bln) return;
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = new AbortController();

        showProgress();

        var fetchUrl = "jurnal.php?ajax=1&bulan=" + encodeURIComponent(bln);

        fetch(fetchUrl, {
            signal: abortCtrl.signal,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(function(res) {
            if (!res.ok) throw new Error("Network status " + res.status);
            return res.json();
        })
        .then(function(data) {
            if (data.status === "success") {
                if (inputBulan) inputBulan.value = data.bulan;

                if (metricsWrap) {
                    metricsWrap.innerHTML = data.html_metrics;
                    metricsWrap.classList.add("ajax-fade-in");
                    setTimeout(function() { metricsWrap.classList.remove("ajax-fade-in"); }, 300);
                }

                if (sheetWrap) {
                    sheetWrap.innerHTML = data.html_sheet;
                    sheetWrap.classList.add("ajax-fade-in");
                    setTimeout(function() { sheetWrap.classList.remove("ajax-fade-in"); }, 300);
                }

                if (quickNavWrap) {
                    quickNavWrap.innerHTML = data.html_quick_nav;
                }

                if (exportHero) exportHero.href = data.export_url;
                if (exportToolbar) exportToolbar.href = data.export_url;

                if (pushHistory !== false && window.history && window.history.replaceState) {
                    window.history.replaceState({ bulan: data.bulan }, "", "jurnal.php?bulan=" + encodeURIComponent(data.bulan));
                }
            }
        })
        .catch(function(err) {
            if (err.name !== "AbortError") {
                console.error("[Jurnal AJAX Error]", err);
            }
        })
        .finally(function() {
            hideProgress();
        });
    }

    if (inputBulan) {
        inputBulan.addEventListener("change", function() {
            executeAjaxJurnal(inputBulan.value);
        });
    }

    if (btnApply) {
        btnApply.addEventListener("click", function() {
            if (inputBulan) executeAjaxJurnal(inputBulan.value);
        });
    }

    if (quickNavWrap) {
        quickNavWrap.addEventListener("click", function(e) {
            var btn = e.target.closest(".jrn-btn-nav");
            if (!btn) return;
            e.preventDefault();
            var bln = btn.getAttribute("data-bulan") || "";
            if (bln) executeAjaxJurnal(bln);
        });
    }

    window.addEventListener("popstate", function() {
        var params = new URLSearchParams(window.location.search);
        var bln = params.get("bulan") || "";
        if (bln) {
            executeAjaxJurnal(bln, false);
        }
    });
})();
</script>';

include __DIR__ . '/../includes/footer.php';

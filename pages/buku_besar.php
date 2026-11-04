<?php
/**
 * =====================================================================
 * FINTOKO — SISTEM INFORMASI KEUANGAN & SIKLUS AKUNTANSI
 * =====================================================================
 * File    : pages/buku_besar.php (v7.0 — Real-Time AJAX Account & Month Filtering)
 * Halaman : Buku Besar (General Ledger - Double-Balance Running Account)
 * Fitur   : 1. Live Filter Akun & Bulan berbasis AJAX (Zero Page Reload)
 *           2. Endpoint Asinkron JSON dengan partial HTML render
 *           3. Professional Ledger Sheet Container (Lembar Kerja Putih)
 *           4. Kop Surat Perusahaan Formal & Terstruktur
 *           5. 4 Metric Cards Ringkasan Akun (Saldo Awal, Debit, Kredit, Saldo Akhir)
 *           6. Spesifikasi Akun Terpilih (Kode, Nama, Posisi Saldo Normal)
 *           7. Clean Ledger Table Structure (Sub-kolom Saldo Debit & Kredit)
 *           8. Validasi Saldo Awal & Perhitungan Running Balance Otomatis
 *           9. Standar Cetak Kertas A4 Landscape (@media print)
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');
require_admin('../');

/* ═══════════════════════════════════════════════════════════════════════════
   1. FILTER PARAMETERS (BULAN & AKUN)
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
$tgl_awal_bulan   = $tahun_f . '-' . $bulan_f . '-01';

// Ambil daftar kategori unik transaksi dari DB untuk sub-akun
$kat_masuk_rows = db()->query("
    SELECT DISTINCT kategori FROM transaksi 
    WHERE jenis = 'Pemasukan' AND kategori IS NOT NULL AND kategori != '' 
    ORDER BY kategori
")->fetch_all(MYSQLI_ASSOC);

$kat_keluar_rows = db()->query("
    SELECT DISTINCT kategori FROM transaksi 
    WHERE jenis = 'Pengeluaran' AND kategori IS NOT NULL AND kategori != '' 
    ORDER BY kategori
")->fetch_all(MYSQLI_ASSOC);

$akun_pilihan = $_GET['akun'] ?? 'Kas';

/* ═══════════════════════════════════════════════════════════════════════════
   2. SPESIFIKASI AKUN & IDENTIFIKASI ATURAN AKUNTANSI
   ═══════════════════════════════════════════════════════════════════════════ */
$nama_akun     = 'Kas';
$kode_akun     = '101';
$klasifikasi   = 'Aset Lancar (Current Assets)';
$saldo_normal  = 'Debit';
$tipe_normal   = 'debit';
$is_sub_akun   = false;
$sub_kategori  = '';

if ($akun_pilihan === 'Kas') {
    $nama_akun    = 'Kas Toko (Utama)';
    $kode_akun    = '101';
    $klasifikasi  = 'Aset Lancar (Current Assets)';
    $saldo_normal = 'Debit';
    $tipe_normal  = 'debit';
} elseif ($akun_pilihan === 'Pendapatan') {
    $nama_akun    = 'Pendapatan Usaha (Seluruhnya)';
    $kode_akun    = '401';
    $klasifikasi  = 'Pendapatan (Revenue)';
    $saldo_normal = 'Kredit';
    $tipe_normal  = 'kredit';
} elseif ($akun_pilihan === 'Beban') {
    $nama_akun    = 'Beban Operasional (Seluruhnya)';
    $kode_akun    = '501';
    $klasifikasi  = 'Beban (Operating Expenses)';
    $saldo_normal = 'Debit';
    $tipe_normal  = 'debit';
} elseif (strpos($akun_pilihan, 'Pendapatan: ') === 0) {
    $sub_kategori = substr($akun_pilihan, 12);
    $nama_akun    = 'Pendapatan — ' . $sub_kategori;
    $kode_akun    = '401.' . substr(md5($sub_kategori), 0, 3);
    $klasifikasi  = 'Pendapatan Sub-Akun';
    $saldo_normal = 'Kredit';
    $tipe_normal  = 'kredit';
    $is_sub_akun  = true;
} elseif (strpos($akun_pilihan, 'Beban: ') === 0) {
    $sub_kategori = substr($akun_pilihan, 7);
    $nama_akun    = 'Beban — ' . $sub_kategori;
    $kode_akun    = '501.' . substr(md5($sub_kategori), 0, 3);
    $klasifikasi  = 'Beban Sub-Akun';
    $saldo_normal = 'Debit';
    $tipe_normal  = 'debit';
    $is_sub_akun  = true;
}

/* ═══════════════════════════════════════════════════════════════════════════
   3. HITUNG SALDO AWAL (SEBELUM BULAN BERJALAN)
   ═══════════════════════════════════════════════════════════════════════════ */
$saldo_awal = 0.0;

if ($akun_pilihan === 'Kas') {
    $stmt_sa = db()->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN jenis = 'Pemasukan' THEN nominal ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN jenis = 'Pengeluaran' THEN nominal ELSE 0 END), 0) AS saldo
        FROM transaksi WHERE tanggal < ?
    ");
    $stmt_sa->bind_param('s', $tgl_awal_bulan);
    $stmt_sa->execute();
    $saldo_awal = (float) $stmt_sa->get_result()->fetch_assoc()['saldo'];
    $stmt_sa->close();
} elseif ($akun_pilihan === 'Pendapatan') {
    $stmt_sa = db()->prepare("
        SELECT COALESCE(SUM(nominal), 0) AS saldo
        FROM transaksi 
        WHERE jenis = 'Pemasukan' AND YEAR(tanggal) = ? AND tanggal < ?
    ");
    $stmt_sa->bind_param('is', $tahun_f, $tgl_awal_bulan);
    $stmt_sa->execute();
    $saldo_awal = (float) $stmt_sa->get_result()->fetch_assoc()['saldo'];
    $stmt_sa->close();
} elseif ($akun_pilihan === 'Beban') {
    $stmt_sa = db()->prepare("
        SELECT COALESCE(SUM(nominal), 0) AS saldo
        FROM transaksi 
        WHERE jenis = 'Pengeluaran' AND YEAR(tanggal) = ? AND tanggal < ?
    ");
    $stmt_sa->bind_param('is', $tahun_f, $tgl_awal_bulan);
    $stmt_sa->execute();
    $saldo_awal = (float) $stmt_sa->get_result()->fetch_assoc()['saldo'];
    $stmt_sa->close();
} elseif ($is_sub_akun && strpos($akun_pilihan, 'Pendapatan: ') === 0) {
    $stmt_sa = db()->prepare("
        SELECT COALESCE(SUM(nominal), 0) AS saldo
        FROM transaksi 
        WHERE jenis = 'Pemasukan' AND kategori = ? AND YEAR(tanggal) = ? AND tanggal < ?
    ");
    $stmt_sa->bind_param('sis', $sub_kategori, $tahun_f, $tgl_awal_bulan);
    $stmt_sa->execute();
    $saldo_awal = (float) $stmt_sa->get_result()->fetch_assoc()['saldo'];
    $stmt_sa->close();
} elseif ($is_sub_akun && strpos($akun_pilihan, 'Beban: ') === 0) {
    $stmt_sa = db()->prepare("
        SELECT COALESCE(SUM(nominal), 0) AS saldo
        FROM transaksi 
        WHERE jenis = 'Pengeluaran' AND kategori = ? AND YEAR(tanggal) = ? AND tanggal < ?
    ");
    $stmt_sa->bind_param('sis', $sub_kategori, $tahun_f, $tgl_awal_bulan);
    $stmt_sa->execute();
    $saldo_awal = (float) $stmt_sa->get_result()->fetch_assoc()['saldo'];
    $stmt_sa->close();
}

/* ═══════════════════════════════════════════════════════════════════════════
   4. QUERY TRANSAKSI MUTASI BULAN BERJALAN
   ═══════════════════════════════════════════════════════════════════════════ */
$data_mutasi = [];

if ($akun_pilihan === 'Kas') {
    $stmt_tx = db()->prepare("
        SELECT * FROM transaksi
        WHERE YEAR(tanggal) = ? AND MONTH(tanggal) = ?
        ORDER BY tanggal ASC, id ASC
    ");
    $stmt_tx->bind_param('ii', $tahun_f, $bulan_f);
    $stmt_tx->execute();
    $data_mutasi = $stmt_tx->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tx->close();
} elseif ($akun_pilihan === 'Pendapatan') {
    $stmt_tx = db()->prepare("
        SELECT * FROM transaksi
        WHERE jenis = 'Pemasukan' AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
        ORDER BY tanggal ASC, id ASC
    ");
    $stmt_tx->bind_param('ii', $tahun_f, $bulan_f);
    $stmt_tx->execute();
    $data_mutasi = $stmt_tx->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tx->close();
} elseif ($akun_pilihan === 'Beban') {
    $stmt_tx = db()->prepare("
        SELECT * FROM transaksi
        WHERE jenis = 'Pengeluaran' AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
        ORDER BY tanggal ASC, id ASC
    ");
    $stmt_tx->bind_param('ii', $tahun_f, $bulan_f);
    $stmt_tx->execute();
    $data_mutasi = $stmt_tx->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tx->close();
} elseif ($is_sub_akun && strpos($akun_pilihan, 'Pendapatan: ') === 0) {
    $stmt_tx = db()->prepare("
        SELECT * FROM transaksi
        WHERE jenis = 'Pemasukan' AND kategori = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
        ORDER BY tanggal ASC, id ASC
    ");
    $stmt_tx->bind_param('sii', $sub_kategori, $tahun_f, $bulan_f);
    $stmt_tx->execute();
    $data_mutasi = $stmt_tx->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tx->close();
} elseif ($is_sub_akun && strpos($akun_pilihan, 'Beban: ') === 0) {
    $stmt_tx = db()->prepare("
        SELECT * FROM transaksi
        WHERE jenis = 'Pengeluaran' AND kategori = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
        ORDER BY tanggal ASC, id ASC
    ");
    $stmt_tx->bind_param('sii', $sub_kategori, $tahun_f, $bulan_f);
    $stmt_tx->execute();
    $data_mutasi = $stmt_tx->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tx->close();
}

/* ═══════════════════════════════════════════════════════════════════════════
   5. PERHITUNGAN RUNNING BALANCE (SALDO BERJALAN)
   ═══════════════════════════════════════════════════════════════════════════ */
$buku_besar          = [];
$saldo_berjalan      = $saldo_awal;
$total_mutasi_debit  = 0.0;
$total_mutasi_kredit = 0.0;

foreach ($data_mutasi as $t) {
    $nominal = (float) $t['nominal'];
    $debit   = 0.0;
    $kredit  = 0.0;

    if ($akun_pilihan === 'Kas') {
        if ($t['jenis'] === 'Pemasukan') {
            $debit = $nominal;
        } else {
            $kredit = $nominal;
        }
        $saldo_berjalan += ($debit - $kredit);
    } elseif ($akun_pilihan === 'Pendapatan' || ($is_sub_akun && strpos($akun_pilihan, 'Pendapatan: ') === 0)) {
        $kredit = $nominal;
        $saldo_berjalan += ($kredit - $debit);
    } elseif ($akun_pilihan === 'Beban' || ($is_sub_akun && strpos($akun_pilihan, 'Beban: ') === 0)) {
        $debit = $nominal;
        $saldo_berjalan += ($debit - $kredit);
    }

    $total_mutasi_debit  += $debit;
    $total_mutasi_kredit += $kredit;

    $saldo_tampil_debit  = null;
    $saldo_tampil_kredit = null;

    if ($tipe_normal === 'debit') {
        if ($saldo_berjalan >= 0) {
            $saldo_tampil_debit = $saldo_berjalan;
        } else {
            $saldo_tampil_kredit = abs($saldo_berjalan);
        }
    } else {
        if ($saldo_berjalan >= 0) {
            $saldo_tampil_kredit = $saldo_berjalan;
        } else {
            $saldo_tampil_debit = abs($saldo_berjalan);
        }
    }

    $buku_besar[] = [
        'id'           => $t['id'],
        'tanggal'      => $t['tanggal'],
        'keterangan'   => $t['keterangan'] ?: ($t['jenis'] . ' ' . $t['kategori']),
        'ref'          => 'TRX-' . str_pad($t['id'], 4, '0', STR_PAD_LEFT),
        'debit'        => $debit,
        'kredit'       => $kredit,
        'saldo_debit'  => $saldo_tampil_debit,
        'saldo_kredit' => $saldo_tampil_kredit,
    ];
}

$saldo_akhir = $saldo_berjalan;

/* ═══════════════════════════════════════════════════════════════════════════
   6. EKSPOR EXCEL / CSV UTF-8 (BUKU BESAR LENGKAP)
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'csv'], true)) {
    $filename = 'Buku_Besar_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nama_akun) . '_' . $bulan . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, [NAMA_INSTANSI], ';');
    fputcsv($out, ['BUKU BESAR (GENERAL LEDGER)'], ';');
    fputcsv($out, ['Akun: ' . $nama_akun . ' (Kode: ' . $kode_akun . ')'], ';');
    fputcsv($out, ['Periode: ' . bulan_tahun_id($bulan)], ';');
    fputcsv($out, ['Saldo Normal: ' . $saldo_normal], ';');
    fputcsv($out, [], ';');

    fputcsv($out, ['Tanggal', 'Keterangan', 'Ref', 'Debit (Rp)', 'Kredit (Rp)', 'Saldo Debit (Rp)', 'Saldo Kredit (Rp)'], ';');

    $sa_debit  = ($tipe_normal === 'debit' && $saldo_awal >= 0) || ($tipe_normal === 'kredit' && $saldo_awal < 0) ? abs($saldo_awal) : 0;
    $sa_kredit = ($tipe_normal === 'kredit' && $saldo_awal >= 0) || ($tipe_normal === 'debit' && $saldo_awal < 0) ? abs($saldo_awal) : 0;
    fputcsv($out, [
        tanggal_id($tgl_awal_bulan),
        'Saldo Awal Periode',
        'SALDO-AWAL',
        '0,00',
        '0,00',
        number_format($sa_debit, 2, ',', '.'),
        number_format($sa_kredit, 2, ',', '.')
    ], ';');

    foreach ($buku_besar as $row) {
        fputcsv($out, [
            tanggal_id($row['tanggal']),
            $row['keterangan'],
            $row['ref'],
            number_format($row['debit'], 2, ',', '.'),
            number_format($row['kredit'], 2, ',', '.'),
            $row['saldo_debit'] !== null ? number_format($row['saldo_debit'], 2, ',', '.') : '0,00',
            $row['saldo_kredit'] !== null ? number_format($row['saldo_kredit'], 2, ',', '.') : '0,00',
        ], ';');
    }

    fputcsv($out, [], ';');
    fputcsv($out, [
        'TOTAL MUTASI',
        '',
        '',
        number_format($total_mutasi_debit, 2, ',', '.'),
        number_format($total_mutasi_kredit, 2, ',', '.'),
        '',
        ''
    ], ';');
    fputcsv($out, [
        'SALDO AKHIR PERIODE (' . $saldo_normal . ')',
        '',
        '',
        '',
        '',
        number_format($saldo_akhir, 2, ',', '.'),
        ''
    ], ';');

    fclose($out);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   HELPER RENDER PARSIAL (Digunakan SSR dan Endpoint AJAX)
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Render 4 kartu metrik buku besar
 */
function render_buku_besar_metrics(float $saldo_awal, float $total_mutasi_debit, float $total_mutasi_kredit, float $saldo_akhir, string $tipe_normal, string $bulan): string
{
    ob_start();
    ?>
    <!-- Card 1: Saldo Awal -->
    <div class="bkb-metric-card bkb-metric-card--sa">
        <div class="bkb-metric-icon">
            <i class="fa-solid fa-hourglass-start"></i>
        </div>
        <div class="bkb-metric-body">
            <div class="bkb-metric-label">Saldo Awal Periode</div>
            <div class="bkb-metric-value" style="color:var(--sl-800);"><?= rupiah($saldo_awal) ?></div>
            <div class="bkb-metric-note">
                <i class="fa-regular fa-calendar-check"></i> Posisi per 1 <?= bulan_tahun_id($bulan) ?>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Mutasi Debit -->
    <div class="bkb-metric-card bkb-metric-card--debit">
        <div class="bkb-metric-icon">
            <i class="fa-solid fa-arrow-down-left"></i>
        </div>
        <div class="bkb-metric-body">
            <div class="bkb-metric-label">Total Mutasi Debit</div>
            <div class="bkb-metric-value" style="color:#047857;"><?= rupiah($total_mutasi_debit) ?></div>
            <div class="bkb-metric-note">
                <span class="bkb-badge-pill bkb-badge-pill--green">
                    <i class="fa-solid fa-plus"></i> Arus Masuk Sisi Debit
                </span>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Mutasi Kredit -->
    <div class="bkb-metric-card bkb-metric-card--kredit">
        <div class="bkb-metric-icon">
            <i class="fa-solid fa-arrow-up-right"></i>
        </div>
        <div class="bkb-metric-body">
            <div class="bkb-metric-label">Total Mutasi Kredit</div>
            <div class="bkb-metric-value" style="color:#0369a1;"><?= rupiah($total_mutasi_kredit) ?></div>
            <div class="bkb-metric-note">
                <span class="bkb-badge-pill bkb-badge-pill--blue">
                    <i class="fa-solid fa-arrow-right"></i> Arus Masuk Sisi Kredit
                </span>
            </div>
        </div>
    </div>

    <!-- Card 4: Saldo Akhir Berjalan -->
    <div class="bkb-metric-card bkb-metric-card--akhir">
        <div class="bkb-metric-icon">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div class="bkb-metric-body">
            <div class="bkb-metric-label">Saldo Akhir Periode</div>
            <div class="bkb-metric-value" style="color:#0f172a; font-weight:800;"><?= rupiah($saldo_akhir) ?></div>
            <div class="bkb-metric-note">
                <span class="bkb-badge-pill <?= ($saldo_akhir >= 0) ? 'bkb-badge-pill--green' : 'bkb-badge-pill--danger' ?>">
                    <i class="fa-solid fa-circle-check"></i> Posisi Akhir Akun
                </span>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render lembar kerja buku besar (#bkbSheet)
 */
function render_buku_besar_sheet(string $bulan, string $tahun_f, string $bulan_f, string $akun_pilihan, string $nama_akun, string $kode_akun, string $klasifikasi, string $saldo_normal, string $tipe_normal, float $saldo_awal, array $buku_besar, float $total_mutasi_debit, float $total_mutasi_kredit, float $saldo_akhir, string $tgl_awal_bulan): string
{
    ob_start();
    ?>
    <div class="bkb-letterhead">
        <div class="bkb-letterhead__brand">
            <div class="bkb-brand-logo">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div class="bkb-brand-info">
                <h1 class="bkb-company-name"><?= e(NAMA_INSTANSI) ?></h1>
                <div class="bkb-company-tagline">Sistem Informasi Akuntansi &amp; Siklus Pembukuan</div>
                <div class="bkb-company-address">
                    <i class="fa-solid fa-location-dot" style="color:var(--az-500); margin-right:4px;"></i>
                    <?= e(ALAMAT_INSTANSI) ?>
                </div>
            </div>
        </div>

        <div class="bkb-doc-meta">
            <div class="bkb-doc-ref">No. Register Buku Besar</div>
            <div class="bkb-doc-ref-code">GL/<?= $kode_akun ?>/<?= $tahun_f ?>-<?= $bulan_f ?></div>
            <div class="bkb-doc-date">
                <i class="fa-regular fa-clock" style="margin-right:4px;"></i>
                Dicetak: <?= tanggal_panjang_id() ?>
            </div>
        </div>
    </div>

    <div class="bkb-kop-divider">
        <div class="bkb-kop-divider__thick"></div>
        <div class="bkb-kop-divider__thin"></div>
    </div>

    <div class="bkb-title-banner">
        <h2 class="bkb-title-main">BUKU BESAR</h2>
        <div class="bkb-title-en">GENERAL LEDGER ACCOUNT</div>
        <div style="margin-top:6px;">
            <span class="bkb-title-period">
                <i class="fa-regular fa-calendar-check" style="color:var(--az-600); margin-right:4px;"></i>
                Periode: <?= bulan_tahun_id($bulan) ?>
            </span>
        </div>
    </div>

    <div class="bkb-account-spec">
        <div class="bkb-spec-col">
            <span class="bkb-spec-label">Nama Akun:</span>
            <span class="bkb-spec-val" style="color:var(--sl-900); font-weight:800; font-size:15px;">
                <?= e($nama_akun) ?>
            </span>
        </div>
        <div class="bkb-spec-col">
            <span class="bkb-spec-label">Kode Akun:</span>
            <span class="bkb-spec-val">
                <span class="bkb-code-badge"><?= e($kode_akun) ?></span>
            </span>
        </div>
        <div class="bkb-spec-col">
            <span class="bkb-spec-label">Klasifikasi:</span>
            <span class="bkb-spec-val" style="color:var(--sl-700); font-size:12.5px;">
                <?= e($klasifikasi) ?>
            </span>
        </div>
        <div class="bkb-spec-col">
            <span class="bkb-spec-label">Saldo Normal:</span>
            <span class="bkb-spec-val">
                <span class="bkb-badge-pill <?= $tipe_normal === 'debit' ? 'bkb-badge-pill--green' : 'bkb-badge-pill--blue' ?>">
                    <i class="fa-solid fa-scale-balanced"></i> <?= e($saldo_normal) ?>
                </span>
            </span>
        </div>
    </div>

    <div class="bkb-table-container">
        <table class="bkb-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-date" style="vertical-align:middle;">Tanggal</th>
                    <th rowspan="2" class="col-memo" style="vertical-align:middle;">Keterangan Transaksi</th>
                    <th rowspan="2" class="col-ref" style="vertical-align:middle;">Ref</th>
                    <th colspan="2" class="col-group-header">Mutasi Periode</th>
                    <th colspan="2" class="col-group-header">Saldo Berjalan</th>
                </tr>
                <tr>
                    <th class="col-debit">Debit (Rp)</th>
                    <th class="col-kredit">Kredit (Rp)</th>
                    <th class="col-saldo">Debit (Rp)</th>
                    <th class="col-saldo">Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bkb-row-sa">
                    <td style="text-align:center; font-variant-numeric:tabular-nums; font-weight:600;">
                        <?= tanggal_id($tgl_awal_bulan) ?>
                    </td>
                    <td>
                        <div class="bkb-sa-title">
                            <i class="fa-solid fa-hourglass-start" style="color:var(--az-500); margin-right:4px;"></i>
                            <strong>Saldo Awal Periode</strong>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <span class="bkb-ref-tag">SALDO-AWAL</span>
                    </td>
                    <td><span class="bkb-dash">&mdash;</span></td>
                    <td><span class="bkb-dash">&mdash;</span></td>
                    <?php
                        $sa_deb = ($tipe_normal === 'debit' && $saldo_awal >= 0) || ($tipe_normal === 'kredit' && $saldo_awal < 0) ? abs($saldo_awal) : null;
                        $sa_kre = ($tipe_normal === 'kredit' && $saldo_awal >= 0) || ($tipe_normal === 'debit' && $saldo_awal < 0) ? abs($saldo_awal) : null;
                    ?>
                    <td class="bkb-amount bkb-amount--saldo">
                        <?= $sa_deb !== null ? rupiah($sa_deb) : '<span class="bkb-dash">&mdash;</span>' ?>
                    </td>
                    <td class="bkb-amount bkb-amount--saldo">
                        <?= $sa_kre !== null ? rupiah($sa_kre) : '<span class="bkb-dash">&mdash;</span>' ?>
                    </td>
                </tr>

                <?php if (count($buku_besar) > 0): ?>
                    <?php foreach ($buku_besar as $r): ?>
                        <tr>
                            <td class="col-date">
                                <span class="bkb-date-pill"><?= tanggal_id($r['tanggal']) ?></span>
                            </td>
                            <td class="col-memo">
                                <span class="bkb-memo-text"><?= e($r['keterangan']) ?></span>
                            </td>
                            <td class="col-ref">
                                <span class="bkb-ref-tag"><?= e($r['ref']) ?></span>
                            </td>
                            <td class="bkb-amount bkb-amount--debit">
                                <?= $r['debit'] > 0 ? rupiah($r['debit']) : '<span class="bkb-dash">&mdash;</span>' ?>
                            </td>
                            <td class="bkb-amount bkb-amount--kredit">
                                <?= $r['kredit'] > 0 ? rupiah($r['kredit']) : '<span class="bkb-dash">&mdash;</span>' ?>
                            </td>
                            <td class="bkb-amount bkb-amount--saldo">
                                <?= $r['saldo_debit'] !== null ? rupiah($r['saldo_debit']) : '<span class="bkb-dash">&mdash;</span>' ?>
                            </td>
                            <td class="bkb-amount bkb-amount--saldo">
                                <?= $r['saldo_kredit'] !== null ? rupiah($r['saldo_kredit']) : '<span class="bkb-dash">&mdash;</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="bkb-empty-state">
                            <i class="fa-regular fa-folder-open"></i>
                            <p>Tidak Ada Mutasi Transaksi pada Periode Ini</p>
                            <span>Belum ada pergerakan debit maupun kredit pada akun ini untuk bulan <?= bulan_tahun_id($bulan) ?>. Saldo berjalan tetap sama dengan saldo awal.</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="bkb-foot-total">
                    <td colspan="3" class="bkb-foot-label">
                        <i class="fa-solid fa-calculator" style="margin-right:6px;"></i> TOTAL MUTASI PERIODE
                    </td>
                    <td class="bkb-foot-val bkb-foot-val--debit">
                        <?= rupiah($total_mutasi_debit) ?>
                    </td>
                    <td class="bkb-foot-val bkb-foot-val--kredit">
                        <?= rupiah($total_mutasi_kredit) ?>
                    </td>
                    <td colspan="2" class="bkb-foot-val bkb-foot-val--saldo">
                        Posisi Akhir: <strong><?= rupiah($saldo_akhir) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render navigasi bulan cepat
 */
function render_buku_besar_quick_nav(string $akun_pilihan, string $bulan, string $bulan_ini, string $bulan_sebelumnya, string $bulan_berikutnya): string
{
    ob_start();
    ?>
    <a href="?akun=<?= urlencode($akun_pilihan) ?>&bulan=<?= $bulan_sebelumnya ?>" class="bkb-btn-nav" data-bulan="<?= $bulan_sebelumnya ?>" title="Bulan Sebelumnya">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <a href="?akun=<?= urlencode($akun_pilihan) ?>&bulan=<?= $bulan_ini ?>" class="bkb-btn-nav <?= ($bulan === $bulan_ini) ? 'bkb-btn-nav--active' : '' ?>" data-bulan="<?= $bulan_ini ?>">
        Bulan Ini
    </a>
    <a href="?akun=<?= urlencode($akun_pilihan) ?>&bulan=<?= $bulan_berikutnya ?>" class="bkb-btn-nav" data-bulan="<?= $bulan_berikutnya ?>" title="Bulan Berikutnya">
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
        'akun'           => $akun_pilihan,
        'bulan'          => $bulan,
        'html_metrics'   => render_buku_besar_metrics($saldo_awal, $total_mutasi_debit, $total_mutasi_kredit, $saldo_akhir, $tipe_normal, $bulan),
        'html_sheet'     => render_buku_besar_sheet($bulan, $tahun_f, $bulan_f, $akun_pilihan, $nama_akun, $kode_akun, $klasifikasi, $saldo_normal, $tipe_normal, $saldo_awal, $buku_besar, $total_mutasi_debit, $total_mutasi_kredit, $saldo_akhir, $tgl_awal_bulan),
        'html_quick_nav' => render_buku_besar_quick_nav($akun_pilihan, $bulan, $bulan_ini, $bulan_sebelumnya, $bulan_berikutnya),
        'export_url'     => '?akun=' . urlencode($akun_pilihan) . '&bulan=' . urlencode($bulan) . '&export=excel',
    ]);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   TEMPLATE META & HEADER
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Buku Besar';
$page_eyebrow = NAMA_INSTANSI . ' · Siklus Akuntansi';
$root         = '../';
$active       = 'buku_besar';

include __DIR__ . '/../includes/header.php';
?>

<!-- Stylesheet Terdedikasi Buku Besar v7.0 — Icy Azure Enterprise -->
<link rel="stylesheet" href="../assets/css/buku_besar.css">

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     HERO BANNER BUKU BESAR
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="bkb-hero" id="bkbHero">
    <div class="bkb-hero__left">
        <div class="bkb-eyebrow">
            <i class="fa-solid fa-book-open-reader"></i> SIKLUS AKUNTANSI &bull; GENERAL LEDGER
        </div>
        <h2 class="bkb-hero__title">Buku Besar Akun (General Ledger)</h2>
        <p class="bkb-hero__sub">
            Rincian mutasi saldo berpasangan dan histori saldo berjalan (<em>running balance</em>) akun 
            <strong id="heroNamaAkun"><?= e($nama_akun) ?></strong> periode <strong id="heroBulanAkun"><?= bulan_tahun_id($bulan) ?></strong>.
        </p>
    </div>
    <div class="bkb-hero__actions">
        <a href="?akun=<?= urlencode($akun_pilihan) ?>&bulan=<?= urlencode($bulan) ?>&export=excel" class="bkb-btn bkb-btn--excel" id="btnExportBkb" title="Unduh CSV Buku Besar">
            <i class="fa-solid fa-file-excel"></i> Ekspor CSV
        </a>
        <button type="button" class="bkb-btn bkb-btn--primary" onclick="window.print()" title="Cetak Lembar Buku Besar">
            <i class="fa-solid fa-print"></i> Cetak Lembar
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     4 KARTU METRIK RINGKASAN AKUN (METRIC CARDS)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="bkb-metrics" id="bkbMetrics">
    <?= render_buku_besar_metrics($saldo_awal, $total_mutasi_debit, $total_mutasi_kredit, $saldo_akhir, $tipe_normal, $bulan) ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     SMART TOOLBAR (FILTER AKUN & FILTER BULAN)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="bkb-toolbar">
    <div class="bkb-toolbar__left">
        <form method="GET" class="bkb-filter-form" id="bkbFilterForm" action="" onsubmit="return false;">
            <!-- Dropdown Pilihan Akun -->
            <div class="bkb-input-group">
                <label for="akun"><i class="fa-solid fa-book"></i> Akun:</label>
                <select name="akun" id="akun" class="bkb-select">
                    <optgroup label="Akun Utama">
                        <option value="Kas" <?= $akun_pilihan === 'Kas' ? 'selected' : '' ?>>[101] Kas Toko (Utama)</option>
                        <option value="Pendapatan" <?= $akun_pilihan === 'Pendapatan' ? 'selected' : '' ?>>[401] Seluruh Pendapatan</option>
                        <option value="Beban" <?= $akun_pilihan === 'Beban' ? 'selected' : '' ?>>[501] Seluruh Beban</option>
                    </optgroup>

                    <?php if (count($kat_masuk_rows) > 0): ?>
                        <optgroup label="Sub-Akun Pendapatan">
                            <?php foreach ($kat_masuk_rows as $km): ?>
                                <?php $val = 'Pendapatan: ' . $km['kategori']; ?>
                                <option value="<?= e($val) ?>" <?= $akun_pilihan === $val ? 'selected' : '' ?>>
                                    &nbsp;&nbsp;↳ Pendapatan — <?= e($km['kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>

                    <?php if (count($kat_keluar_rows) > 0): ?>
                        <optgroup label="Sub-Akun Beban">
                            <?php foreach ($kat_keluar_rows as $kk): ?>
                                <?php $val = 'Beban: ' . $kk['kategori']; ?>
                                <option value="<?= e($val) ?>" <?= $akun_pilihan === $val ? 'selected' : '' ?>>
                                    &nbsp;&nbsp;↳ Beban — <?= e($kk['kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Input Pemilih Bulan -->
            <div class="bkb-input-group">
                <label for="bulan"><i class="fa-regular fa-calendar"></i> Periode:</label>
                <input type="month" id="bulan" name="bulan" value="<?= e($bulan) ?>" class="bkb-input-month">
            </div>

            <button type="button" class="bkb-btn bkb-btn--outline" id="btnApplyBkb" title="Tampilkan Data">
                <i class="fa-solid fa-filter"></i> Tampilkan
            </button>
        </form>

        <div class="bkb-quick-nav" id="bkbQuickNav">
            <?= render_buku_besar_quick_nav($akun_pilihan, $bulan, $bulan_ini, $bulan_sebelumnya, $bulan_berikutnya) ?>
        </div>
    </div>

    <div class="bkb-toolbar__right">
        <a href="?akun=<?= urlencode($akun_pilihan) ?>&bulan=<?= urlencode($bulan) ?>&export=excel" class="bkb-btn bkb-btn--excel" id="btnExportBkbToolbar" title="Ekspor data akun ini ke format CSV">
            <i class="fa-solid fa-file-csv"></i> Unduh CSV
        </a>
        <button type="button" class="bkb-btn bkb-btn--primary" onclick="window.print()" title="Cetak lembar buku besar ini">
            <i class="fa-solid fa-print"></i> Cetak Lembar
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     WADAH LEMBAR KERJA AKUNTANSI (PROFESSIONAL LEDGER SHEET)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="bkb-sheet-container" id="bkbSheet">
    <?= render_buku_besar_sheet($bulan, $tahun_f, $bulan_f, $akun_pilihan, $nama_akun, $kode_akun, $klasifikasi, $saldo_normal, $tipe_normal, $saldo_awal, $buku_besar, $total_mutasi_debit, $total_mutasi_kredit, $saldo_akhir, $tgl_awal_bulan) ?>
</div>

<?php
/* ═══════════════════════════════════════════════════════════════════════════
   JAVASCRIPT HALAMAN BUKU BESAR: REAL-TIME AJAX FILTERING
   ═══════════════════════════════════════════════════════════════════════════ */
$extra_scripts = '
<script>
(function() {
    var selAkun       = document.getElementById("akun");
    var inputBulan    = document.getElementById("bulan");
    var btnApply      = document.getElementById("btnApplyBkb");
    var metricsWrap   = document.getElementById("bkbMetrics");
    var sheetWrap     = document.getElementById("bkbSheet");
    var quickNavWrap  = document.getElementById("bkbQuickNav");
    var exportHero    = document.getElementById("btnExportBkb");
    var exportToolbar = document.getElementById("btnExportBkbToolbar");
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

    function executeAjaxBkb(akun, bln, pushHistory) {
        if (!akun || !bln) return;
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = new AbortController();

        showProgress();

        var params = new URLSearchParams();
        params.append("ajax", "1");
        params.append("akun", akun);
        params.append("bulan", bln);

        var fetchUrl = "buku_besar.php?" + params.toString();

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
                if (selAkun) selAkun.value = data.akun;
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
                    var newUrl = "buku_besar.php?akun=" + encodeURIComponent(data.akun) + "&bulan=" + encodeURIComponent(data.bulan);
                    window.history.replaceState({ akun: data.akun, bulan: data.bulan }, "", newUrl);
                }
            }
        })
        .catch(function(err) {
            if (err.name !== "AbortError") {
                console.error("[Buku Besar AJAX Error]", err);
            }
        })
        .finally(function() {
            hideProgress();
        });
    }

    if (selAkun) {
        selAkun.addEventListener("change", function() {
            var bln = inputBulan ? inputBulan.value : "";
            executeAjaxBkb(selAkun.value, bln);
        });
    }

    if (inputBulan) {
        inputBulan.addEventListener("change", function() {
            var akn = selAkun ? selAkun.value : "Kas";
            executeAjaxBkb(akn, inputBulan.value);
        });
    }

    if (btnApply) {
        btnApply.addEventListener("click", function() {
            var akn = selAkun ? selAkun.value : "Kas";
            var bln = inputBulan ? inputBulan.value : "";
            executeAjaxBkb(akn, bln);
        });
    }

    if (quickNavWrap) {
        quickNavWrap.addEventListener("click", function(e) {
            var btn = e.target.closest(".bkb-btn-nav");
            if (!btn) return;
            e.preventDefault();
            var bln = btn.getAttribute("data-bulan") || "";
            var akn = selAkun ? selAkun.value : "Kas";
            if (bln) executeAjaxBkb(akn, bln);
        });
    }

    window.addEventListener("popstate", function() {
        var params = new URLSearchParams(window.location.search);
        var akn = params.get("akun") || "Kas";
        var bln = params.get("bulan") || "";
        if (akn && bln) {
            executeAjaxBkb(akn, bln, false);
        }
    });
})();
</script>';

include __DIR__ . '/../includes/footer.php';

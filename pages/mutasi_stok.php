<?php
/**
 * =====================================================================
 * FINTOKO — Sistem Informasi Keuangan & Inventaris
 * =====================================================================
 * File    : pages/mutasi_stok.php  (v7.0 — Real-Time AJAX Filtering & Search)
 * Halaman : Mutasi Stok — Pergerakan Barang Masuk & Keluar
 * Fitur   : 1. Live Search & Filter Jenis/Tanggal berbasis AJAX (Zero Page Reload)
 *           2. Endpoint Asinkron JSON dengan partial HTML render
 *           3. Debounce 250ms & AbortController (Bebas Race Condition)
 *           4. Sinkronisasi History URL Browser (history.replaceState)
 *           5. Hero Banner + Tombol Catat Mutasi & Export CSV
 *           6. 3 Kartu Metrik Glassmorphism (Total Masuk, Keluar, Nilai)
 *           7. Modal Catat Mutasi (Responsive, Anti-Cutoff Footer)
 *           8. Integrasi Otomatis ke Kas/Keuangan
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');

/* ═══════════════════════════════════════════════════════════════════════════
   EKSPOR EXCEL (CSV UTF-8) — Riwayat Mutasi
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_admin('../');

    $rows_exp = db()->query("
        SELECT r.tanggal, p.kode_produk, p.nama_produk, r.jenis, r.jumlah, p.satuan,
               r.harga_satuan, r.total_biaya, r.keterangan
        FROM riwayat_stok r
        JOIN produk p ON r.produk_id = p.id
        ORDER BY r.tanggal DESC, r.id DESC
    ")->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mutasi_stok_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8
    fputcsv($out, ['Tanggal', 'Kode', 'Nama Produk', 'Jenis', 'Jumlah', 'Satuan', 'Harga/Unit', 'Total Biaya', 'Keterangan'], ';');

    foreach ($rows_exp as $r) {
        fputcsv($out, [
            $r['tanggal'],
            $r['kode_produk'],
            $r['nama_produk'],
            $r['jenis'],
            $r['jumlah'],
            $r['satuan'],
            number_format((float)$r['harga_satuan'], 0, ',', '.'),
            number_format((float)$r['total_biaya'], 0, ',', '.'),
            $r['keterangan'],
        ], ';');
    }
    fclose($out);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   POST: CATAT MUTASI STOK BARU
   ═══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin('../');

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        redirect('mutasi_stok.php?error=csrf');
    }

    $produk_id    = (int)   ($_POST['produk_id']    ?? 0);
    $jenis        = trim    ($_POST['jenis']        ?? '');
    $jumlah       = (int)   ($_POST['jumlah']       ?? 0);
    $harga_satuan = (float) ($_POST['harga_satuan'] ?? 0);
    $keterangan   = mb_substr(trim($_POST['keterangan'] ?? ''), 0, 255);
    $tanggal      = trim    ($_POST['tanggal']      ?? date('Y-m-d'));

    // Validasi String & Tipe Data Ketat di Sisi Server
    $errors = [];

    $dt = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
        $errors[] = 'Format tanggal mutasi tidak valid.';
    }

    if ($produk_id <= 0) {
        $errors[] = 'Silakan pilih produk inventori yang valid.';
    } else {
        $stmt_prd = db()->prepare("SELECT nama_produk, stok, satuan FROM produk WHERE id = ? LIMIT 1");
        $stmt_prd->bind_param('i', $produk_id);
        $stmt_prd->execute();
        $prd = $stmt_prd->get_result()->fetch_assoc();
        $stmt_prd->close();

        if (!$prd) {
            $errors[] = 'Produk yang dipilih tidak ditemukan dalam basis data.';
        } elseif ($jenis === 'Keluar' && $jumlah > (int)$prd['stok']) {
            $errors[] = "Stok tidak mencukupi! Stok saat ini untuk <strong>" . e($prd['nama_produk']) . "</strong> hanya <strong>" . (int)$prd['stok'] . " " . e($prd['satuan']) . "</strong> (diminta: {$jumlah}).";
        }
    }

    if (!in_array($jenis, ['Masuk', 'Keluar'], true)) {
        $errors[] = 'Jenis mutasi harus Masuk atau Keluar.';
    }

    if ($jumlah <= 0) {
        $errors[] = 'Jumlah mutasi unit harus minimal 1.';
    }

    if ($harga_satuan < 0) {
        $errors[] = 'Harga satuan tidak boleh bernilai negatif.';
    }

    if (!empty($errors)) {
        set_flash('error', implode('<br>', $errors));
        redirect('mutasi_stok.php?error=validation');
    }

    if (proses_mutasi_stok($produk_id, $jenis, $jumlah, $harga_satuan, $keterangan, $tanggal)) {
        set_flash('success', 'Mutasi stok berhasil dicatat dan kas telah diperbarui secara otomatis.');
        redirect('mutasi_stok.php?success=1');
    } else {
        set_flash('error', 'Gagal memproses mutasi stok. Terjadi kesalahan pada transaksi basis data.');
        redirect('mutasi_stok.php?error=1');
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   HELPER RENDER PARSIAL (Digunakan SSR dan Endpoint AJAX)
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Render baris-baris <tbody> tabel riwayat mutasi
 */
function render_mutasi_rows(array $riwayat, string $q_cari = '', string $f_jenis = '', string $f_dari = '', string $f_sampai = ''): string
{
    ob_start();
    if ($riwayat):
        $no = 1;
        foreach ($riwayat as $r):
        ?>
        <tr>
            <!-- # -->
            <td class="mts-table__num"><?= $no++ ?></td>

            <!-- Tanggal -->
            <td>
                <span class="mts-tanggal"><?= tanggal_id($r['tanggal']) ?></span>
            </td>

            <!-- Produk -->
            <td>
                <div class="mts-produk">
                    <span class="mts-produk__nama"><?= e($r['nama_produk']) ?></span>
                    <span class="mts-produk__kode"><?= e($r['kode_produk']) ?></span>
                </div>
            </td>

            <!-- Jenis Mutasi -->
            <td>
                <?php if ($r['jenis'] === 'Masuk'): ?>
                    <span class="mts-jenis mts-jenis--masuk">
                        <i class="fa-solid fa-arrow-down"></i> Masuk (Restok)
                    </span>
                <?php else: ?>
                    <span class="mts-jenis mts-jenis--keluar">
                        <i class="fa-solid fa-arrow-up"></i> Keluar (Terjual)
                    </span>
                <?php endif; ?>
            </td>

            <!-- Jumlah -->
            <td>
                <span class="mts-jumlah">
                    <?= (int)$r['jumlah'] ?> <?= e($r['satuan']) ?>
                </span>
            </td>

            <!-- Harga/Unit -->
            <td class="mts-table__col-right">
                <span class="mts-harga"><?= rupiah($r['harga_satuan']) ?></span>
            </td>

            <!-- Total Biaya -->
            <td class="mts-table__col-right">
                <?php if ($r['jenis'] === 'Masuk'): ?>
                    <span class="mts-total mts-total--keluar">
                        − <?= rupiah($r['total_biaya']) ?>
                    </span>
                <?php else: ?>
                    <span class="mts-total mts-total--masuk">
                        + <?= rupiah($r['total_biaya']) ?>
                    </span>
                <?php endif; ?>
            </td>

            <!-- Keterangan -->
            <td class="mts-keterangan">
                <?= e($r['keterangan'] ?: '—') ?>
            </td>
        </tr>
        <?php endforeach;
    else: ?>
        <tr>
            <td colspan="8" class="mts-empty">
                <div class="mts-empty__inner">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>
                        <?= ($q_cari || $f_jenis || $f_dari || $f_sampai)
                            ? 'Tidak ada riwayat mutasi yang cocok dengan filter. <a href="mutasi_stok.php" class="mts-empty-reset" onclick="window.resetMutasiFilter(event)">Hapus filter &rarr;</a>'
                            : 'Belum ada riwayat mutasi stok. Klik <strong>Catat Mutasi</strong> untuk memulai.'
                        ?>
                    </p>
                </div>
            </td>
        </tr>
    <?php endif;
    return ob_get_clean();
}

/**
 * Render info total mutasi pada header toolbar
 */
function render_mutasi_total_info(int $count): string
{
    return '<i class="fa-solid fa-list-check"></i> <span>Total: <strong>' . number_format($count, 0, ',', '.') . '</strong> data mutasi</span>';
}

/**
 * Render badge jumlah mutasi pada header panel tabel
 */
function render_mutasi_panel_count(int $count, bool $is_filtered): string
{
    $html = '<span class="mts-panel__count">' . number_format($count, 0, ',', '.') . ' mutasi</span>';
    if ($is_filtered) {
        $html .= ' <span class="mts-panel__count mts-panel__count--warn"><i class="fa-solid fa-filter"></i> Terfilter</span>';
    }
    return $html;
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTER & PENCARIAN
   ═══════════════════════════════════════════════════════════════════════════ */
$q_cari   = trim($_GET['q']     ?? '');
$f_jenis  = trim($_GET['jenis'] ?? '');
$f_dari   = trim($_GET['dari']  ?? '');
$f_sampai = trim($_GET['sampai'] ?? '');

// Build query with filters
$sql = "
    SELECT r.*, p.nama_produk, p.kode_produk, p.satuan
    FROM riwayat_stok r
    JOIN produk p ON r.produk_id = p.id
    WHERE 1=1
";
$params = [];
$types  = '';

if ($q_cari !== '') {
    $sql .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ? OR r.keterangan LIKE ?)";
    $like = '%' . $q_cari . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

if ($f_jenis !== '' && in_array($f_jenis, ['Masuk', 'Keluar'], true)) {
    $sql .= " AND r.jenis = ?";
    $params[] = $f_jenis;
    $types   .= 's';
}

if ($f_dari !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_dari)) {
    $sql .= " AND r.tanggal >= ?";
    $params[] = $f_dari;
    $types   .= 's';
}

if ($f_sampai !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_sampai)) {
    $sql .= " AND r.tanggal <= ?";
    $params[] = $f_sampai;
    $types   .= 's';
}

$sql .= " ORDER BY r.tanggal DESC, r.id DESC";

if ($params) {
    $stmt = db()->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $riwayat = db()->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/* ═══════════════════════════════════════════════════════════════════════════
   [FITUR AJAX] ENDPOINT ASINKRON TANPA REFRESH
   ═══════════════════════════════════════════════════════════════════════════ */
$is_ajax = (isset($_GET['ajax']) && $_GET['ajax'] == '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($is_ajax) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $is_filtered      = ($q_cari !== '' || $f_jenis !== '' || $f_dari !== '' || $f_sampai !== '');
    $html_tbody       = render_mutasi_rows($riwayat, $q_cari, $f_jenis, $f_dari, $f_sampai);
    $html_total_info  = render_mutasi_total_info(count($riwayat));
    $html_panel_count = render_mutasi_panel_count(count($riwayat), $is_filtered);

    echo json_encode([
        'status'           => 'success',
        'total'            => count($riwayat),
        'is_filtered'      => $is_filtered,
        'html_tbody'       => $html_tbody,
        'html_total_info'  => $html_total_info,
        'html_panel_count' => $html_panel_count,
        'q'                => $q_cari,
        'jenis'            => $f_jenis,
        'dari'             => $f_dari,
        'sampai'           => $f_sampai,
    ]);
    exit;
}

// Ringkasan metrik mutasi (keseluruhan, tanpa filter)
$metrik = db()->query("
    SELECT
        COALESCE(SUM(CASE WHEN jenis = 'Masuk'  THEN jumlah END), 0)       AS total_unit_masuk,
        COALESCE(SUM(CASE WHEN jenis = 'Keluar' THEN jumlah END), 0)       AS total_unit_keluar,
        COALESCE(SUM(CASE WHEN jenis = 'Masuk'  THEN total_biaya END), 0)  AS total_biaya_masuk,
        COALESCE(SUM(CASE WHEN jenis = 'Keluar' THEN total_biaya END), 0)  AS total_biaya_keluar,
        COUNT(*)                                                            AS total_mutasi
    FROM riwayat_stok
")->fetch_assoc();

// Daftar produk untuk form modal
$produk_list = daftar_produk();

/* ═══════════════════════════════════════════════════════════════════════════
   PAGE META & INCLUDES
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Mutasi Stok';
$page_eyebrow = NAMA_INSTANSI . ' · Inventory';
$root         = '../';
$active       = 'mutasi';

include __DIR__ . '/../includes/header.php';
?>

<!-- Stylesheet khusus halaman Mutasi Stok v7 — Icy Azure Enterprise -->
<link rel="stylesheet" href="../assets/css/mutasi_stok.css">

<!-- Style Inline Penyesuaian Modal & Input Group (Bebas Tabrakan & Sticky Footer) -->
<style>
/* Pastikan backdrop modal menengahkan konten dan bisa scroll bila layar kecil */
#modalMutasi.modal {
    display: none;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 24px 16px;
    z-index: 1050;
}
#modalMutasi.modal.show {
    display: flex !important;
}

/* Kontainer Modal Rapi & Terkunci Max-Height */
#modalMutasi .mts-modal {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 660px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(186, 230, 253, 0.7);
    margin: auto;
}

/* Form Flex Container */
#modalMutasi .mts-modal__form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
    margin: 0;
}

/* Header Modal */
#modalMutasi .mts-modal__header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(186, 230, 253, 0.5);
    background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
}

/* Body Modal dengan Scroll Halus */
#modalMutasi .mts-modal__body {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
    flex: 1;
    min-height: 0;
}

#modalMutasi .mts-modal__body::-webkit-scrollbar {
    width: 6px;
}
#modalMutasi .mts-modal__body::-webkit-scrollbar-track {
    background: #f8fafc;
}
#modalMutasi .mts-modal__body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}
#modalMutasi .mts-modal__body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Footer Modal: Selalu Muncul, Kontras, dan Sticky di Bagian Bawah */
#modalMutasi .mts-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    flex-shrink: 0;
    border-top: 1px solid rgba(226, 232, 240, 0.9);
    background: #f8fafc;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

/* Tombol Batal Sekunder */
#modalMutasi .mts-modal__btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 8px;
    background: #ffffff;
    color: #475569;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 600;
    border: 1.5px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.2s ease;
}
#modalMutasi .mts-modal__btn-cancel:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}

/* Tombol Simpan Biru Korporat Kontras */
#modalMutasi .mts-modal__btn-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 24px;
    border-radius: 8px;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
    transition: all 0.2s ease;
}
#modalMutasi .mts-modal__btn-save:hover {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
    box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
    transform: translateY(-1px);
}
#modalMutasi .mts-modal__btn-save:active {
    transform: translateY(0);
}

/* Input Group Addon "Rp" yang Bersih & Anti Bertabrakan */
#modalMutasi .mts-input-group {
    display: flex;
    align-items: stretch;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    transition: border-color 0.2s, box-shadow 0.2s;
}
#modalMutasi .mts-input-group:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 3.5px rgba(14, 165, 233, 0.15);
}
#modalMutasi .mts-input-addon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 14px;
    background: #e0f2fe;
    border-right: 1.5px solid #bae6fd;
    color: #0369a1;
    font-size: 12.5px;
    font-weight: 800;
    letter-spacing: 0.5px;
    user-select: none;
    flex-shrink: 0;
}
#modalMutasi .mts-input-group .mts-form__input {
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 11px 14px !important;
    flex: 1;
    min-width: 0;
    background: transparent;
    font-size: 13.5px;
    font-weight: 600;
    color: #1e293b;
}
#modalMutasi .mts-input-group .mts-form__input:focus {
    outline: none;
}

/* Status Indikator Pemuatan AJAX */
.ajax-loading-panel {
    opacity: 0.55 !important;
    pointer-events: none !important;
    transition: opacity 0.15s ease;
}
.ajax-fade-in {
    animation: mtsAjaxFadeIn 0.24s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes mtsAjaxFadeIn {
    from {
        opacity: 0.45;
        transform: translateY(3px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     PAGE HERO BANNER + CATAT MUTASI & EXPORT BUTTONS
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="mts-hero" id="mtsHero">
    <div class="mts-hero-left">
        <div class="mts-eyebrow">
            <i class="fa-solid fa-boxes-stacked"></i> MANAJEMEN INVENTORI
        </div>
        <h2 class="mts-hero-title">Mutasi Stok</h2>
        <p class="mts-hero-sub">
            Pantau arus pergerakan barang masuk (restok) dan keluar (penjualan) terintegrasi otomatis dengan buku kas.
        </p>
    </div>
    <div class="mts-hero-actions">
        <?php if (is_admin()): ?>
        <!-- Export -->
        <a href="mutasi_stok.php?export=excel" class="mts-btn-outline" title="Ekspor data riwayat mutasi ke CSV" id="btnExportMutasi">
            <i class="fa-solid fa-file-excel"></i> Export Excel
        </a>
        <!-- Catat Mutasi -->
        <button type="button" class="mts-btn-primary" onclick="openModal('modalMutasi')" id="btnCatatMutasi">
            <i class="fa-solid fa-plus"></i> Catat Mutasi
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     3 METRIC CARDS GLASSMORPHISM — Mutasi Stok
     Total Masuk (Unit & Biaya) · Total Keluar (Unit & Pendapatan) · Total Aktivitas
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="mts-metrics" id="mtsMetrics">

    <!-- 1. Total Barang Masuk -->
    <div class="mts-card mts-card--green" id="cardMutasiMasuk">
        <div class="mts-card__accent"></div>
        <div class="mts-card__header">
            <div class="mts-card__icon mts-card__icon--green">
                <i class="fa-solid fa-arrow-down"></i>
            </div>
            <span class="mts-card__label">Total Barang Masuk</span>
        </div>
        <div class="mts-card__value">
            <span class="mts-card__number"><?= number_format((int)$metrik['total_unit_masuk']) ?></span>
            <span class="mts-card__unit">unit</span>
        </div>
        <div class="mts-card__footer">
            <span class="mts-card__badge mts-card__badge--green">
                <i class="fa-solid fa-coins"></i> Nilai Beli: <?= rupiah($metrik['total_biaya_masuk']) ?>
            </span>
        </div>
    </div>

    <!-- 2. Total Barang Keluar -->
    <div class="mts-card mts-card--blue" id="cardMutasiKeluar">
        <div class="mts-card__accent"></div>
        <div class="mts-card__header">
            <div class="mts-card__icon mts-card__icon--blue">
                <i class="fa-solid fa-arrow-up"></i>
            </div>
            <span class="mts-card__label">Total Barang Keluar</span>
        </div>
        <div class="mts-card__value">
            <span class="mts-card__number"><?= number_format((int)$metrik['total_unit_keluar']) ?></span>
            <span class="mts-card__unit">unit</span>
        </div>
        <div class="mts-card__footer">
            <span class="mts-card__badge mts-card__badge--blue">
                <i class="fa-solid fa-receipt"></i> Penjualan: <?= rupiah($metrik['total_biaya_keluar']) ?>
            </span>
        </div>
    </div>

    <!-- 3. Total Aktivitas Mutasi -->
    <div class="mts-card mts-card--purple" id="cardTotalMutasi">
        <div class="mts-card__accent"></div>
        <div class="mts-card__header">
            <div class="mts-card__icon mts-card__icon--purple">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <span class="mts-card__label">Aktivitas Mutasi</span>
        </div>
        <div class="mts-card__value">
            <span class="mts-card__number"><?= number_format((int)$metrik['total_mutasi']) ?></span>
            <span class="mts-card__unit">transaksi</span>
        </div>
        <div class="mts-card__footer">
            <span class="mts-card__badge mts-card__badge--purple">
                <i class="fa-solid fa-layer-group"></i> Total riwayat mutasi
            </span>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     TOOLBAR: SEARCH + FILTER JENIS + FILTER TANGGAL (CLEAN TWO-ROW SAAS LAYOUT)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="mts-toolbar" id="mtsToolbar">
    <form method="GET" action="mutasi_stok.php" class="mts-toolbar__form" id="filterFormMutasi" onsubmit="return false;">

        <!-- Baris 1: Kotak Pencarian di Sisi Kiri/Atas & Total Mutasi Counter di Sisi Kanan -->
        <div class="mts-toolbar__row--top">
            <div class="mts-search-wrap">
                <!-- Search Box -->
                <div class="mts-search">
                    <i class="fa-solid fa-magnifying-glass mts-search__icon"></i>
                    <input
                        type="text"
                        name="q"
                        class="mts-search__input"
                        placeholder="Cari kode, nama produk, catatan mutasi..."
                        value="<?= e($q_cari) ?>"
                        autocomplete="off"
                        id="searchInputMutasi">
                    <span id="mtsClearArea">
                        <?php if ($q_cari): ?>
                            <button type="button" class="mts-search__clear" id="searchClearBtnMutasi" title="Hapus pencarian">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Total Data Counter Badge -->
            <div class="mts-toolbar__count" id="mtsToolbarCount">
                <?= render_mutasi_total_info(count($riwayat)) ?>
            </div>
        </div>

        <!-- Baris 2: Filter Jenis Mutasi (Kiri) & Rentang Tanggal + Terapkan + Reset (Kanan) secara Horizontal -->
        <div class="mts-toolbar__row--bottom">
            <!-- Filter Jenis Mutasi Pills -->
            <div class="mts-filter-jenis-wrap">
                <span class="mts-filter-label">
                    <i class="fa-solid fa-arrows-rotate"></i> Jenis:
                </span>
                <div class="mts-pill-group" id="mtsPillGroup">
                    <a href="mutasi_stok.php<?= $q_cari ? '?q=' . urlencode($q_cari) : '' ?><?= $f_dari ? '&dari=' . urlencode($f_dari) : '' ?><?= $f_sampai ? '&sampai=' . urlencode($f_sampai) : '' ?>"
                       class="mts-pill <?= $f_jenis === '' ? 'mts-pill--active' : '' ?>"
                       data-jenis="">
                        Semua
                    </a>
                    <a href="mutasi_stok.php?jenis=Masuk<?= $q_cari ? '&q=' . urlencode($q_cari) : '' ?><?= $f_dari ? '&dari=' . urlencode($f_dari) : '' ?><?= $f_sampai ? '&sampai=' . urlencode($f_sampai) : '' ?>"
                       class="mts-pill <?= $f_jenis === 'Masuk' ? 'mts-pill--active mts-pill--masuk' : '' ?>"
                       data-jenis="Masuk">
                        <i class="fa-solid fa-arrow-down"></i> Masuk (Restok)
                    </a>
                    <a href="mutasi_stok.php?jenis=Keluar<?= $q_cari ? '&q=' . urlencode($q_cari) : '' ?><?= $f_dari ? '&dari=' . urlencode($f_dari) : '' ?><?= $f_sampai ? '&sampai=' . urlencode($f_sampai) : '' ?>"
                       class="mts-pill <?= $f_jenis === 'Keluar' ? 'mts-pill--active mts-pill--keluar' : '' ?>"
                       data-jenis="Keluar">
                        <i class="fa-solid fa-arrow-up"></i> Keluar (Terjual)
                    </a>
                </div>
            </div>

            <!-- Date Range Filter & Tombol Terapkan Sejajar Berdampingan Horizontal -->
            <div class="mts-filter-date-wrap">
                <div class="mts-date-range-bar">
                    <span class="mts-filter-label">
                        <i class="fa-regular fa-calendar-days"></i> Periode:
                    </span>
                    <div class="mts-date-inputs-cluster">
                        <div class="mts-date-field">
                            <i class="fa-regular fa-calendar"></i>
                            <input type="date" name="dari" id="filterDari" value="<?= e($f_dari) ?>"
                                   class="mts-date-control" title="Dari tanggal">
                        </div>
                        <span class="mts-date-sep">s/d</span>
                        <div class="mts-date-field">
                            <i class="fa-regular fa-calendar"></i>
                            <input type="date" name="sampai" id="filterSampai" value="<?= e($f_sampai) ?>"
                                   class="mts-date-control" title="Sampai tanggal">
                        </div>
                        <button type="button" class="mts-btn-apply" id="btnApplyDate" title="Terapkan filter tanggal">
                            <i class="fa-solid fa-filter"></i>
                            <span>Terapkan</span>
                        </button>
                    </div>
                </div>

                <div id="mtsResetArea">
                    <?php if ($q_cari || $f_jenis || $f_dari || $f_sampai): ?>
                        <a href="mutasi_stok.php" class="mts-btn-reset" id="mtsBtnReset" title="Kembalikan semua filter ke awal">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Reset Filter</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </form>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     TABEL RIWAYAT MUTASI
     Kolom: # · Tanggal · Produk · Jenis · Jumlah · Harga/Unit · Total Biaya · Keterangan
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="mts-panel" id="mtsPanel">

    <div class="mts-panel__header">
        <div class="mts-panel__title-group">
            <div class="mts-panel__eyebrow">
                <i class="fa-solid fa-clock-rotate-left"></i> RIWAYAT MUTASI
            </div>
            <h3 class="mts-panel__title">
                Pergerakan Barang
                <span id="mtsPanelCountGroup">
                    <?= render_mutasi_panel_count(count($riwayat), ($q_cari || $f_jenis || $f_dari || $f_sampai)) ?>
                </span>
            </h3>
        </div>
    </div>

    <div class="mts-table-wrap">
        <table class="mts-table" id="mtsTable">
            <thead>
                <tr>
                    <th class="mts-table__col-num">#</th>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Jenis Mutasi</th>
                    <th>Jumlah</th>
                    <th class="mts-table__col-right">Harga/Unit</th>
                    <th class="mts-table__col-right">Total Biaya</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody id="mtsTbody">
                <?= render_mutasi_rows($riwayat, $q_cari, $f_jenis, $f_dari, $f_sampai) ?>
            </tbody>
        </table>
    </div>

</div><!-- /.mts-panel -->

<?php if (is_admin()): ?>
<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL: CATAT MUTASI STOK
     Tinggi terkontrol (max-height), scroll halus, footer sticky, input group bersih
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal" id="modalMutasi">
    <div class="mts-modal">
        <!-- Header Modal -->
        <div class="mts-modal__header">
            <div class="mts-modal__header-icon">
                <i class="fa-solid fa-arrows-rotate"></i>
            </div>
            <div class="mts-modal__header-text">
                <h3 class="mts-modal__title" id="modalTitle">Catat Mutasi Stok</h3>
                <p class="mts-modal__subtitle">
                    Pencatatan mutasi akan otomatis memperbarui jumlah stok dan buku kas.
                </p>
            </div>
            <button type="button" class="mts-modal__close" onclick="closeModal('modalMutasi')" id="btnCloseModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Form Modal -->
        <form method="POST" action="mutasi_stok.php" id="formMutasi" class="mts-modal__form">
            <?= csrf_field() ?>

            <div class="mts-modal__body">

                <!-- Alert Notifikasi Validasi Modal Mutasi -->
                <div class="fintoko-modal-alert" id="mtsModalAlert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Terjadi Kesalahan Pengisian:</strong>
                        <ul id="mtsModalAlertList"></ul>
                    </div>
                </div>

                <!-- Section: Identitas Mutasi -->
                <div class="mts-form__section">
                    <i class="fa-solid fa-tag"></i> Informasi Produk &amp; Jenis Mutasi
                </div>

                <!-- Baris 1: Pilih Produk -->
                <div class="mts-form__group">
                    <label class="mts-form__label" for="mutasi_produk">
                        <i class="fa-solid fa-box"></i> Pilih Produk <span class="mts-form__req">*</span>
                    </label>
                    <select id="mutasi_produk" name="produk_id" class="mts-form__select" required>
                        <option value="">-- Pilih Produk Inventori --</option>
                        <?php foreach ($produk_list as $pl): ?>
                            <option value="<?= (int)$pl['id'] ?>"
                                    data-harga-beli="<?= (float)$pl['harga_beli'] ?>"
                                    data-harga-jual="<?= (float)$pl['harga_jual'] ?>"
                                    data-stok="<?= (int)$pl['stok'] ?>"
                                    data-satuan="<?= e($pl['satuan']) ?>">
                                [<?= e($pl['kode_produk']) ?>] <?= e($pl['nama_produk']) ?> (Stok: <?= (int)$pl['stok'] ?> <?= e($pl['satuan']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error-feedback" id="err_mutasi_produk">
                        <i class="fa-solid fa-circle-exclamation"></i> Silakan pilih salah satu produk inventori.
                    </span>
                </div>

                <!-- Baris 2: Jenis Mutasi + Tanggal -->
                <div class="mts-form__row">
                    <div class="mts-form__group">
                        <label class="mts-form__label" for="mutasi_jenis">
                            <i class="fa-solid fa-arrows-up-down"></i> Jenis Mutasi <span class="mts-form__req">*</span>
                        </label>
                        <select id="mutasi_jenis" name="jenis" class="mts-form__select" required>
                            <option value="Masuk">📥 Masuk (Restok / Pembelian)</option>
                            <option value="Keluar">📤 Keluar (Penjualan / Pengurangan)</option>
                        </select>
                    </div>
                    <div class="mts-form__group">
                        <label class="mts-form__label" for="mutasi_tanggal">
                            <i class="fa-solid fa-calendar"></i> Tanggal Mutasi <span class="mts-form__req">*</span>
                        </label>
                        <input type="date" id="mutasi_tanggal" name="tanggal" class="mts-form__input"
                               value="<?= date('Y-m-d') ?>" required>
                        <span class="form-error-feedback" id="err_mutasi_tanggal">
                            <i class="fa-solid fa-circle-exclamation"></i> Tanggal mutasi wajib diisi.
                        </span>
                    </div>
                </div>

                <!-- Section: Jumlah & Biaya -->
                <div class="mts-form__section">
                    <i class="fa-solid fa-coins"></i> Kuantitas &amp; Harga Satuan
                </div>

                <!-- Baris 3: Jumlah + Harga/Unit -->
                <div class="mts-form__row">
                    <div class="mts-form__group">
                        <label class="mts-form__label" for="mutasi_jumlah">
                            <i class="fa-solid fa-calculator"></i> Jumlah Unit <span class="mts-form__req">*</span>
                        </label>
                        <input type="number" id="mutasi_jumlah" name="jumlah" class="mts-form__input"
                               placeholder="1" min="1" step="1" required>
                        <span class="form-error-feedback" id="err_mutasi_jumlah">
                            <i class="fa-solid fa-circle-exclamation"></i> Jumlah mutasi minimal 1 unit.
                        </span>
                    </div>
                    <div class="mts-form__group">
                        <label class="mts-form__label" for="mutasi_harga">
                            <i class="fa-solid fa-tags"></i> Harga Satuan (Rp) <span class="mts-form__req">*</span>
                        </label>
                        <div class="mts-input-group">
                            <span class="mts-input-addon">Rp</span>
                            <input type="number" id="mutasi_harga" name="harga_satuan" class="mts-form__input"
                                   placeholder="0" min="0" step="1" required>
                        </div>
                        <span class="form-error-feedback" id="err_mutasi_harga">
                            <i class="fa-solid fa-circle-exclamation"></i> Harga satuan minimal Rp 0.
                        </span>
                    </div>
                </div>

                <!-- Baris 4: Preview Total Biaya / Pendapatan -->
                <div class="mts-preview-box">
                    <div class="mts-preview-label">Estimasi Total Transaksi:</div>
                    <div class="mts-preview-value" id="previewNominal">Rp 0</div>
                </div>

                <!-- Section: Catatan -->
                <div class="mts-form__section">
                    <i class="fa-solid fa-note-sticky"></i> Keterangan / Catatan
                </div>

                <!-- Baris 5: Keterangan -->
                <div class="mts-form__group">
                    <label class="mts-form__label" for="mutasi_keterangan">
                        <i class="fa-solid fa-pencil"></i> Catatan Mutasi (Opsional)
                    </label>
                    <input type="text" id="mutasi_keterangan" name="keterangan" class="mts-form__input"
                           placeholder="Contoh: Pembelian stok baru dari distributor X..." maxlength="255">
                    <span class="form-error-feedback" id="err_mutasi_keterangan">
                        <i class="fa-solid fa-circle-exclamation"></i> Keterangan maksimal 255 karakter.
                    </span>
                </div>

            </div><!-- /.mts-modal__body -->

            <!-- FOOTER MODAL: Sticky, Tombol Batal & Simpan Muncul dengan Jelas -->
            <div class="mts-modal__footer">
                <button type="button" class="mts-modal__btn-cancel" onclick="closeModal('modalMutasi')">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
                <button type="submit" class="mts-modal__btn-save" id="btnSaveMutasi">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Mutasi
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; /* is_admin */ ?>

<?php
/* ═══════════════════════════════════════════════════════════════════════════
   JAVASCRIPT HALAMAN MUTASI STOK: REAL-TIME AJAX FILTER & LIVE SEARCH
   ═══════════════════════════════════════════════════════════════════════════ */
$extra_scripts = '
<script>
/* ═══════════════════════════════════════════════════════════════════════════
   REAL-TIME AJAX LIVE SEARCH & FILTERING (ZERO PAGE RELOAD)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var searchInput   = document.getElementById("searchInputMutasi");
    var toolbarCount  = document.getElementById("mtsToolbarCount");
    var countGroupEl  = document.getElementById("mtsPanelCountGroup");
    var tbodyEl       = document.getElementById("mtsTbody");
    var pillGroup     = document.getElementById("mtsPillGroup");
    var resetArea     = document.getElementById("mtsResetArea");
    var clearArea     = document.getElementById("mtsClearArea");
    var inputDari     = document.getElementById("filterDari");
    var inputSampai   = document.getElementById("filterSampai");
    var btnApplyDate  = document.getElementById("btnApplyDate");

    var currentJenis   = ' . json_encode($f_jenis) . ';
    var currentQuery   = searchInput ? searchInput.value.trim() : "";
    var currentDari    = inputDari ? inputDari.value : "";
    var currentSampai  = inputSampai ? inputSampai.value : "";
    var searchDebounce = null;
    var abortCtrl      = null;

    function showProgress() {
        var bar = document.getElementById("fintokoAjaxLoader");
        if (bar) {
            bar.classList.add("active");
            bar.style.width = "40%";
            bar.style.opacity = "1";
        }
        if (tbodyEl) {
            tbodyEl.classList.add("ajax-loading-panel");
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
        if (tbodyEl) {
            tbodyEl.classList.remove("ajax-loading-panel");
        }
    }

    function updatePillsUI(jenis) {
        if (!pillGroup) return;
        var pills = pillGroup.querySelectorAll(".mts-pill");
        pills.forEach(function(pill) {
            var pJenis = pill.getAttribute("data-jenis") || "";
            if (pJenis.toLowerCase() === (jenis || "").toLowerCase()) {
                pill.classList.add("mts-pill--active");
                if (jenis === "Masuk") pill.classList.add("mts-pill--masuk");
                if (jenis === "Keluar") pill.classList.add("mts-pill--keluar");
            } else {
                pill.classList.remove("mts-pill--active", "mts-pill--masuk", "mts-pill--keluar");
            }
        });
    }

    function updateResetUI(q, jenis, dari, sampai) {
        var hasFilter = (q !== "" || jenis !== "" || dari !== "" || sampai !== "");

        if (resetArea) {
            if (hasFilter) {
                if (!document.getElementById("mtsBtnReset")) {
                    resetArea.innerHTML = \'<a href="mutasi_stok.php" class="mts-btn-reset" id="mtsBtnReset" title="Kembalikan semua filter ke awal">\' +
                        \'<i class="fa-solid fa-rotate-left"></i> <span>Reset Filter</span></a>\';
                }
            } else {
                resetArea.innerHTML = "";
            }
        }

        if (clearArea) {
            if (q !== "") {
                clearArea.innerHTML = \'<button type="button" class="mts-search__clear" id="searchClearBtnMutasi" title="Hapus pencarian">\' +
                    \'<i class="fa-solid fa-xmark"></i></button>\';
            } else {
                clearArea.innerHTML = "";
            }
        }
    }

    function executeAjaxFilter(q, jenis, dari, sampai, pushHistory) {
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = new AbortController();

        showProgress();

        var params = new URLSearchParams();
        params.append("ajax", "1");
        if (q) params.append("q", q);
        if (jenis) params.append("jenis", jenis);
        if (dari) params.append("dari", dari);
        if (sampai) params.append("sampai", sampai);

        var fetchUrl = "mutasi_stok.php?" + params.toString();

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
                // 1. Perbarui Baris Tabel
                if (tbodyEl) {
                    tbodyEl.innerHTML = data.html_tbody;
                    tbodyEl.classList.add("ajax-fade-in");
                    setTimeout(function() {
                        tbodyEl.classList.remove("ajax-fade-in");
                    }, 300);
                }

                // 2. Perbarui Total Data Counter Toolbar
                if (toolbarCount) {
                    toolbarCount.innerHTML = data.html_total_info;
                }

                // 3. Perbarui Badge Count pada Header Panel
                if (countGroupEl) {
                    countGroupEl.innerHTML = data.html_panel_count;
                }

                // 4. Perbarui Tombol Jenis Aktif
                updatePillsUI(data.jenis);

                // 5. Perbarui Tombol Reset & Clear Search
                updateResetUI(data.q, data.jenis, data.dari, data.sampai);

                // 6. Sinkronisasi URL Address Bar
                if (pushHistory !== false && window.history && window.history.replaceState) {
                    var browserParams = new URLSearchParams();
                    if (data.q) browserParams.append("q", data.q);
                    if (data.jenis) browserParams.append("jenis", data.jenis);
                    if (data.dari) browserParams.append("dari", data.dari);
                    if (data.sampai) browserParams.append("sampai", data.sampai);
                    var cleanUrl = "mutasi_stok.php" + (browserParams.toString() ? "?" + browserParams.toString() : "");
                    window.history.replaceState({ q: data.q, jenis: data.jenis, dari: data.dari, sampai: data.sampai }, "", cleanUrl);
                }
            }
        })
        .catch(function(err) {
            if (err.name !== "AbortError") {
                console.error("[Mutasi AJAX Filter Error]", err);
            }
        })
        .finally(function() {
            hideProgress();
        });
    }

    // 1. Event Input Pencarian dengan Debounce 250ms
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            clearTimeout(searchDebounce);
            currentQuery = searchInput.value.trim();
            searchDebounce = setTimeout(function() {
                executeAjaxFilter(currentQuery, currentJenis, currentDari, currentSampai);
            }, 250);
        });

        searchInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(searchDebounce);
                currentQuery = searchInput.value.trim();
                executeAjaxFilter(currentQuery, currentJenis, currentDari, currentSampai);
            }
        });
    }

    // 2. Event Klik Tombol Jenis Mutasi
    if (pillGroup) {
        pillGroup.addEventListener("click", function(e) {
            var pill = e.target.closest(".mts-pill");
            if (!pill) return;
            e.preventDefault();

            var selectedJenis = pill.getAttribute("data-jenis") || "";
            currentJenis = selectedJenis;
            currentQuery = searchInput ? searchInput.value.trim() : "";
            currentDari  = inputDari ? inputDari.value : "";
            currentSampai = inputSampai ? inputSampai.value : "";
            executeAjaxFilter(currentQuery, currentJenis, currentDari, currentSampai);
        });
    }

    // 3. Event Tombol Terapkan Filter Tanggal
    if (btnApplyDate) {
        btnApplyDate.addEventListener("click", function(e) {
            e.preventDefault();
            currentQuery  = searchInput ? searchInput.value.trim() : "";
            currentDari   = inputDari ? inputDari.value : "";
            currentSampai = inputSampai ? inputSampai.value : "";
            executeAjaxFilter(currentQuery, currentJenis, currentDari, currentSampai);
        });
    }

    // 4. Event Klik Tombol Clear Search & Reset Filter
    document.addEventListener("click", function(e) {
        var clearBtn = e.target.closest("#searchClearBtnMutasi, .mts-search__clear");
        if (clearBtn) {
            e.preventDefault();
            if (searchInput) {
                searchInput.value = "";
                searchInput.focus();
            }
            currentQuery = "";
            executeAjaxFilter(currentQuery, currentJenis, currentDari, currentSampai);
            return;
        }

        var rBtn = e.target.closest("#mtsBtnReset, .mts-btn-reset, .mts-empty-reset");
        if (rBtn) {
            e.preventDefault();
            if (searchInput) searchInput.value = "";
            if (inputDari) inputDari.value = "";
            if (inputSampai) inputSampai.value = "";
            currentQuery  = "";
            currentJenis  = "";
            currentDari   = "";
            currentSampai = "";
            executeAjaxFilter("", "", "", "");
            return;
        }
    });

    // 5. Dukungan Navigasi Tombol Back/Forward Browser
    window.addEventListener("popstate", function() {
        var params = new URLSearchParams(window.location.search);
        var q      = params.get("q") || "";
        var jenis  = params.get("jenis") || "";
        var dari   = params.get("dari") || "";
        var sampai = params.get("sampai") || "";
        if (searchInput) searchInput.value = q;
        if (inputDari) inputDari.value = dari;
        if (inputSampai) inputSampai.value = sampai;
        currentQuery  = q;
        currentJenis  = jenis;
        currentDari   = dari;
        currentSampai = sampai;
        executeAjaxFilter(q, jenis, dari, sampai, false);
    });

    // Helper Global untuk link "Hapus filter →" saat data kosong
    window.resetMutasiFilter = function(e) {
        if (e) e.preventDefault();
        if (searchInput) searchInput.value = "";
        if (inputDari) inputDari.value = "";
        if (inputSampai) inputSampai.value = "";
        currentQuery  = "";
        currentJenis  = "";
        currentDari   = "";
        currentSampai = "";
        executeAjaxFilter("", "", "", "");
    };
})();

/* ═══════════════════════════════════════════════════════════════════════════
   AUTO-FILL HARGA berdasarkan produk & jenis mutasi yang dipilih
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var selProduk = document.getElementById("mutasi_produk");
    var selJenis  = document.getElementById("mutasi_jenis");
    var inpHarga  = document.getElementById("mutasi_harga");

    function autoFillHarga() {
        if (!selProduk || !selJenis || !inpHarga) return;
        var opt = selProduk.options[selProduk.selectedIndex];
        if (!opt || !opt.value) return;

        var jenis = selJenis.value;
        if (jenis === "Masuk") {
            inpHarga.value = opt.getAttribute("data-harga-beli") || 0;
        } else if (jenis === "Keluar") {
            inpHarga.value = opt.getAttribute("data-harga-jual") || 0;
        }
        updatePreview();
    }

    if (selProduk) selProduk.addEventListener("change", autoFillHarga);
    if (selJenis)  selJenis.addEventListener("change", autoFillHarga);
})();

/* ═══════════════════════════════════════════════════════════════════════════
   PREVIEW TOTAL BIAYA — hitung estimasi real-time
   ═══════════════════════════════════════════════════════════════════════════ */
function updatePreview() {
    var jumlah = parseInt(document.getElementById("mutasi_jumlah").value) || 0;
    var harga  = parseFloat(document.getElementById("mutasi_harga").value) || 0;
    var total  = jumlah * harga;
    var fmt    = new Intl.NumberFormat("id-ID", {style: "currency", currency: "IDR", minimumFractionDigits: 0});
    document.getElementById("previewNominal").textContent = fmt.format(total);
}

(function() {
    var inpJumlah = document.getElementById("mutasi_jumlah");
    var inpHarga  = document.getElementById("mutasi_harga");
    if (inpJumlah) inpJumlah.addEventListener("input", updatePreview);
    if (inpHarga)  inpHarga.addEventListener("input", updatePreview);
})();

/* ═══════════════════════════════════════════════════════════════════════════
   VALIDASI FORM INPUT & ALERT FEEDBACK (CLIENT-SIDE)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var form      = document.getElementById("formMutasi");
    var alertBox  = document.getElementById("mtsModalAlert");
    var alertList = document.getElementById("mtsModalAlertList");
    if (!form) return;

    function clearError(id) {
        var input = document.getElementById(id);
        var errEl = document.getElementById("err_" + id);
        if (input) input.classList.remove("is-invalid");
        if (errEl) errEl.classList.remove("is-visible");
    }

    function setError(id, msg) {
        var input = document.getElementById(id);
        var errEl = document.getElementById("err_" + id);
        if (input) input.classList.add("is-invalid");
        if (errEl) {
            errEl.innerHTML = "<i class=\"fa-solid fa-circle-exclamation\"></i> " + msg;
            errEl.classList.add("is-visible");
        }
    }

    function resetAll() {
        if (alertBox) alertBox.classList.remove("is-visible");
        if (alertList) alertList.innerHTML = "";
        ["mutasi_produk", "mutasi_tanggal", "mutasi_jumlah", "mutasi_harga", "mutasi_keterangan"].forEach(clearError);
    }

    ["mutasi_produk", "mutasi_jenis", "mutasi_tanggal", "mutasi_jumlah", "mutasi_harga", "mutasi_keterangan"].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener("input", function() { clearError(id); });
            el.addEventListener("change", function() { clearError(id); });
        }
    });

    form.addEventListener("submit", function(e) {
        resetAll();
        var errors = [];
        var firstInvalid = null;

        var selPrd   = document.getElementById("mutasi_produk");
        var selJenis = document.getElementById("mutasi_jenis");
        var inpTgl   = document.getElementById("mutasi_tanggal");
        var inpJml   = document.getElementById("mutasi_jumlah");
        var inpHrg   = document.getElementById("mutasi_harga");
        var inpKet   = document.getElementById("mutasi_keterangan");

        var opt = selPrd && selPrd.selectedIndex >= 0 ? selPrd.options[selPrd.selectedIndex] : null;
        var prdId = selPrd ? parseInt(selPrd.value, 10) : 0;
        var jenis = selJenis ? selJenis.value : "Masuk";
        var tgl   = inpTgl ? inpTgl.value.trim() : "";
        var jml   = inpJml ? parseInt(inpJml.value, 10) : 0;
        var hrg   = inpHrg ? parseFloat(inpHrg.value) : 0;
        var ket   = inpKet ? inpKet.value.trim() : "";

        // 1. Validasi Produk
        if (!prdId || isNaN(prdId) || prdId <= 0) {
            errors.push("Silakan pilih salah satu produk inventori.");
            setError("mutasi_produk", "Silakan pilih produk terlebih dahulu.");
            if (!firstInvalid) firstInvalid = selPrd;
        }

        // 2. Validasi Tanggal
        if (!tgl) {
            errors.push("Tanggal mutasi wajib diisi.");
            setError("mutasi_tanggal", "Tanggal mutasi wajib diisi.");
            if (!firstInvalid) firstInvalid = inpTgl;
        }

        // 3. Validasi Jumlah & Cek Ketersediaan Stok
        if (isNaN(jml) || jml <= 0) {
            errors.push("Jumlah mutasi unit harus minimal 1.");
            setError("mutasi_jumlah", "Jumlah mutasi unit minimal 1.");
            if (!firstInvalid) firstInvalid = inpJml;
        } else if (opt && jenis === "Keluar") {
            var stokTersedia = parseInt(opt.getAttribute("data-stok"), 10) || 0;
            var satuan = opt.getAttribute("data-satuan") || "unit";
            if (jml > stokTersedia) {
                errors.push("Stok tidak mencukupi! Sisa stok produk ini hanya " + stokTersedia + " " + satuan + " (diminta: " + jml + ").");
                setError("mutasi_jumlah", "Stok tidak cukup (sisa: " + stokTersedia + " " + satuan + ").");
                if (!firstInvalid) firstInvalid = inpJml;
            }
        }

        // 4. Validasi Harga Satuan
        if (isNaN(hrg) || hrg < 0) {
            errors.push("Harga satuan tidak boleh negatif.");
            setError("mutasi_harga", "Harga satuan minimal Rp 0.");
            if (!firstInvalid) firstInvalid = inpHrg;
        }

        // 5. Validasi Keterangan
        if (ket.length > 255) {
            errors.push("Keterangan terlalu panjang (maksimal 255 karakter).");
            setError("mutasi_keterangan", "Maksimal 255 karakter.");
            if (!firstInvalid) firstInvalid = inpKet;
        }

        if (errors.length > 0) {
            e.preventDefault();
            if (alertBox && alertList) {
                alertList.innerHTML = errors.map(function(m) { return "<li>" + m + "</li>"; }).join("");
                alertBox.classList.add("is-visible");
            }
            var modalCard = form.closest(".mts-modal") || form;
            modalCard.classList.remove("shake-it");
            void modalCard.offsetWidth;
            modalCard.classList.add("shake-it");

            if (firstInvalid) firstInvalid.focus();
        }
    });

    window.resetMutasiErrors = resetAll;
})();

/* ═══════════════════════════════════════════════════════════════════════════
   RESET MODAL saat ditutup
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var origCloseModal = window.closeModal;
    if (origCloseModal) {
        window.closeModal = function(id) {
            origCloseModal(id);
            if (id === "modalMutasi") {
                if (window.resetMutasiErrors) window.resetMutasiErrors();
                document.getElementById("formMutasi").reset();
                document.getElementById("mutasi_tanggal").value = new Date().toISOString().split("T")[0];
                document.getElementById("previewNominal").textContent = "Rp 0";
            }
        };
    }
})();

/* ═══════════════════════════════════════════════════════════════════════════
   FORM TRIM — hapus spasi berlebih sebelum submit
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var form = document.getElementById("formMutasi");
    if (form) {
        form.addEventListener("submit", function() {
            form.querySelectorAll("input[type=text]").forEach(function(input) {
                input.value = input.value.trim();
            });
        });
    }
})();
</script>';

include __DIR__ . '/../includes/footer.php';

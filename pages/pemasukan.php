<?php
/**
 * =====================================================================
 * FINTOKO — Sistem Informasi Keuangan & Inventaris
 * =====================================================================
 * File    : pages/pemasukan.php (v8.0 — Enterprise Card Containers & Edit Sync)
 * Halaman : Riwayat Transaksi Pemasukan
 * Fitur   : 1. Card Containers Konsisten (Icy Pastel Blue & Thin Glassmorphism)
 *           2. Live Search & Filter Periode/Metode berbasis AJAX (Tanpa Reload)
 *           3. Modal Edit Transaksi Sinkron & Responsif (Anti-Overlapping)
 *           4. Format Input Rupiah Real-Time (rupiah.js)
 *           5. Custom Popup Konfirmasi Hapus (SweetAlert2)
 *           6. Validasi Form Sisi Klien & Server
 *           7. Export CSV & Cetak Laporan
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');

if (!defined('METODE_LIST')) {
    define('METODE_LIST', ['Tunai', 'Transfer', 'QRIS', 'Debit']);
}

/* ═══════════════════════════════════════════════════════════════════════════
   EKSPOR EXCEL (CSV UTF-8)
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_admin('../');

    $rows_exp = db()->query("
        SELECT tanggal, kategori, keterangan, nominal, metode, input_oleh
        FROM transaksi WHERE jenis = 'Pemasukan'
        ORDER BY tanggal DESC, id DESC
    ")->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pemasukan_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Tanggal', 'Kategori', 'Keterangan', 'Metode', 'Nominal', 'Kasir / Oleh'], ';');

    foreach ($rows_exp as $r) {
        fputcsv($out, [
            $r['tanggal'],
            $r['kategori'],
            $r['keterangan'],
            $r['metode'],
            number_format((float)$r['nominal'], 0, ',', '.'),
            $r['input_oleh'],
        ], ';');
    }
    fclose($out);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   POST: TAMBAH PEMASUKAN
   ═══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'tambah') {
    require_admin('../');

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        redirect('pemasukan.php?error=csrf');
    }

    $tanggal    = trim($_POST['tanggal']    ?? '');
    $kategori   = trim($_POST['kategori']   ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nominal    = (float)($_POST['nominal'] ?? 0);
    $metode     = in_array($_POST['metode'] ?? '', METODE_LIST, true) ? $_POST['metode'] : 'Tunai';

    // Validasi Sisi Server
    $errors = [];
    $dt = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
        $errors[] = 'Format tanggal transaksi tidak valid.';
    }
    if ($kategori === '') {
        $errors[] = 'Kategori transaksi pemasukan wajib diisi.';
    } elseif (mb_strlen($kategori) > 100) {
        $errors[] = 'Kategori terlalu panjang (maksimal 100 karakter).';
    }
    if ($nominal <= 0) {
        $errors[] = 'Nominal pemasukan harus bernilai lebih dari Rp 0.';
    }
    if (mb_strlen($keterangan) > 255) {
        $errors[] = 'Keterangan terlalu panjang (maksimal 255 karakter).';
    }

    if (!empty($errors)) {
        set_flash('error', implode('<br>', $errors));
        redirect('pemasukan.php?error=validation');
    }

    $id = tambah_transaksi($tanggal, 'Pemasukan', $kategori, $nominal, $keterangan, $metode);
    if ($id) {
        set_flash('success', "Pemasukan sebesar <strong>Rp " . number_format($nominal, 0, ',', '.') . "</strong> berhasil dicatat.");
        redirect('pemasukan.php?success=1');
    }

    set_flash('error', 'Gagal menyimpan transaksi ke basis data.');
    redirect('pemasukan.php?error=1');
}

/* ═══════════════════════════════════════════════════════════════════════════
   POST: EDIT PEMASUKAN
   ═══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'edit') {
    require_admin('../');

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        redirect('pemasukan.php?error=csrf');
    }

    $id         = (int)($_POST['edit_id']    ?? 0);
    $tanggal    = trim($_POST['tanggal']     ?? '');
    $kategori   = trim($_POST['kategori']    ?? '');
    $keterangan = trim($_POST['keterangan']  ?? '');
    $nominal    = (float)($_POST['nominal']   ?? 0);
    $metode     = in_array($_POST['metode'] ?? '', METODE_LIST, true) ? $_POST['metode'] : 'Tunai';

    // Validasi Sisi Server
    $errors = [];
    $dt = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
        $errors[] = 'Format tanggal transaksi tidak valid.';
    }
    if ($id <= 0) {
        $errors[] = 'ID transaksi tidak valid.';
    }
    if ($kategori === '') {
        $errors[] = 'Kategori transaksi pemasukan wajib diisi.';
    } elseif (mb_strlen($kategori) > 100) {
        $errors[] = 'Kategori terlalu panjang (maksimal 100 karakter).';
    }
    if ($nominal <= 0) {
        $errors[] = 'Nominal pemasukan harus bernilai lebih dari Rp 0.';
    }
    if (mb_strlen($keterangan) > 255) {
        $errors[] = 'Keterangan terlalu panjang (maksimal 255 karakter).';
    }

    if (!empty($errors)) {
        set_flash('error', implode('<br>', $errors));
        redirect('pemasukan.php?error=validation');
    }

    $stmt = db()->prepare("
        UPDATE transaksi
        SET tanggal = ?, kategori = ?, keterangan = ?, nominal = ?, metode = ?
        WHERE id = ? AND jenis = 'Pemasukan'
    ");
    $stmt->bind_param('sssdsi', $tanggal, $kategori, $keterangan, $nominal, $metode, $id);
    $stmt->execute();
    $stmt->close();

    set_flash('success', 'Transaksi pemasukan berhasil diperbarui.');
    redirect('pemasukan.php?updated=1');
}

/* ═══════════════════════════════════════════════════════════════════════════
   GET: HAPUS PEMASUKAN
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['hapus'])) {
    require_admin('../');

    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['ajax']);

    if (!csrf_valid($_GET['csrf'] ?? '')) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Token keamanan CSRF tidak valid. Silakan muat ulang halaman.']);
            exit;
        }
        redirect('pemasukan.php?error=csrf');
    }

    $id = (int)($_GET['hapus'] ?? 0);
    if ($id > 0) {
        hapus_transaksi($id, 'Pemasukan');
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Transaksi pemasukan berhasil dihapus.']);
            exit;
        }
        set_flash('success', 'Transaksi pemasukan berhasil dihapus.');
    }
    redirect('pemasukan.php?deleted=1');
}

/* ═══════════════════════════════════════════════════════════════════════════
   AJAX: GET DATA UNTUK MODAL EDIT
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['get_row'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $id  = (int)$_GET['get_row'];
    $row = db()->prepare("SELECT * FROM transaksi WHERE id = ? AND jenis = 'Pemasukan'");
    $row->bind_param('i', $id);
    $row->execute();
    $data = $row->get_result()->fetch_assoc();
    $row->close();
    echo json_encode($data ?: [], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   HELPER RENDER PARSIAL (Digunakan SSR dan Endpoint AJAX)
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Render baris <tbody> tabel pemasukan
 */
function render_pemasukan_rows(array $rows, string $q_cari = '', string $f_metode = '', string $f_dari = '', string $f_sampai = ''): string
{
    ob_start();
    if ($rows):
        $no = 1;
        foreach ($rows as $r):
        ?>
        <tr>
            <td class="td-no"><?= $no++ ?></td>
            <td class="td-date">
                <i class="fa-regular fa-calendar"></i>
                <?= tanggal_id($r['tanggal']) ?>
            </td>
            <td>
                <span class="chip"><?= e($r['kategori'] ?: 'Umum') ?></span>
            </td>
            <td class="td-desc"><?= e($r['keterangan'] ?: '—') ?></td>
            <td>
                <?php
                    $mtd     = $r['metode'] ?? 'Tunai';
                    $mtd_cls = [
                        'Tunai'    => 'mtd-cash',
                        'Transfer' => 'mtd-bank',
                        'QRIS'     => 'mtd-qr',
                        'Debit'    => 'mtd-debit',
                    ];
                    $mtd_ico = [
                        'Tunai'    => 'fa-money-bill-wave',
                        'Transfer' => 'fa-building-columns',
                        'QRIS'     => 'fa-qrcode',
                        'Debit'    => 'fa-credit-card',
                    ];
                ?>
                <span class="metode-pill <?= $mtd_cls[$mtd] ?? 'mtd-cash' ?>">
                    <i class="fa-solid <?= $mtd_ico[$mtd] ?? 'fa-money-bill-wave' ?>"></i>
                    <?= e($mtd) ?>
                </span>
            </td>
            <td class="td-amount ta-in">
                + <?= rupiah($r['nominal']) ?>
            </td>
            <td class="td-kasir">
                <i class="fa-solid fa-user-tie"></i>
                <?= e($r['input_oleh'] ?: 'System') ?>
            </td>
            <td class="td-action no-print">
                <?php if (is_admin()): ?>
                <button type="button"
                    class="btn-edit"
                    title="Edit pemasukan ini"
                    onclick="openEdit(<?= (int)$r['id'] ?>, this)">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button type="button"
                   class="btn-del"
                   title="Hapus pemasukan"
                   onclick="confirmHapusPemasukan(event, <?= (int)$r['id'] ?>, '<?= csrf_token() ?>', '<?= addslashes(e($r['kategori'])) ?> (+ <?= rupiah($r['nominal']) ?>)')">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
                <?php else: ?>
                    <span style="font-size:12px;color:#94a3b8;" title="Hanya Administrator yang dapat mengubah data"><i class="fa-solid fa-lock"></i> Read-only</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    else: ?>
        <tr>
            <td colspan="8" class="td-empty">
                <i class="fa-regular fa-folder-open"></i>
                <p>
                    <?= ($q_cari || $f_metode || $f_dari || $f_sampai)
                        ? 'Tidak ada data pemasukan yang cocok dengan kriteria filter. <a href="pemasukan.php" style="color:var(--az-600);font-weight:700;" onclick="window.resetPemasukanFilter(event)">Hapus Filter &rarr;</a>'
                        : 'Belum ada transaksi pemasukan tercatat. Klik <strong>Tambah Pemasukan</strong> di atas untuk memulai.'
                    ?>
                </p>
            </td>
        </tr>
    <?php endif;
    return ob_get_clean();
}

/**
 * Render badge jumlah record
 */
function render_pemasukan_badge(int $count, bool $is_filtered): string
{
    $html = '<span class="record-badge">' . number_format($count, 0, ',', '.') . ' data</span>';
    if ($is_filtered) {
        $html .= '<span class="filter-active-badge"><i class="fa-solid fa-filter"></i> Terfilter</span>';
    }
    return $html;
}

/**
 * Render ringkasan total nominal
 */
function render_pemasukan_summary(array $rows): string
{
    if (!$rows) return '';
    $total = array_sum(array_column($rows, 'nominal'));
    return '<span class="fs-item fs-green"><i class="fa-solid fa-arrow-trend-up"></i> Total: ' . rupiah($total) . '</span>';
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTER & PENCARIAN (PARAMETER)
   ═══════════════════════════════════════════════════════════════════════════ */
$q_cari    = trim($_GET['q'] ?? '');
$f_metode  = trim($_GET['metode'] ?? '');
$f_dari    = trim($_GET['dari'] ?? '');
$f_sampai  = trim($_GET['sampai'] ?? '');
$f_periode = trim($_GET['periode'] ?? '');

if ($f_periode === 'today') {
    $f_dari   = date('Y-m-d');
    $f_sampai = date('Y-m-d');
} elseif ($f_periode === 'bulan_ini') {
    $f_dari   = date('Y-m-01');
    $f_sampai = date('Y-m-t');
} elseif ($f_periode === 'bulan_lalu') {
    $f_dari   = date('Y-m-01', strtotime('-1 month'));
    $f_sampai = date('Y-m-t',  strtotime('-1 month'));
}

// Bangun Query Filter
$sql    = "SELECT * FROM transaksi WHERE jenis = 'Pemasukan'";
$params = [];
$types  = '';

if ($q_cari !== '') {
    $sql .= " AND (kategori LIKE ? OR keterangan LIKE ? OR input_oleh LIKE ?)";
    $like = "%$q_cari%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}

if ($f_metode !== '' && in_array($f_metode, METODE_LIST, true)) {
    $sql .= " AND metode = ?";
    $params[] = $f_metode;
    $types   .= 's';
}

if ($f_dari !== '') {
    $sql .= " AND tanggal >= ?";
    $params[] = $f_dari;
    $types   .= 's';
}

if ($f_sampai !== '') {
    $sql .= " AND tanggal <= ?";
    $params[] = $f_sampai;
    $types   .= 's';
}

$sql .= " ORDER BY tanggal DESC, id DESC";

if ($params) {
    $stmt = db()->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rows = db()->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/* ═══════════════════════════════════════════════════════════════════════════
   ENDPOINT AJAX: MERESPON FETCH() TANPA RELOAD HALAMAN
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=UTF-8');

    $is_filtered  = ($q_cari !== '' || $f_metode !== '' || $f_dari !== '' || $f_sampai !== '' || $f_periode !== '');
    $rows_html    = render_pemasukan_rows($rows, $q_cari, $f_metode, $f_dari, $f_sampai);
    $badge_html   = render_pemasukan_badge(count($rows), $is_filtered);
    $summary_html = render_pemasukan_summary($rows);
    $total_val    = array_sum(array_column($rows, 'nominal'));

    echo json_encode([
        'status'       => 'success',
        'count'        => count($rows),
        'total'        => $total_val,
        'total_fmt'    => rupiah($total_val),
        'rows_html'    => $rows_html,
        'badge_html'   => $badge_html,
        'summary_html' => $summary_html,
        'q'            => $q_cari,
        'metode'       => $f_metode,
        'dari'         => $f_dari,
        'sampai'       => $f_sampai,
        'periode'      => $f_periode,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   METRIK RINGKASAN
   ═══════════════════════════════════════════════════════════════════════════ */
$total_pemasukan = (float)(db()->query("
    SELECT COALESCE(SUM(nominal), 0) AS t FROM transaksi WHERE jenis = 'Pemasukan'
")->fetch_assoc()['t'] ?? 0);

$jumlah_transaksi = (int)(db()->query("
    SELECT COUNT(*) AS c FROM transaksi WHERE jenis = 'Pemasukan'
")->fetch_assoc()['c'] ?? 0);

$tunai_masuk = (float)(db()->query("
    SELECT COALESCE(SUM(nominal), 0) AS t FROM transaksi
    WHERE jenis = 'Pemasukan' AND metode = 'Tunai'
")->fetch_assoc()['t'] ?? 0);

$digital_masuk = $total_pemasukan - $tunai_masuk;

/* ═══════════════════════════════════════════════════════════════════════════
   PAGE META & INCLUDES
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Pemasukan';
$page_eyebrow = 'Manajemen Keuangan';
$root         = '../';
$active       = 'pemasukan';

include __DIR__ . '/../includes/header.php';
?>

<!-- Stylesheet khusus halaman Pemasukan -->
<link rel="stylesheet" href="../assets/css/pemasukan.css">

<style>
/* ═══════════════════════════════════════════════════════════════════════
   KONSISTENSI CARD CONTAINER, MODAL EDIT & SAAS AESTHETICS (ICY PASTEL)
   ═══════════════════════════════════════════════════════════════════════ */
.page-hero {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(240, 249, 255, 0.9) 60%, rgba(224, 242, 254, 0.75) 100%) !important;
    border: 1px solid rgba(186, 230, 253, 0.8) !important;
    border-radius: 16px !important;
    padding: 24px 28px !important;
    box-shadow: 0 10px 30px rgba(14, 165, 233, 0.05), 0 2px 8px rgba(15, 23, 42, 0.03) !important;
    margin-bottom: 22px !important;
}

.pm-metrics {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 16px !important;
    margin-bottom: 24px !important;
}

.pm-card {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border-radius: 16px !important;
    border: 1px solid rgba(186, 230, 253, 0.75) !important;
    padding: 20px 22px !important;
    box-shadow: 0 8px 24px rgba(14, 165, 233, 0.05), 0 2px 6px rgba(15, 23, 42, 0.02) !important;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s, border-color 0.22s !important;
}

.pm-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 14px 34px rgba(14, 165, 233, 0.12), 0 3px 10px rgba(15, 23, 42, 0.04) !important;
    border-color: #7dd3fc !important;
}

/* Card Filter Container Berstandar FinToko */
.filter-toolbar {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border: 1px solid rgba(186, 230, 253, 0.75) !important;
    border-radius: 16px !important;
    padding: 20px 24px !important;
    box-shadow: 0 10px 30px rgba(14, 165, 233, 0.06), 0 2px 8px rgba(15, 23, 42, 0.03) !important;
    margin-bottom: 24px !important;
}

.filter-form {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    flex-wrap: wrap !important;
}

/* Kotak Pencarian dengan Padding 44px (Anti-Overlap) */
.search-box {
    position: relative !important;
    flex: 1 1 280px !important;
    min-width: 240px !important;
}

.search-box .sb-icon {
    position: absolute !important;
    left: 14px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    font-size: 14px !important;
    color: #0284c7 !important;
    pointer-events: none !important;
    z-index: 2 !important;
}

.search-box .sb-input {
    width: 100% !important;
    height: 42px !important;
    padding: 0 38px 0 44px !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    font-size: 13.5px !important;
    color: #0f172a !important;
    background: #ffffff !important;
    outline: none !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
}

.search-box .sb-input:focus {
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 3.5px rgba(14, 165, 233, 0.14) !important;
    background: #fafeff !important;
}

.search-box .sb-clear {
    position: absolute !important;
    right: 12px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    font-size: 13px !important;
    color: #94a3b8 !important;
    background: transparent !important;
    border: none !important;
    cursor: pointer !important;
    padding: 4px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 2 !important;
}

.search-box .sb-clear:hover {
    color: #ef4444 !important;
}

/* Periode Quick Pills */
.periode-btns {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    flex-wrap: wrap !important;
}

.prd-btn {
    display: inline-flex !important;
    align-items: center !important;
    padding: 8px 16px !important;
    border-radius: 100px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    color: #475569 !important;
    background: #f8fafc !important;
    border: 1.5px solid #e2e8f0 !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    cursor: pointer !important;
    white-space: nowrap !important;
}

.prd-btn:hover {
    background: #e0f2fe !important;
    border-color: #7dd3fc !important;
    color: #0369a1 !important;
}

.prd-btn.active {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
    color: #ffffff !important;
    border-color: #0284c7 !important;
    box-shadow: 0 2px 10px rgba(2, 132, 199, 0.3) !important;
}

/* Select Dropdown & Date Range */
.filter-dropdowns .flt-sel,
.date-range-wrap .dr-input {
    height: 42px !important;
    padding: 0 14px !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    font-size: 13px !important;
    color: #334155 !important;
    background: #ffffff !important;
    outline: none !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
}

.date-range-wrap {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

.date-range-wrap .dr-btn {
    height: 42px !important;
    padding: 0 16px !important;
    border-radius: 10px !important;
    background: #0284c7 !important;
    color: #ffffff !important;
    border: none !important;
    cursor: pointer !important;
    font-weight: 700 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25) !important;
}

.date-range-wrap .dr-btn:hover {
    background: #0369a1 !important;
}

.btn-filter-reset {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    height: 40px !important;
    padding: 0 16px !important;
    border-radius: 10px !important;
    background: #fff1f2 !important;
    color: #e11d48 !important;
    border: 1px solid #fecdd3 !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    transition: all 0.18s ease !important;
    white-space: nowrap !important;
}

.btn-filter-reset:hover {
    background: #ffe4e6 !important;
    border-color: #fca5a5 !important;
    transform: translateY(-1px) !important;
}

/* Card Tabel Panel */
.panel {
    background: #ffffff !important;
    border: 1px solid rgba(186, 230, 253, 0.8) !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 30px rgba(14, 165, 233, 0.05), 0 2px 8px rgba(15, 23, 42, 0.02) !important;
    overflow: hidden !important;
    margin-bottom: 24px !important;
}

.panel-head {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 16px !important;
    padding: 20px 24px !important;
    border-bottom: 1px solid rgba(224, 242, 254, 0.8) !important;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
    flex-wrap: wrap !important;
}

.panel-title {
    font-size: 16px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.tbl-scroll {
    overflow-x: auto !important;
    width: 100% !important;
}

.tbl {
    width: 100% !important;
    border-collapse: collapse !important;
    text-align: left !important;
}

.tbl th {
    background: #f8fafc !important;
    padding: 14px 18px !important;
    font-size: 11.5px !important;
    font-weight: 800 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    border-bottom: 1.5px solid #e2e8f0 !important;
    white-space: nowrap !important;
}

.tbl td {
    padding: 14px 18px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    border-bottom: 1px solid #f1f5f9 !important;
    vertical-align: middle !important;
}

.tbl tbody tr:hover {
    background: #f0f9ff !important;
}

/* Modal Popups Standarisasi FinToko */
.modal-card {
    border-radius: 16px !important;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(186, 230, 253, 0.7) !important;
    background: #ffffff !important;
    overflow: hidden !important;
    max-width: 620px !important;
    width: 92% !important;
    margin: auto !important;
}

.modal-hd {
    padding: 20px 24px !important;
    border-bottom: 1px solid rgba(186, 230, 253, 0.6) !important;
    background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%) !important;
}

.modal-body {
    padding: 24px !important;
    gap: 18px !important;
}

/* Sticky Modal Footer & Anti-Overlap Action Buttons */
.modal-ft {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 12px !important;
    padding: 16px 24px !important;
    background: #f8fafc !important;
    border-top: 1px solid #e2e8f0 !important;
    position: sticky !important;
    bottom: 0 !important;
    z-index: 10 !important;
}

.modal-ft .btn-ghost {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    padding: 10px 20px !important;
    border-radius: 8px !important;
    background: #ffffff !important;
    color: #475569 !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    border: 1.5px solid #cbd5e1 !important;
    cursor: pointer !important;
    transition: all 0.18s ease !important;
}

.modal-ft .btn-ghost:hover {
    background: #f1f5f9 !important;
    color: #0f172a !important;
    border-color: #94a3b8 !important;
}

.modal-ft .btn-primary-lg,
.modal-ft .btn-warning-lg {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    padding: 10px 24px !important;
    border-radius: 8px !important;
    color: #ffffff !important;
    font-size: 13.5px !important;
    font-weight: 700 !important;
    border: none !important;
    cursor: pointer !important;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35) !important;
    transition: all 0.2s ease !important;
}

.modal-ft .btn-primary-lg,
.modal-ft .btn-warning-lg {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
}

.modal-ft .btn-primary-lg:hover,
.modal-ft .btn-warning-lg:hover {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%) !important;
    box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45) !important;
    transform: translateY(-1px) !important;
}

/* Responsif Mobile */
@media screen and (max-width: 768px) {
    .pm-metrics {
        grid-template-columns: 1fr 1fr !important;
    }
    .filter-toolbar {
        padding: 16px !important;
    }
    .search-box {
        flex: 1 1 100% !important;
    }
    .modal-ft {
        flex-direction: column-reverse !important;
        gap: 10px !important;
    }
    .modal-ft .btn-ghost,
    .modal-ft .btn-primary-lg,
    .modal-ft .btn-warning-lg {
        width: 100% !important;
    }
}
@media screen and (max-width: 480px) {
    .pm-metrics {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     PAGE HERO BANNER + ACTION BUTTONS
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="page-hero">
    <div class="page-hero-text">
        <div class="page-eyebrow-tag">
            <span class="wb-dot"></span>
            <span>ARUS KAS MASUK &bull; <?= strtoupper(e(label_role_user())) ?></span>
        </div>
        <h2 class="page-hero-title">Riwayat Pemasukan</h2>
        <p class="page-hero-sub">
            Catat semua pemasukan usaha dari penjualan, transfer bank, QRIS, dan sumber pendapatan lainnya.
        </p>
    </div>
    <div class="page-hero-actions">
        <?php if (is_admin()): ?>
        <a href="pemasukan.php?export=excel" class="btn-action-outline" title="Export seluruh data pemasukan ke CSV">
            <i class="fa-solid fa-file-excel"></i>
            <span>Export CSV</span>
        </a>
        <?php endif; ?>
        <button type="button" class="btn-action-outline" onclick="window.print()" title="Cetak laporan pemasukan">
            <i class="fa-solid fa-print"></i>
            <span>Cetak</span>
        </button>
        <?php if (is_admin()): ?>
        <button type="button" class="btn-action-primary" onclick="openModal('modalTambah')">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Pemasukan</span>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     4 METRIC CARDS GLASSMORPHISM
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="pm-metrics">

    <!-- 1. Total Pemasukan -->
    <div class="pm-card pm-green">
        <div class="pm-top">
            <div class="pm-ico"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <span class="pm-lbl">Total Pemasukan</span>
        </div>
        <div class="pm-val">
            <span class="pm-rp">Rp</span>
            <span class="pm-num"><?= number_format($total_pemasukan, 0, ',', '.') ?></span>
        </div>
        <div class="pm-foot">
            <span class="pm-badge pm-badge-green">
                <i class="fa-solid fa-circle-check"></i> Semua periode
            </span>
        </div>
    </div>

    <!-- 2. Jumlah Transaksi -->
    <div class="pm-card pm-blue">
        <div class="pm-top">
            <div class="pm-ico"><i class="fa-solid fa-receipt"></i></div>
            <span class="pm-lbl">Jumlah Transaksi</span>
        </div>
        <div class="pm-val">
            <span class="pm-num"><?= number_format($jumlah_transaksi) ?></span>
            <span class="pm-num-unit">transaksi</span>
        </div>
        <div class="pm-foot">
            <span class="pm-badge pm-badge-blue">
                <i class="fa-solid fa-chart-simple"></i> Semua periode
            </span>
        </div>
    </div>

    <!-- 3. Pemasukan Tunai (Laci) -->
    <div class="pm-card pm-cash">
        <div class="pm-top">
            <div class="pm-ico"><i class="fa-solid fa-money-bill-wave"></i></div>
            <span class="pm-lbl">Pemasukan Tunai</span>
        </div>
        <div class="pm-val">
            <span class="pm-rp">Rp</span>
            <span class="pm-num"><?= number_format($tunai_masuk, 0, ',', '.') ?></span>
        </div>
        <div class="pm-foot">
            <span class="pm-foot-detail">Uang fisik masuk laci kasir</span>
        </div>
    </div>

    <!-- 4. Pemasukan Non-Tunai (Bank / Digital) -->
    <div class="pm-card pm-digital">
        <div class="pm-top">
            <div class="pm-ico"><i class="fa-solid fa-qrcode"></i></div>
            <span class="pm-lbl">Pemasukan Non-Tunai</span>
        </div>
        <div class="pm-val">
            <span class="pm-rp">Rp</span>
            <span class="pm-num"><?= number_format($digital_masuk, 0, ',', '.') ?></span>
        </div>
        <div class="pm-foot">
            <span class="pm-foot-detail">Transfer &bull; QRIS &bull; Debit</span>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     CARD CONTAINER TOOLBAR: SEARCH BAR + QUICK FILTER + DATE RANGE
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="filter-toolbar">
    <form method="GET" action="pemasukan.php" class="filter-form" id="filterForm" onsubmit="return false;">

        <!-- Search Box -->
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass sb-icon"></i>
            <input
                type="text"
                name="q"
                class="sb-input"
                placeholder="Cari kategori, keterangan, kasir..."
                value="<?= e($q_cari) ?>"
                autocomplete="off"
                id="searchInput">
            <span id="pmClearArea">
                <?php if ($q_cari): ?>
                    <button type="button" class="sb-clear" id="searchClearBtnPm" title="Hapus pencarian">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                <?php endif; ?>
            </span>
        </div>

        <!-- Quick Period Buttons -->
        <div class="periode-btns" id="pmPeriodeBtns">
            <a href="pemasukan.php" class="prd-btn <?= $f_periode === '' && $f_dari === '' ? 'active' : '' ?>" data-periode="">
                Semua
            </a>
            <a href="pemasukan.php?periode=today" class="prd-btn <?= $f_periode === 'today' ? 'active' : '' ?>" data-periode="today">
                Hari Ini
            </a>
            <a href="pemasukan.php?periode=bulan_ini" class="prd-btn <?= $f_periode === 'bulan_ini' ? 'active' : '' ?>" data-periode="bulan_ini">
                Bulan Ini
            </a>
            <a href="pemasukan.php?periode=bulan_lalu" class="prd-btn <?= $f_periode === 'bulan_lalu' ? 'active' : '' ?>" data-periode="bulan_lalu">
                Bulan Lalu
            </a>
        </div>

        <!-- Filter: Metode -->
        <div class="filter-dropdowns">
            <select name="metode" id="filterMetode" class="flt-sel">
                <option value="">Semua Metode</option>
                <?php foreach (METODE_LIST as $m): ?>
                    <option value="<?= $m ?>" <?= $f_metode === $m ? 'selected' : '' ?>><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Custom Date Range -->
        <div class="date-range-wrap">
            <input type="date" name="dari" id="filterDari" class="dr-input" value="<?= e($f_dari) ?>" title="Dari tanggal">
            <span class="dr-sep">–</span>
            <input type="date" name="sampai" id="filterSampai" class="dr-input" value="<?= e($f_sampai) ?>" title="Sampai tanggal">
            <button type="button" class="dr-btn" id="btnApplyDatePm" title="Terapkan filter tanggal">
                <i class="fa-solid fa-filter"></i>
            </button>
        </div>

        <div id="pmResetArea">
            <?php if ($q_cari || $f_metode || $f_dari || $f_sampai || $f_periode): ?>
                <a href="pemasukan.php" class="btn-filter-reset" id="btnResetPm" title="Kembalikan semua filter ke awal">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset Filter</span>
                </a>
            <?php endif; ?>
        </div>

    </form>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     CARD CONTAINER TABEL RIWAYAT PEMASUKAN
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="panel" id="printArea">

    <!-- Print-only header -->
    <div class="print-header">
        <h2><?= e(NAMA_INSTANSI) ?> — Laporan Pemasukan</h2>
        <p>Dicetak pada: <?= tanggal_panjang_id(date('Y-m-d')) ?> · <?= e(nama_user()) ?></p>
    </div>

    <div class="panel-head">
        <div>
            <div class="panel-eyebrow">
                <i class="fa-solid fa-arrow-trend-up"></i> RIWAYAT PEMASUKAN
            </div>
            <h3 class="panel-title">
                Daftar Transaksi Masuk
                <span id="pmBadgeWrap">
                    <?= render_pemasukan_badge(count($rows), ($q_cari || $f_metode || $f_dari || $f_sampai || $f_periode)) ?>
                </span>
            </h3>
        </div>
        <!-- Ringkasan hasil filter -->
        <div class="filter-summary" id="pmFilterSummary">
            <?= render_pemasukan_summary($rows) ?>
        </div>
    </div>

    <div class="tbl-scroll">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:42px">#</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Metode</th>
                    <th class="text-right">Nominal</th>
                    <th>Oleh / Kasir</th>
                    <th style="width:90px;text-align:center" class="no-print">Aksi</th>
                </tr>
            </thead>
            <tbody id="pmTbody">
                <?= render_pemasukan_rows($rows, $q_cari, $f_metode, $f_dari, $f_sampai) ?>
            </tbody>
        </table>
    </div>

</div><!-- /.panel -->

<?php if (is_admin()): ?>
<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL: TAMBAH PEMASUKAN
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal" id="modalTambah">
    <div class="modal-card">
        <div class="modal-hd">
            <div class="modal-hd-icon">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div>
                <h3 class="modal-hd-title">Tambah Pemasukan Kas</h3>
                <p class="modal-hd-sub">Catat transaksi penerimaan kas baru ke dalam pembukuan.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalTambah')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="pemasukan.php" id="formTambah">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="tambah">

            <div class="modal-body">
                <!-- Alert Notifikasi Validasi Modal Tambah -->
                <div class="fintoko-modal-alert" id="pmTambahAlert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Terjadi Kesalahan Pengisian:</strong>
                        <ul id="pmTambahAlertList"></ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="t-tanggal">
                            <i class="fa-regular fa-calendar"></i> Tanggal Transaksi <span class="req">*</span>
                        </label>
                        <input type="date" id="t-tanggal" name="tanggal" class="form-input"
                               value="<?= date('Y-m-d') ?>" required>
                        <span class="form-error-feedback" id="err_t_tanggal">
                            <i class="fa-solid fa-circle-exclamation"></i> Tanggal transaksi wajib diisi.
                        </span>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="t-metode">
                            <i class="fa-solid fa-credit-card"></i> Metode Pembayaran <span class="req">*</span>
                        </label>
                        <select id="t-metode" name="metode" class="form-input">
                            <?php foreach (METODE_LIST as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="t-kategori">
                            <i class="fa-solid fa-tag"></i> Kategori Pemasukan <span class="req">*</span>
                        </label>
                        <input type="text" id="t-kategori" name="kategori" class="form-input"
                               placeholder="Misal: Penjualan Produk, Jasa Servis..."
                               maxlength="100" required autocomplete="off">
                        <span class="form-error-feedback" id="err_t_kategori">
                            <i class="fa-solid fa-circle-exclamation"></i> Kategori wajib diisi (maks. 100 karakter).
                        </span>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="t-nominal">
                            <i class="fa-solid fa-coins"></i> Nominal Pemasukan (Rp) <span class="req">*</span>
                        </label>
                        <div class="input-rp-wrap">
                            <span class="input-rp-prefix">Rp</span>
                            <input type="text" id="t-nominal" name="nominal"
                                   class="form-input has-rp-prefix rupiah-input"
                                   inputmode="numeric" placeholder="0" autocomplete="off" required>
                        </div>
                        <span class="form-error-feedback" id="err_t_nominal">
                            <i class="fa-solid fa-circle-exclamation"></i> Nominal harus lebih besar dari Rp 0.
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-col-full">
                        <label class="form-label" for="t-keterangan">
                            <i class="fa-regular fa-note-sticky"></i> Keterangan Tambahan <span class="form-opt">(Opsional)</span>
                        </label>
                        <input type="text" id="t-keterangan" name="keterangan"
                               class="form-input" placeholder="Catatan transaksi atau nama pelanggan..."
                               maxlength="255">
                        <span class="form-error-feedback" id="err_t_keterangan">
                            <i class="fa-solid fa-circle-exclamation"></i> Keterangan maksimal 255 karakter.
                        </span>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <div class="modal-ft">
                <button type="button" class="btn-ghost" onclick="closeModal('modalTambah')">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
                <button type="submit" class="btn-primary-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Pemasukan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL: EDIT PEMASUKAN
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal" id="modalEdit">
    <div class="modal-card">
        <div class="modal-hd">
            <div class="modal-hd-icon edit-icon" style="background: linear-gradient(135deg, #0284c7, #0369a1);">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <h3 class="modal-hd-title">Edit Transaksi Pemasukan</h3>
                <p class="modal-hd-sub">Perbarui informasi rincian penerimaan kas.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalEdit')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="pemasukan.php" id="formEdit">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="edit">
            <input type="hidden" name="edit_id" id="e-id">

            <div class="modal-body">
                <!-- Alert Notifikasi Validasi Modal Edit -->
                <div class="fintoko-modal-alert" id="pmEditAlert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Terjadi Kesalahan Pengisian:</strong>
                        <ul id="pmEditAlertList"></ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="e-tanggal">
                            <i class="fa-regular fa-calendar"></i> Tanggal Transaksi <span class="req">*</span>
                        </label>
                        <input type="date" id="e-tanggal" name="tanggal" class="form-input" required>
                        <span class="form-error-feedback" id="err_e_tanggal">
                            <i class="fa-solid fa-circle-exclamation"></i> Tanggal transaksi wajib diisi.
                        </span>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="e-metode">
                            <i class="fa-solid fa-credit-card"></i> Metode Pembayaran <span class="req">*</span>
                        </label>
                        <select id="e-metode" name="metode" class="form-input">
                            <?php foreach (METODE_LIST as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="e-kategori">
                            <i class="fa-solid fa-tag"></i> Kategori Pemasukan <span class="req">*</span>
                        </label>
                        <input type="text" id="e-kategori" name="kategori" class="form-input"
                               maxlength="100" required autocomplete="off">
                        <span class="form-error-feedback" id="err_e_kategori">
                            <i class="fa-solid fa-circle-exclamation"></i> Kategori wajib diisi (maks. 100 karakter).
                        </span>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="e-nominal">
                            <i class="fa-solid fa-coins"></i> Nominal Pemasukan (Rp) <span class="req">*</span>
                        </label>
                        <div class="input-rp-wrap">
                            <span class="input-rp-prefix">Rp</span>
                            <input type="text" id="e-nominal" name="nominal"
                                   class="form-input has-rp-prefix rupiah-input"
                                   inputmode="numeric" autocomplete="off" required>
                        </div>
                        <span class="form-error-feedback" id="err_e_nominal">
                            <i class="fa-solid fa-circle-exclamation"></i> Nominal harus lebih besar dari Rp 0.
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-col-full">
                        <label class="form-label" for="e-keterangan">
                            <i class="fa-regular fa-note-sticky"></i> Keterangan Tambahan <span class="form-opt">(Opsional)</span>
                        </label>
                        <input type="text" id="e-keterangan" name="keterangan"
                               class="form-input" maxlength="255">
                        <span class="form-error-feedback" id="err_e_keterangan">
                            <i class="fa-solid fa-circle-exclamation"></i> Keterangan maksimal 255 karakter.
                        </span>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <div class="modal-ft">
                <button type="button" class="btn-ghost" onclick="closeModal('modalEdit')">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
                <button type="submit" class="btn-primary-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; /* is_admin */ ?>

<?php
/* ═══════════════════════════════════════════════════════════════════════════
   JAVASCRIPT HALAMAN PEMASUKAN: REAL-TIME AJAX FILTER & LIVE SEARCH
   ═══════════════════════════════════════════════════════════════════════════ */
$extra_scripts = '
<script>
/* ═══════════════════════════════════════════════════════════════════════════
   REAL-TIME AJAX LIVE SEARCH & FILTERING (ZERO PAGE RELOAD)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var searchInput   = document.getElementById("searchInput");
    var tbodyEl       = document.getElementById("pmTbody");
    var badgeWrap     = document.getElementById("pmBadgeWrap");
    var summaryWrap   = document.getElementById("pmFilterSummary");
    var periodeBtns   = document.getElementById("pmPeriodeBtns");
    var selMetode     = document.getElementById("filterMetode");
    var inputDari     = document.getElementById("filterDari");
    var inputSampai   = document.getElementById("filterSampai");
    var btnApplyDate  = document.getElementById("btnApplyDatePm");
    var resetArea     = document.getElementById("pmResetArea");
    var clearArea     = document.getElementById("pmClearArea");

    var currentQuery   = searchInput ? searchInput.value.trim() : "";
    var currentMetode  = selMetode ? selMetode.value : "";
    var currentPeriode = ' . json_encode($f_periode) . ';
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

    function updatePillsUI(periode) {
        if (!periodeBtns) return;
        var btns = periodeBtns.querySelectorAll(".prd-btn");
        btns.forEach(function(btn) {
            var prd = btn.getAttribute("data-periode") || "";
            if (prd === (periode || "")) {
                btn.classList.add("active");
            } else {
                btn.classList.remove("active");
            }
        });
    }

    function updateResetUI(q, metode, dari, sampai, periode) {
        var hasFilter = (q !== "" || metode !== "" || dari !== "" || sampai !== "" || periode !== "");

        if (resetArea) {
            if (hasFilter) {
                if (!document.getElementById("btnResetPm")) {
                    resetArea.innerHTML = \'<a href="pemasukan.php" class="btn-filter-reset" id="btnResetPm" title="Kembalikan semua filter ke awal">\' +
                        \'<i class="fa-solid fa-rotate-left"></i> <span>Reset Filter</span></a>\';
                }
            } else {
                resetArea.innerHTML = "";
            }
        }

        if (clearArea) {
            if (q !== "") {
                clearArea.innerHTML = \'<button type="button" class="sb-clear" id="searchClearBtnPm" title="Hapus pencarian">\' +
                    \'<i class="fa-solid fa-xmark"></i></button>\';
            } else {
                clearArea.innerHTML = "";
            }
        }
    }

    function executeAjaxFilterPm(q, metode, dari, sampai, periode, pushHistory) {
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = new AbortController();

        showProgress();

        var params = new URLSearchParams();
        params.append("ajax", "1");
        if (q) params.append("q", q);
        if (metode) params.append("metode", metode);
        if (periode) params.append("periode", periode);
        if (dari) params.append("dari", dari);
        if (sampai) params.append("sampai", sampai);

        var fetchUrl = "pemasukan.php?" + params.toString();

        fetch(fetchUrl, {
            signal: abortCtrl.signal,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(function(res) {
            if (!res.ok) throw new Error("HTTP error " + res.status);
            return res.json();
        })
        .then(function(data) {
            if (data.status === "success") {
                if (tbodyEl) {
                    tbodyEl.innerHTML = data.rows_html;
                    tbodyEl.classList.remove("ajax-fade-in");
                    void tbodyEl.offsetWidth;
                    tbodyEl.classList.add("ajax-fade-in");
                }
                if (badgeWrap)   badgeWrap.innerHTML   = data.badge_html;
                if (summaryWrap) summaryWrap.innerHTML = data.summary_html;

                updatePillsUI(data.periode);
                updateResetUI(data.q, data.metode, data.dari, data.sampai, data.periode);

                if (pushHistory !== false && window.history && window.history.replaceState) {
                    var browserParams = new URLSearchParams();
                    if (data.q) browserParams.append("q", data.q);
                    if (data.metode) browserParams.append("metode", data.metode);
                    if (data.periode) browserParams.append("periode", data.periode);
                    if (data.dari) browserParams.append("dari", data.dari);
                    if (data.sampai) browserParams.append("sampai", data.sampai);
                    var cleanUrl = "pemasukan.php" + (browserParams.toString() ? "?" + browserParams.toString() : "");
                    window.history.replaceState({ q: data.q, metode: data.metode, dari: data.dari, sampai: data.sampai, periode: data.periode }, "", cleanUrl);
                }
            }
        })
        .catch(function(err) {
            if (err.name !== "AbortError") {
                console.error("[Pemasukan AJAX Filter Error]", err);
            }
        })
        .finally(function() {
            hideProgress();
        });
    }

    // 1. Live Search Input dengan Debounce 250ms
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            clearTimeout(searchDebounce);
            currentQuery = searchInput.value.trim();
            searchDebounce = setTimeout(function() {
                executeAjaxFilterPm(currentQuery, currentMetode, currentDari, currentSampai, currentPeriode);
            }, 250);
        });

        searchInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(searchDebounce);
                currentQuery = searchInput.value.trim();
                executeAjaxFilterPm(currentQuery, currentMetode, currentDari, currentSampai, currentPeriode);
            }
        });
    }

    // 2. Dropdown Filter Metode
    if (selMetode) {
        selMetode.addEventListener("change", function() {
            currentMetode = selMetode.value;
            executeAjaxFilterPm(currentQuery, currentMetode, currentDari, currentSampai, currentPeriode);
        });
    }

    // 3. Tombol Filter Periode Cepat
    if (periodeBtns) {
        periodeBtns.addEventListener("click", function(e) {
            var btn = e.target.closest(".prd-btn");
            if (!btn) return;
            e.preventDefault();

            var prd = btn.getAttribute("data-periode") || "";
            currentPeriode = prd;
            currentDari    = "";
            currentSampai  = "";
            if (inputDari)   inputDari.value = "";
            if (inputSampai) inputSampai.value = "";

            executeAjaxFilterPm(currentQuery, currentMetode, "", "", currentPeriode);
        });
    }

    // 4. Tombol Terapkan Rentang Tanggal
    if (btnApplyDate) {
        btnApplyDate.addEventListener("click", function(e) {
            e.preventDefault();
            currentDari    = inputDari ? inputDari.value : "";
            currentSampai  = inputSampai ? inputSampai.value : "";
            currentPeriode = "";
            executeAjaxFilterPm(currentQuery, currentMetode, currentDari, currentSampai, "");
        });
    }

    // 5. Tombol Clear Search & Reset Filter
    document.addEventListener("click", function(e) {
        var clearBtn = e.target.closest("#searchClearBtnPm, .sb-clear");
        if (clearBtn) {
            e.preventDefault();
            if (searchInput) {
                searchInput.value = "";
                searchInput.focus();
            }
            currentQuery = "";
            executeAjaxFilterPm("", currentMetode, currentDari, currentSampai, currentPeriode);
            return;
        }

        var rBtn = e.target.closest("#btnResetPm, .btn-filter-reset");
        if (rBtn) {
            e.preventDefault();
            if (searchInput) searchInput.value = "";
            if (selMetode)   selMetode.value = "";
            if (inputDari)   inputDari.value = "";
            if (inputSampai) inputSampai.value = "";
            currentQuery   = "";
            currentMetode  = "";
            currentPeriode = "";
            currentDari    = "";
            currentSampai  = "";
            executeAjaxFilterPm("", "", "", "", "");
            return;
        }
    });

    // 6. Dukungan Back/Forward Browser
    window.addEventListener("popstate", function() {
        var params = new URLSearchParams(window.location.search);
        currentQuery   = params.get("q") || "";
        currentMetode  = params.get("metode") || "";
        currentPeriode = params.get("periode") || "";
        currentDari    = params.get("dari") || "";
        currentSampai  = params.get("sampai") || "";

        if (searchInput) searchInput.value = currentQuery;
        if (selMetode)   selMetode.value   = currentMetode;
        if (inputDari)   inputDari.value   = currentDari;
        if (inputSampai) inputSampai.value = currentSampai;

        executeAjaxFilterPm(currentQuery, currentMetode, currentDari, currentSampai, currentPeriode, false);
    });

    // Helper Global untuk reload tabel pemasukan secara asinkron
    window.reloadPemasukanTable = function() {
        executeAjaxFilterPm(currentQuery, currentMetode, currentDari, currentSampai, currentPeriode, false);
    };

    // Helper Global untuk reset filter saat tabel kosong
    window.resetPemasukanFilter = function(e) {
        if (e) e.preventDefault();
        if (searchInput) searchInput.value = "";
        if (selMetode)   selMetode.value = "";
        if (inputDari)   inputDari.value = "";
        if (inputSampai) inputSampai.value = "";
        currentQuery   = "";
        currentMetode  = "";
        currentPeriode = "";
        currentDari    = "";
        currentSampai  = "";
        executeAjaxFilterPm("", "", "", "", "");
    };
})();

/* ═══════════════════════════════════════════════════════════════════════════
   CUSTOM POPUP KONFIRMASI HAPUS PEMASUKAN (AJAX REAL-TIME)
   ═══════════════════════════════════════════════════════════════════════════ */
function confirmHapusPemasukan(e, id, csrf, info) {
    if (e) e.preventDefault();
    window.fintokoDeleteConfirm({
        title: "Hapus Transaksi Pemasukan?",
        text: "Apakah Anda yakin ingin menghapus catatan penerimaan kas ini? Data yang terhapus tidak dapat dipulihkan.",
        itemName: "💹 " + info,
        onConfirm: function() {
            fetch("pemasukan.php?hapus=" + id + "&csrf=" + encodeURIComponent(csrf), {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.status === "success") {
                    window.fintokoToast(data.message || "Pemasukan berhasil dihapus.", "success");
                    if (typeof window.reloadPemasukanTable === "function") {
                        window.reloadPemasukanTable();
                    }
                } else {
                    window.fintokoToast(data.message || "Gagal menghapus pemasukan.", "error");
                }
            })
            .catch(function() {
                window.location.href = "pemasukan.php?deleted=1";
            });
        }
    });
}

/* ═══════════════════════════════════════════════════════════════════════════
   EDIT PEMASUKAN — Ambil data via AJAX & Isi Form Modal
   ═══════════════════════════════════════════════════════════════════════════ */
function openEdit(id, btnEl) {
    var btn = btnEl || (window.event ? window.event.currentTarget : null);
    if (btn) { btn.style.opacity = "0.5"; btn.style.pointerEvents = "none"; }

    fetch("pemasukan.php?get_row=" + id)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (btn) { btn.style.opacity = ""; btn.style.pointerEvents = ""; }
            if (!d || !d.id) { alert("Data transaksi tidak ditemukan."); return; }

            document.getElementById("e-id").value         = d.id;
            document.getElementById("e-tanggal").value    = d.tanggal;
            document.getElementById("e-kategori").value   = d.kategori;

            var nominalInput = document.getElementById("e-nominal");
            var rawNominal   = parseInt(d.nominal, 10) || 0;
            if (nominalInput) {
                nominalInput.value = rawNominal > 0 ? (typeof RupiahInput !== "undefined" ? RupiahInput.fmt(rawNominal) : rawNominal.toLocaleString("id-ID")) : "";
                var hiddenNominal  = nominalInput.nextElementSibling;
                if (hiddenNominal && hiddenNominal.type === "hidden") {
                    hiddenNominal.value = rawNominal > 0 ? rawNominal : "";
                }
            }

            var selMetode = document.getElementById("e-metode");
            if (selMetode) selMetode.value = d.metode || "Tunai";

            var inputKet = document.getElementById("e-keterangan");
            if (inputKet) inputKet.value = d.keterangan || "";

            openModal("modalEdit");
        })
        .catch(function(err) {
            console.error("openEdit error:", err);
            if (btn) { btn.style.opacity = ""; btn.style.pointerEvents = ""; }
            alert("Gagal memuat data transaksi. Periksa koneksi jaringan.");
        });
}

/* ═══════════════════════════════════════════════════════════════════════════
   VALIDASI FORM INPUT & ALERT FEEDBACK (CLIENT-SIDE)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    function setupTransactionValidation(formId, prefix, alertBoxId, alertListId) {
        var form      = document.getElementById(formId);
        var alertBox  = document.getElementById(alertBoxId);
        var alertList = document.getElementById(alertListId);
        if (!form) return;

        function clearError(fieldKey) {
            var input = document.getElementById(prefix + "-" + fieldKey);
            var errEl = document.getElementById("err_" + prefix + "_" + fieldKey);
            if (input) input.classList.remove("is-invalid");
            if (errEl) errEl.classList.remove("is-visible");
        }

        function setError(fieldKey, msg) {
            var input = document.getElementById(prefix + "-" + fieldKey);
            var errEl = document.getElementById("err_" + prefix + "_" + fieldKey);
            if (input) input.classList.add("is-invalid");
            if (errEl) {
                errEl.innerHTML = "<i class=\"fa-solid fa-circle-exclamation\"></i> " + msg;
                errEl.classList.add("is-visible");
            }
        }

        function resetAll() {
            if (alertBox) alertBox.classList.remove("is-visible");
            if (alertList) alertList.innerHTML = "";
            ["tanggal", "kategori", "nominal", "keterangan"].forEach(clearError);
        }

        ["tanggal", "kategori", "nominal", "keterangan"].forEach(function(key) {
            var el = document.getElementById(prefix + "-" + key);
            if (el) {
                el.addEventListener("input", function() { clearError(key); });
                el.addEventListener("change", function() { clearError(key); });
            }
        });

        form.addEventListener("submit", function(e) {
            resetAll();
            var errors = [];
            var firstInvalid = null;

            var tglInput = document.getElementById(prefix + "-tanggal");
            var katInput = document.getElementById(prefix + "-kategori");
            var nomInput = document.getElementById(prefix + "-nominal");
            var ketInput = document.getElementById(prefix + "-keterangan");

            var tgl = tglInput ? tglInput.value.trim() : "";
            var kat = katInput ? katInput.value.trim() : "";
            var ket = ketInput ? ketInput.value.trim() : "";

            // Baca nominal dari hidden input rupiah atau input teks
            var hiddenNom = nomInput ? nomInput.nextElementSibling : null;
            var nomVal = 0;
            if (hiddenNom && hiddenNom.type === "hidden" && hiddenNom.value) {
                nomVal = parseFloat(hiddenNom.value);
            } else if (nomInput) {
                nomVal = parseFloat(nomInput.value.replace(/[^0-9]/g, "")) || 0;
            }

            // 1. Validasi Tanggal
            if (!tgl) {
                errors.push("Tanggal transaksi wajib diisi.");
                setError("tanggal", "Tanggal transaksi wajib diisi.");
                if (!firstInvalid) firstInvalid = tglInput;
            }

            // 2. Validasi Kategori
            if (!kat) {
                errors.push("Kategori transaksi wajib diisi.");
                setError("kategori", "Kategori transaksi wajib diisi.");
                if (!firstInvalid) firstInvalid = katInput;
            } else if (kat.length > 100) {
                errors.push("Kategori maksimal 100 karakter.");
                setError("kategori", "Maksimal 100 karakter.");
                if (!firstInvalid) firstInvalid = katInput;
            }

            // 3. Validasi Nominal
            if (isNaN(nomVal) || nomVal <= 0) {
                errors.push("Nominal pemasukan harus lebih besar dari Rp 0.");
                setError("nominal", "Nominal harus lebih besar dari Rp 0.");
                if (!firstInvalid) firstInvalid = nomInput;
            }

            // 4. Validasi Keterangan
            if (ket.length > 255) {
                errors.push("Keterangan terlalu panjang (maksimal 255 karakter).");
                setError("keterangan", "Maksimal 255 karakter.");
                if (!firstInvalid) firstInvalid = ketInput;
            }

            if (errors.length > 0) {
                e.preventDefault();
                if (alertBox && alertList) {
                    alertList.innerHTML = errors.map(function(m) { return "<li>" + m + "</li>"; }).join("");
                    alertBox.classList.add("is-visible");
                }
                var card = form.closest(".modal-card") || form;
                card.classList.remove("shake-it");
                void card.offsetWidth;
                card.classList.add("shake-it");

                if (firstInvalid) firstInvalid.focus();
            }
        });
    }

    setupTransactionValidation("formTambah", "t", "pmTambahAlert", "pmTambahAlertList");
    setupTransactionValidation("formEdit", "e", "pmEditAlert", "pmEditAlertList");
})();
</script>
<script src="<?= BASE_URL ?>assets/js/rupiah.js"></script>';

include __DIR__ . '/../includes/footer.php';

<?php
/**
 * =====================================================================
 * FINTOKO — Sistem Informasi Keuangan & Inventaris
 * =====================================================================
 * File    : pages/kas.php   (v7.0 — Real-Time AJAX Filtering & Search)
 * Halaman : Buku Kas Profesional
 * Fitur   : 1. Live Search & Filter Periode/Jenis/Metode berbasis AJAX (Zero Page Reload)
 *           2. Endpoint Asinkron JSON dengan partial HTML render
 *           3. Debounce 250ms & AbortController (Bebas Race Condition)
 *           4. Sinkronisasi History URL Browser (history.replaceState)
 *           5. 5 Kartu Metrik Glassmorphism — Icy Pastel Blue
 *           6. Modal Tambah & Edit Transaksi + Bukti Lampiran
 *           7. Ekspor Excel Native dengan Auto-Fit Column Width
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');

/* ═══════════════════════════════════════════════════════════════════════════
   KONSTANTA & HELPER LOKAL
   ═══════════════════════════════════════════════════════════════════════════ */
define('UPLOAD_DIR', __DIR__ . '/../uploads/bukti/');
define('UPLOAD_URL', '../uploads/bukti/');
define('MAX_FILE_MB', 5);

/** Daftar metode pembayaran yang valid */
$METODE_LIST = ['Tunai', 'Transfer', 'QRIS', 'Debit'];

/**
 * Upload file bukti transaksi (gambar atau PDF).
 */
function upload_bukti(array $file): ?string
{
    return secure_upload($file, UPLOAD_DIR, MAX_FILE_MB);
}

/**
 * Hapus file bukti lama dari disk secara aman (mencegah path traversal).
 */
function hapus_file_bukti(?string $filename): void
{
    safe_delete_file($filename, UPLOAD_DIR);
}

/* ═══════════════════════════════════════════════════════════════════════════
   EKSPOR EXCEL NATIVE DENGAN AUTO-FIT COLUMN WIDTH & PADDING
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_admin('../');

    $rows_exp = db()->query("
        SELECT tanggal, jenis, kategori, keterangan, metode, nominal, input_oleh
        FROM transaksi ORDER BY tanggal DESC, id DESC
    ")->fetch_all(MYSQLI_ASSOC);

    $headers = [
        'No',
        'Tanggal Transaksi',
        'Jenis Kas',
        'Kategori',
        'Keterangan Transaksi',
        'Metode Pembayaran',
        'Nominal (Rp)',
        'Dicatat Oleh'
    ];

    $export_data = [];
    $no = 1;
    foreach ($rows_exp as $r) {
        $export_data[] = [
            $no++,
            tanggal_id($r['tanggal']),
            $r['jenis'],
            $r['kategori'] ?: 'Umum',
            $r['keterangan'] ?: '—',
            $r['metode'] ?? 'Tunai',
            (float)$r['nominal'],
            $r['input_oleh'] ?? '—'
        ];
    }

    export_excel_auto_fit(
        'buku_kas_fintoko',
        $headers,
        $export_data,
        [0 => 'number', 6 => 'number'],
        'Buku Kas FinToko'
    );
}

/* ═══════════════════════════════════════════════════════════════════════════
   POST: TAMBAH TRANSAKSI
   ═══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'tambah') {
    require_admin('../');

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        redirect('kas.php?error=csrf');
    }

    $tanggal    = trim($_POST['tanggal']    ?? '');
    $jenis      = trim($_POST['jenis']      ?? '');
    $kategori   = trim($_POST['kategori']   ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nominal    = (float)($_POST['nominal']  ?? 0);
    $metode     = in_array($_POST['metode'] ?? '', $METODE_LIST, true) ? $_POST['metode'] : 'Tunai';
    $input_oleh = nama_user();
    $bukti_file = upload_bukti($_FILES['bukti_file'] ?? []);

    // Validasi String & Tipe Data Ketat di Sisi Server
    $errors = [];
    $dt = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
        $errors[] = 'Format tanggal transaksi tidak valid.';
    }
    if (!in_array($jenis, ['Pemasukan', 'Pengeluaran'], true)) {
        $errors[] = 'Jenis transaksi harus Pemasukan atau Pengeluaran.';
    }
    if ($kategori === '') {
        $errors[] = 'Kategori transaksi buku kas wajib diisi.';
    } elseif (mb_strlen($kategori) > 100) {
        $errors[] = 'Kategori terlalu panjang (maksimal 100 karakter).';
    }
    if ($nominal <= 0) {
        $errors[] = 'Nominal transaksi harus bernilai lebih dari Rp 0.';
    }
    if (mb_strlen($keterangan) > 255) {
        $errors[] = 'Keterangan terlalu panjang (maksimal 255 karakter).';
    }

    if (!empty($errors)) {
        hapus_file_bukti($bukti_file);
        set_flash('error', implode('<br>', $errors));
        redirect('kas.php?error=validation');
    }

    $stmt = db()->prepare("
        INSERT INTO transaksi
            (tanggal, jenis, kategori, keterangan, nominal, metode, input_oleh, bukti_file)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssdsss',
        $tanggal, $jenis, $kategori, $keterangan,
        $nominal, $metode, $input_oleh, $bukti_file
    );
    $stmt->execute();
    $stmt->close();

    set_flash('success',
        "Transaksi <strong>{$jenis}</strong> sebesar <strong>Rp "
        . number_format($nominal, 0, ',', '.') . "</strong> berhasil dicatat."
    );
    redirect('kas.php?success=1');
}

/* ═══════════════════════════════════════════════════════════════════════════
   POST: EDIT TRANSAKSI
   ═══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'edit') {
    require_admin('../');

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        redirect('kas.php?error=csrf');
    }

    $id         = (int)($_POST['edit_id']    ?? 0);
    $tanggal    = trim($_POST['tanggal']     ?? '');
    $jenis      = trim($_POST['jenis']       ?? '');
    $kategori   = trim($_POST['kategori']    ?? '');
    $keterangan = trim($_POST['keterangan']  ?? '');
    $nominal    = (float)($_POST['nominal']   ?? 0);
    $metode     = in_array($_POST['metode'] ?? '', $METODE_LIST, true) ? $_POST['metode'] : 'Tunai';

    // Validasi String & Tipe Data Ketat di Sisi Server
    $errors = [];
    $dt = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
        $errors[] = 'Format tanggal transaksi tidak valid.';
    }
    if ($id <= 0) {
        $errors[] = 'ID transaksi tidak valid.';
    }
    if (!in_array($jenis, ['Pemasukan', 'Pengeluaran'], true)) {
        $errors[] = 'Jenis transaksi harus Pemasukan atau Pengeluaran.';
    }
    if ($kategori === '') {
        $errors[] = 'Kategori transaksi buku kas wajib diisi.';
    } elseif (mb_strlen($kategori) > 100) {
        $errors[] = 'Kategori terlalu panjang (maksimal 100 karakter).';
    }
    if ($nominal <= 0) {
        $errors[] = 'Nominal transaksi harus bernilai lebih dari Rp 0.';
    }
    if (mb_strlen($keterangan) > 255) {
        $errors[] = 'Keterangan terlalu panjang (maksimal 255 karakter).';
    }

    if (!empty($errors)) {
        set_flash('error', implode('<br>', $errors));
        redirect('kas.php?error=validation');
    }

    $bukti_baru = upload_bukti($_FILES['bukti_file'] ?? []);

    if ($bukti_baru) {
        $old = db()->prepare('SELECT bukti_file FROM transaksi WHERE id = ?');
        $old->bind_param('i', $id);
        $old->execute();
        $old_file = $old->get_result()->fetch_assoc()['bukti_file'] ?? null;
        $old->close();
        hapus_file_bukti($old_file);

        $stmt = db()->prepare("
            UPDATE transaksi
            SET tanggal = ?, jenis = ?, kategori = ?, keterangan = ?,
                nominal = ?, metode = ?, bukti_file = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssssdssi',
            $tanggal, $jenis, $kategori, $keterangan,
            $nominal, $metode, $bukti_baru, $id
        );
    } else {
        $stmt = db()->prepare("
            UPDATE transaksi
            SET tanggal = ?, jenis = ?, kategori = ?, keterangan = ?,
                nominal = ?, metode = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssssdsi',
            $tanggal, $jenis, $kategori, $keterangan,
            $nominal, $metode, $id
        );
    }

    $stmt->execute();
    $stmt->close();

    set_flash('success', "Transaksi berhasil diperbarui.");
    redirect('kas.php?updated=1');
}

/* ═══════════════════════════════════════════════════════════════════════════
   GET: HAPUS TRANSAKSI
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['hapus'])) {
    require_admin('../');

    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['ajax']);

    if (!csrf_valid($_GET['csrf'] ?? '')) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid. Silakan muat ulang halaman.']);
            exit;
        }
        redirect('kas.php?error=csrf');
    }

    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        $st = db()->prepare('SELECT bukti_file FROM transaksi WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $bf = $st->get_result()->fetch_assoc()['bukti_file'] ?? null;
        $st->close();
        hapus_file_bukti($bf);

        $del = db()->prepare('DELETE FROM transaksi WHERE id = ?');
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();

        if ($is_ajax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil dihapus dari buku kas.']);
            exit;
        }

        set_flash('success', 'Transaksi berhasil dihapus dari buku kas.');
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'error', 'message' => 'ID transaksi tidak valid.']);
            exit;
        }
    }
    redirect('kas.php?deleted=1');
}

/* ═══════════════════════════════════════════════════════════════════════════
   AJAX: GET DATA UNTUK MODAL EDIT
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['get_row'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $id  = (int)$_GET['get_row'];
    $row = db()->prepare('SELECT * FROM transaksi WHERE id = ?');
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
 * Render baris <tbody> tabel riwayat buku kas
 */
function render_kas_rows(array $rows, string $q_cari = '', string $f_jenis = '', string $f_metode = '', string $f_dari = '', string $f_sampai = ''): string
{
    ob_start();
    if ($rows):
        $no = 1;
        foreach ($rows as $r):
        ?>
        <tr>
            <td class="td-no"><?= $no++ ?></td>
            <td class="td-date">
                <i class="fa-regular fa-calendar-check" style="color:var(--az-500);margin-right:4px;"></i>
                <?= tanggal_id($r['tanggal']) ?>
            </td>
            <td>
                <?php if ($r['jenis'] === 'Pemasukan'): ?>
                    <span class="pill pill-in">
                        <i class="fa-solid fa-arrow-trend-up"></i> Masuk
                    </span>
                <?php else: ?>
                    <span class="pill pill-out">
                        <i class="fa-solid fa-arrow-trend-down"></i> Keluar
                    </span>
                <?php endif; ?>
            </td>
            <td>
                <span class="chip-kategori"><?= e($r['kategori'] ?: 'Umum') ?></span>
            </td>
            <td class="td-desc" title="<?= e($r['keterangan']) ?>"><?= e($r['keterangan'] ?: '—') ?></td>
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
            <td class="td-amount <?= $r['jenis'] === 'Pemasukan' ? 'ta-in' : 'ta-out' ?>">
                <?= $r['jenis'] === 'Pemasukan' ? '+' : '−' ?> <?= rupiah($r['nominal']) ?>
            </td>
            <td class="td-kasir">
                <i class="fa-solid fa-user-circle"></i>
                <span><?= e($r['input_oleh'] ?: 'System') ?></span>
            </td>
            <td class="td-bukti">
                <?php if (!empty($r['bukti_file'])): ?>
                    <?php $ext_b = strtolower(pathinfo($r['bukti_file'], PATHINFO_EXTENSION)); ?>
                    <?php if (in_array($ext_b, ['jpg','jpeg','png','gif','webp'])): ?>
                        <a href="<?= UPLOAD_URL . e($r['bukti_file']) ?>"
                           target="_blank" class="bukti-thumb" title="Lihat lampiran bukti foto">
                            <img src="<?= UPLOAD_URL . e($r['bukti_file']) ?>" alt="Bukti" loading="lazy">
                        </a>
                    <?php else: ?>
                        <a href="<?= UPLOAD_URL . e($r['bukti_file']) ?>"
                           target="_blank" class="bukti-file" title="Buka lampiran dokumen PDF">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="no-bukti-dash" title="Tidak ada lampiran file">&mdash;</span>
                <?php endif; ?>
            </td>
            <td class="td-action no-print">
                <?php if (is_admin()): ?>
                <button type="button"
                    class="btn-edit"
                    title="Edit transaksi ini"
                    onclick="openEdit(<?= (int)$r['id'] ?>)">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button type="button"
                   class="btn-del"
                   title="Hapus transaksi ini"
                   onclick="confirmHapusKas(event, <?= (int)$r['id'] ?>, '<?= csrf_token() ?>', '<?= addslashes(e($r['kategori'])) ?> (<?= $r['jenis'] === 'Pemasukan' ? '+' : '-' ?> <?= rupiah($r['nominal']) ?>)')">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
                <?php else: ?>
                    <span style="font-size:11.5px;color:#94a3b8;font-weight:600;" title="Hanya Administrator yang dapat memodifikasi data">
                        <i class="fa-solid fa-lock"></i> Read-only
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    else: ?>
        <tr>
            <td colspan="10" class="td-empty">
                <i class="fa-regular fa-folder-open"></i>
                <p>
                    <?= ($q_cari || $f_jenis || $f_metode || $f_dari || $f_sampai)
                        ? 'Tidak ada transaksi yang sesuai dengan kriteria filter. <a href="kas.php" style="color:var(--az-600);font-weight:700;" onclick="window.resetKasFilter(event)">Hapus Filter &rarr;</a>'
                        : 'Belum ada transaksi kas tercatat. Klik <strong>Tambah Transaksi</strong> di atas untuk memulai pencatatan.'
                    ?>
                </p>
            </td>
        </tr>
    <?php endif;
    return ob_get_clean();
}

/**
 * Render ringkasan total uang masuk & keluar pada tabel
 */
function render_kas_summary(array $rows): string
{
    if (!$rows) return '';
    $sum_in  = array_sum(array_column(array_filter($rows, fn($r) => $r['jenis'] === 'Pemasukan'), 'nominal'));
    $sum_out = array_sum(array_column(array_filter($rows, fn($r) => $r['jenis'] === 'Pengeluaran'), 'nominal'));

    return '
        <span class="fs-item fs-green" title="Total kas masuk pada daftar yang tampil">
            <i class="fa-solid fa-arrow-down-left"></i> Masuk: ' . rupiah($sum_in) . '
        </span>
        <span class="fs-item fs-red" title="Total kas keluar pada daftar yang tampil">
            <i class="fa-solid fa-arrow-up-right"></i> Keluar: ' . rupiah($sum_out) . '
        </span>
    ';
}

/**
 * Render badge jumlah baris data
 */
function render_kas_badge(int $count, bool $is_filtered): string
{
    $html = '<span class="record-badge">' . number_format($count, 0, ',', '.') . ' data</span>';
    if ($is_filtered) {
        $html .= ' <span class="filter-active-badge"><i class="fa-solid fa-filter"></i> Hasil Filter</span>';
    }
    return $html;
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTER & PENCARIAN PARAMETER
   ═══════════════════════════════════════════════════════════════════════════ */
$q_cari    = trim($_GET['q']       ?? '');
$f_jenis   = trim($_GET['jenis']   ?? '');
$f_metode  = trim($_GET['metode']  ?? '');
$f_periode = trim($_GET['periode'] ?? '');
$f_dari    = trim($_GET['dari']    ?? '');
$f_sampai  = trim($_GET['sampai']  ?? '');

if ($f_periode === 'today') {
    $f_dari = $f_sampai = date('Y-m-d');
} elseif ($f_periode === 'bulan_ini') {
    $f_dari   = date('Y-m-01');
    $f_sampai = date('Y-m-t');
} elseif ($f_periode === 'bulan_lalu') {
    $f_dari   = date('Y-m-01', strtotime('first day of last month'));
    $f_sampai = date('Y-m-t',  strtotime('last day of last month'));
}

$where  = [];
$params = [];
$types  = '';

if ($q_cari !== '') {
    $where[]  = '(kategori LIKE ? OR keterangan LIKE ? OR input_oleh LIKE ?)';
    $like     = "%{$q_cari}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}
if (in_array($f_jenis, ['Pemasukan', 'Pengeluaran'], true)) {
    $where[]  = 'jenis = ?';
    $params[] = $f_jenis;
    $types   .= 's';
}
if (in_array($f_metode, $METODE_LIST, true)) {
    $where[]  = 'metode = ?';
    $params[] = $f_metode;
    $types   .= 's';
}
if ($f_dari !== '') {
    $where[]  = 'tanggal >= ?';
    $params[] = $f_dari;
    $types   .= 's';
}
if ($f_sampai !== '') {
    $where[]  = 'tanggal <= ?';
    $params[] = $f_sampai;
    $types   .= 's';
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql_full  = "SELECT * FROM transaksi {$sql_where} ORDER BY tanggal DESC, id DESC";

if ($params) {
    $stmt_list = db()->prepare($sql_full);
    $stmt_list->bind_param($types, ...$params);
    $stmt_list->execute();
    $rows = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_list->close();
} else {
    $rows = db()->query($sql_full)->fetch_all(MYSQLI_ASSOC);
}

/* ═══════════════════════════════════════════════════════════════════════════
   [FITUR AJAX] ENDPOINT ASINKRON TANPA REFRESH
   ═══════════════════════════════════════════════════════════════════════════ */
$is_ajax = (isset($_GET['ajax']) && $_GET['ajax'] == '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($is_ajax) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $is_filtered  = ($q_cari !== '' || $f_jenis !== '' || $f_metode !== '' || $f_dari !== '' || $f_sampai !== '' || $f_periode !== '');
    $html_tbody   = render_kas_rows($rows, $q_cari, $f_jenis, $f_metode, $f_dari, $f_sampai);
    $html_summary = render_kas_summary($rows);
    $html_badge   = render_kas_badge(count($rows), $is_filtered);

    echo json_encode([
        'status'       => 'success',
        'total'        => count($rows),
        'is_filtered'  => $is_filtered,
        'html_tbody'   => $html_tbody,
        'html_summary' => $html_summary,
        'html_badge'   => $html_badge,
        'q'            => $q_cari,
        'jenis'        => $f_jenis,
        'metode'       => $f_metode,
        'dari'         => $f_dari,
        'sampai'       => $f_sampai,
        'periode'      => $f_periode,
    ]);
    exit;
}

// Ringkasan metrik kas (keseluruhan)
$ring         = ringkasan_kas();
$total_masuk  = (float)($ring['masuk']  ?? 0);
$total_keluar = (float)($ring['keluar'] ?? 0);
$saldo_kas    = (float)($ring['saldo']  ?? 0);

$tunai_masuk = (float)(db()->query("
    SELECT COALESCE(SUM(nominal), 0) AS t FROM transaksi
    WHERE jenis = 'Pemasukan' AND metode = 'Tunai'
")->fetch_assoc()['t'] ?? 0);

$tunai_keluar = (float)(db()->query("
    SELECT COALESCE(SUM(nominal), 0) AS t FROM transaksi
    WHERE jenis = 'Pengeluaran' AND metode = 'Tunai'
")->fetch_assoc()['t'] ?? 0);

$digital_masuk  = $total_masuk  - $tunai_masuk;
$digital_keluar = $total_keluar - $tunai_keluar;

/* ═══════════════════════════════════════════════════════════════════════════
   PAGE META & INCLUDES
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Buku Kas';
$page_eyebrow = 'Manajemen Keuangan';
$root         = '../';
$active       = 'kas';

include __DIR__ . '/../includes/header.php';
?>

<!-- Stylesheet khusus halaman Kas -->
<link rel="stylesheet" href="../assets/css/kas.css">

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     PAGE HERO BANNER + EXPORT BUTTONS
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="page-hero">
    <div class="page-hero-text">
        <div class="page-eyebrow-tag">
            <span class="wb-dot"></span>
            <span>MANAJEMEN KAS UTAMA &bull; <?= strtoupper(e(label_role_user())) ?></span>
        </div>
        <h2 class="page-hero-title">Manajemen Buku Kas</h2>
        <p class="page-hero-sub">
            Catat arus kas masuk, pengeluaran operasional, verifikasi bukti pembayaran, dan pantau likuiditas toko secara <em>real-time</em>.
        </p>
    </div>
    <div class="page-hero-actions">
        <?php if (is_admin()): ?>
        <a href="kas.php?export=excel" class="btn-action-outline" title="Ekspor seluruh data transaksi ke file Excel/CSV">
            <i class="fa-solid fa-file-excel"></i>
            <span>Export Excel</span>
        </a>
        <?php endif; ?>
        <button type="button" class="btn-action-outline" onclick="window.print()" title="Cetak laporan kas ke printer atau simpan sebagai PDF">
            <i class="fa-solid fa-print"></i>
            <span>Cetak PDF</span>
        </button>
        <?php if (is_admin()): ?>
        <button type="button" class="btn-action-primary" onclick="openModal('modalTambah')">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Transaksi</span>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     5 METRIC CARDS GLASSMORPHISM
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="kas-metrics">

    <!-- 1. Total Kas Masuk -->
    <div class="km-card km-green">
        <div class="km-accent km-accent-green"></div>
        <div class="km-top">
            <div>
                <span class="km-eyebrow">ARUS MASUK</span>
                <span class="km-lbl">Total Kas Masuk</span>
            </div>
            <div class="km-ico"><i class="fa-solid fa-arrow-trend-up"></i></div>
        </div>
        <div class="km-val">
            <span class="km-rp">Rp</span>
            <span class="km-num"><?= number_format($total_masuk, 0, ',', '.') ?></span>
        </div>
        <div class="km-foot">
            <span class="kbadge kbadge-green">
                <i class="fa-solid fa-circle-check"></i> Akumulasi Masuk
            </span>
        </div>
    </div>

    <!-- 2. Total Kas Keluar -->
    <div class="km-card km-red">
        <div class="km-accent km-accent-red"></div>
        <div class="km-top">
            <div>
                <span class="km-eyebrow">ARUS KELUAR</span>
                <span class="km-lbl">Total Kas Keluar</span>
            </div>
            <div class="km-ico"><i class="fa-solid fa-arrow-trend-down"></i></div>
        </div>
        <div class="km-val">
            <span class="km-rp">Rp</span>
            <span class="km-num"><?= number_format($total_keluar, 0, ',', '.') ?></span>
        </div>
        <div class="km-foot">
            <span class="kbadge kbadge-red">
                <i class="fa-solid fa-circle-minus"></i> Akumulasi Keluar
            </span>
        </div>
    </div>

    <!-- 3. Saldo Bersih -->
    <div class="km-card km-blue">
        <div class="km-accent km-accent-blue"></div>
        <div class="km-top">
            <div>
                <span class="km-eyebrow">POSISI KAS</span>
                <span class="km-lbl">Saldo Kas Aktif</span>
            </div>
            <div class="km-ico"><i class="fa-solid fa-scale-balanced"></i></div>
        </div>
        <div class="km-val">
            <span class="km-rp">Rp</span>
            <span class="km-num"><?= number_format($saldo_kas, 0, ',', '.') ?></span>
        </div>
        <div class="km-foot">
            <span class="kbadge kbadge-blue">
                <i class="fa-solid fa-vault"></i> Kas Toko Real-Time
            </span>
        </div>
    </div>

    <!-- 4. Kas Tunai (Laci) -->
    <div class="km-card km-amber">
        <div class="km-accent km-accent-amber"></div>
        <div class="km-top">
            <div>
                <span class="km-eyebrow">UANG TUNAI</span>
                <span class="km-lbl">Kas Fisik di Laci</span>
            </div>
            <div class="km-ico"><i class="fa-solid fa-money-bill-wave"></i></div>
        </div>
        <div class="km-val">
            <span class="km-rp">Rp</span>
            <span class="km-num km-sm"><?= number_format($tunai_masuk - $tunai_keluar, 0, ',', '.') ?></span>
        </div>
        <div class="km-foot">
            <span class="kbadge kbadge-amber">
                <i class="fa-solid fa-hand-holding-dollar"></i> Uang Tunai Siap Pakai
            </span>
        </div>
    </div>

    <!-- 5. Kas Non-Tunai (Bank) -->
    <div class="km-card km-purple">
        <div class="km-accent km-accent-purple"></div>
        <div class="km-top">
            <div>
                <span class="km-eyebrow">NON-TUNAI</span>
                <span class="km-lbl">Kas Digital &amp; Bank</span>
            </div>
            <div class="km-ico"><i class="fa-solid fa-building-columns"></i></div>
        </div>
        <div class="km-val">
            <span class="km-rp">Rp</span>
            <span class="km-num km-sm"><?= number_format($digital_masuk - $digital_keluar, 0, ',', '.') ?></span>
        </div>
        <div class="km-foot">
            <span class="kbadge kbadge-purple">
                <i class="fa-solid fa-qrcode"></i> Transfer &bull; QRIS &bull; Debit
            </span>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     CONTROL TOOLBAR: SEARCH + QUICK FILTER + DATE RANGE
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="filter-card">
    <form method="GET" action="kas.php" class="filter-form" id="filterForm" onsubmit="return false;">

        <!-- Baris Pertama: Search Input di Sisi Kiri & Filter Periode Cepat di Sisi Kanan -->
        <div class="filter-main-row">
            <!-- Search Box -->
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass sb-icon"></i>
                <input
                    type="text"
                    name="q"
                    class="sb-input"
                    placeholder="Cari transaksi, kategori, keterangan, atau kasir..."
                    value="<?= e($q_cari) ?>"
                    autocomplete="off"
                    id="searchInput">
                <span id="kasClearArea">
                    <?php if ($q_cari): ?>
                        <button type="button" class="sb-clear" id="searchClearBtnKas" title="Hapus pencarian">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    <?php endif; ?>
                </span>
            </div>

            <!-- Filter Periode (Quick Buttons) -->
            <div class="periode-pills-wrap">
                <span class="filter-section-lbl"><i class="fa-regular fa-calendar"></i> Periode:</span>
                <div class="periode-btns" id="kasPeriodeBtns">
                    <a href="kas.php" class="prd-btn <?= $f_periode === '' && $f_dari === '' ? 'active' : '' ?>" data-periode="">
                        Semua
                    </a>
                    <a href="kas.php?periode=today" class="prd-btn <?= $f_periode === 'today' ? 'active' : '' ?>" data-periode="today">
                        Hari Ini
                    </a>
                    <a href="kas.php?periode=bulan_ini" class="prd-btn <?= $f_periode === 'bulan_ini' ? 'active' : '' ?>" data-periode="bulan_ini">
                        Bulan Ini
                    </a>
                    <a href="kas.php?periode=bulan_lalu" class="prd-btn <?= $f_periode === 'bulan_lalu' ? 'active' : '' ?>" data-periode="bulan_lalu">
                        Bulan Lalu
                    </a>
                </div>
            </div>
        </div>

        <!-- Baris Kedua: Dropdown Jenis & Metode + Date Range Picker & Tombol Aksi -->
        <div class="filter-sub-row">
            <!-- Sisi Kiri: Dropdown Pilihan -->
            <div class="filter-left-group">
                <div class="custom-select-wrap">
                    <i class="fa-solid fa-arrows-up-down sel-icon"></i>
                    <select name="jenis" id="filterJenis" class="flt-sel">
                        <option value="">Semua Jenis (Masuk &amp; Keluar)</option>
                        <option value="Pemasukan"   <?= $f_jenis === 'Pemasukan'   ? 'selected' : '' ?>>💹 Pemasukan (Kas Masuk)</option>
                        <option value="Pengeluaran" <?= $f_jenis === 'Pengeluaran' ? 'selected' : '' ?>>📉 Pengeluaran (Kas Keluar)</option>
                    </select>
                </div>

                <div class="custom-select-wrap">
                    <i class="fa-solid fa-credit-card sel-icon"></i>
                    <select name="metode" id="filterMetode" class="flt-sel">
                        <option value="">Semua Metode Pembayaran</option>
                        <?php foreach ($METODE_LIST as $m): ?>
                            <option value="<?= $m ?>" <?= $f_metode === $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Sisi Kanan: Date Range Picker & Tombol Terapkan/Reset -->
            <div class="filter-right-group">
                <div class="date-range-box">
                    <span class="dr-lbl"><i class="fa-regular fa-calendar-days"></i> Rentang:</span>
                    <input type="date" name="dari" id="filterDari" class="dr-input" value="<?= e($f_dari) ?>" title="Dari tanggal">
                    <span class="dr-sep">s/d</span>
                    <input type="date" name="sampai" id="filterSampai" class="dr-input" value="<?= e($f_sampai) ?>" title="Sampai tanggal">
                    <button type="button" class="btn-filter-apply" id="btnApplyDateKas" title="Terapkan filter tanggal">
                        <i class="fa-solid fa-filter"></i>
                        <span>Terapkan</span>
                    </button>
                </div>

                <div id="kasResetArea">
                    <?php if ($q_cari || $f_jenis || $f_metode || $f_dari || $f_sampai || $f_periode): ?>
                        <a href="kas.php" class="btn-filter-reset" id="btnResetKas" title="Kembalikan semua filter ke awal">
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
     TABEL RIWAYAT TRANSAKSI
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="panel panel-kas" id="printArea">

    <!-- Print-only header -->
    <div class="print-header">
        <h2><?= e(NAMA_INSTANSI) ?> &mdash; Laporan Buku Kas</h2>
        <p>Dicetak pada: <?= tanggal_panjang_id(date('Y-m-d')) ?> &middot; Operator: <?= e(nama_user()) ?></p>
    </div>

    <div class="panel-head">
        <div class="panel-head-left">
            <div class="panel-eyebrow">
                <i class="fa-solid fa-receipt"></i> DATA BUKU KAS
            </div>
            <h3 class="panel-title">
                Daftar Mutasi Transaksi
                <span id="kasBadgeWrap">
                    <?= render_kas_badge(count($rows), ($q_cari || $f_jenis || $f_metode || $f_dari || $f_sampai || $f_periode)) ?>
                </span>
            </h3>
        </div>
        <!-- Ringkasan hasil filter -->
        <div class="filter-summary" id="kasFilterSummary">
            <?= render_kas_summary($rows) ?>
        </div>
    </div>

    <div class="tbl-scroll">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:44px;text-align:center">#</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Metode</th>
                    <th class="text-right">Nominal</th>
                    <th>Dicatat Oleh</th>
                    <th style="width:56px;text-align:center">Bukti</th>
                    <th style="width:96px;text-align:center" class="no-print">Aksi</th>
                </tr>
            </thead>
            <tbody id="kasTbody">
                <?= render_kas_rows($rows, $q_cari, $f_jenis, $f_metode, $f_dari, $f_sampai) ?>
            </tbody>
        </table>
    </div>

</div><!-- /.panel -->

<?php if (is_admin()): ?>
<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL: TAMBAH TRANSAKSI BARU
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal" id="modalTambah">
    <div class="modal-card">
        <div class="modal-hd">
            <div class="modal-hd-icon">
                <i class="fa-solid fa-plus"></i>
            </div>
            <div>
                <h3 class="modal-hd-title">Tambah Transaksi Kas</h3>
                <p class="modal-hd-sub">
                    Isi semua kolom wajib (<span style="color:var(--red)">*</span>) dengan benar.
                </p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalTambah')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="kas.php" enctype="multipart/form-data" id="formTambah">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="tambah">

            <div class="modal-body">
                <!-- Alert Notifikasi Validasi Modal Tambah -->
                <div class="fintoko-modal-alert" id="kasTambahAlert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Terjadi Kesalahan Pengisian:</strong>
                        <ul id="kasTambahAlertList"></ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="t-tanggal">
                            <i class="fa-regular fa-calendar"></i> Tanggal <span class="req">*</span>
                        </label>
                        <input type="date" id="t-tanggal" name="tanggal" class="form-input"
                               value="<?= date('Y-m-d') ?>" required>
                        <span class="form-error-feedback" id="err_t_tanggal">
                            <i class="fa-solid fa-circle-exclamation"></i> Tanggal transaksi wajib diisi.
                        </span>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="t-jenis">
                            <i class="fa-solid fa-arrows-up-down"></i> Jenis <span class="req">*</span>
                        </label>
                        <select id="t-jenis" name="jenis" class="form-input" required>
                            <option value="">— Pilih Jenis —</option>
                            <option value="Pemasukan">💹 Pemasukan (Kas Masuk)</option>
                            <option value="Pengeluaran">📉 Pengeluaran (Kas Keluar)</option>
                        </select>
                        <span class="form-error-feedback" id="err_t_jenis">
                            <i class="fa-solid fa-circle-exclamation"></i> Silakan pilih jenis transaksi.
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="t-kategori">
                            <i class="fa-solid fa-tag"></i> Kategori <span class="req">*</span>
                        </label>
                        <input type="text" id="t-kategori" name="kategori" class="form-input"
                               placeholder="Penjualan, Listrik, Gaji..." maxlength="100" required autocomplete="off">
                        <span class="form-error-feedback" id="err_t_kategori">
                            <i class="fa-solid fa-circle-exclamation"></i> Kategori wajib diisi (maks. 100 karakter).
                        </span>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="t-nominal">
                            <i class="fa-solid fa-coins"></i> Nominal (Rp) <span class="req">*</span>
                        </label>
                        <div class="input-rp-wrap">
                            <span class="input-rp-prefix">Rp</span>
                            <input type="text" id="t-nominal"
                                   class="form-input has-rp-prefix rupiah-input"
                                   placeholder="0" inputmode="numeric" autocomplete="off" required>
                        </div>
                        <span class="form-error-feedback" id="err_t_nominal">
                            <i class="fa-solid fa-circle-exclamation"></i> Nominal harus lebih besar dari Rp 0.
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="t-metode">
                            <i class="fa-solid fa-credit-card"></i> Metode Pembayaran <span class="req">*</span>
                        </label>
                        <select id="t-metode" name="metode" class="form-input">
                            <?php foreach ($METODE_LIST as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="t-keterangan">
                            <i class="fa-regular fa-note-sticky"></i> Keterangan
                            <span class="form-opt">(opsional)</span>
                        </label>
                        <input type="text" id="t-keterangan" name="keterangan" class="form-input"
                               placeholder="Deskripsi singkat transaksi..." maxlength="255">
                        <span class="form-error-feedback" id="err_t_keterangan">
                            <i class="fa-solid fa-circle-exclamation"></i> Keterangan maksimal 255 karakter.
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-col-full">
                        <label class="form-label" for="t-bukti">
                            <i class="fa-solid fa-paperclip"></i> Bukti Transaksi
                            <span class="form-opt">(foto struk / PDF, maks <?= MAX_FILE_MB ?>MB)</span>
                        </label>
                        <div class="file-drop-zone" id="dropZoneTambah">
                            <input type="file" id="t-bukti" name="bukti_file"
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                                   class="file-input-hidden"
                                   onchange="previewFile(this, 'previewTambah')">
                            <div class="fdz-inner" onclick="document.getElementById('t-bukti').click()">
                                <i class="fa-solid fa-cloud-arrow-up fdz-ico"></i>
                                <p class="fdz-txt">Klik untuk pilih file atau drag &amp; drop di sini</p>
                                <span class="fdz-sub">JPG, PNG, PDF hingga <?= MAX_FILE_MB ?>MB</span>
                            </div>
                            <div id="previewTambah" class="file-preview hidden"></div>
                        </div>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <div class="modal-ft">
                <button type="button" class="btn-ghost" onclick="closeModal('modalTambah')">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
                <button type="submit" class="btn-primary-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL: EDIT TRANSAKSI
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal" id="modalEdit">
    <div class="modal-card">
        <div class="modal-hd">
            <div class="modal-hd-icon edit-icon">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <h3 class="modal-hd-title">Edit Transaksi</h3>
                <p class="modal-hd-sub">Perbaiki data transaksi yang salah tanpa perlu menghapus.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalEdit')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="kas.php" enctype="multipart/form-data" id="formEdit">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="edit">
            <input type="hidden" name="edit_id" id="e-id">

            <div class="modal-body">
                <!-- Alert Notifikasi Validasi Modal Edit -->
                <div class="fintoko-modal-alert" id="kasEditAlert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Terjadi Kesalahan Pengisian:</strong>
                        <ul id="kasEditAlertList"></ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="e-tanggal">
                            <i class="fa-regular fa-calendar"></i> Tanggal <span class="req">*</span>
                        </label>
                        <input type="date" id="e-tanggal" name="tanggal" class="form-input" required>
                        <span class="form-error-feedback" id="err_e_tanggal">
                            <i class="fa-solid fa-circle-exclamation"></i> Tanggal transaksi wajib diisi.
                        </span>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="e-jenis">
                            <i class="fa-solid fa-arrows-up-down"></i> Jenis <span class="req">*</span>
                        </label>
                        <select id="e-jenis" name="jenis" class="form-input" required>
                            <option value="Pemasukan">💹 Pemasukan</option>
                            <option value="Pengeluaran">📉 Pengeluaran</option>
                        </select>
                        <span class="form-error-feedback" id="err_e_jenis">
                            <i class="fa-solid fa-circle-exclamation"></i> Silakan pilih jenis transaksi.
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="e-kategori">
                            <i class="fa-solid fa-tag"></i> Kategori <span class="req">*</span>
                        </label>
                        <input type="text" id="e-kategori" name="kategori" class="form-input"
                               maxlength="100" required autocomplete="off">
                        <span class="form-error-feedback" id="err_e_kategori">
                            <i class="fa-solid fa-circle-exclamation"></i> Kategori wajib diisi (maks. 100 karakter).
                        </span>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="e-nominal">
                            <i class="fa-solid fa-coins"></i> Nominal (Rp) <span class="req">*</span>
                        </label>
                        <div class="input-rp-wrap">
                            <span class="input-rp-prefix">Rp</span>
                            <input type="text" id="e-nominal"
                                   class="form-input has-rp-prefix rupiah-input"
                                   inputmode="numeric" autocomplete="off" required>
                        </div>
                        <span class="form-error-feedback" id="err_e_nominal">
                            <i class="fa-solid fa-circle-exclamation"></i> Nominal harus lebih besar dari Rp 0.
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label" for="e-metode">
                            <i class="fa-solid fa-credit-card"></i> Metode Pembayaran
                        </label>
                        <select id="e-metode" name="metode" class="form-input">
                            <?php foreach ($METODE_LIST as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label" for="e-keterangan">
                            <i class="fa-regular fa-note-sticky"></i> Keterangan
                        </label>
                        <input type="text" id="e-keterangan" name="keterangan"
                               class="form-input" maxlength="255">
                        <span class="form-error-feedback" id="err_e_keterangan">
                            <i class="fa-solid fa-circle-exclamation"></i> Keterangan maksimal 255 karakter.
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-col-full">
                        <label class="form-label" for="e-bukti">
                            <i class="fa-solid fa-paperclip"></i> Ganti Bukti Transaksi
                            <span class="form-opt">(kosongkan jika tidak ingin mengubah)</span>
                        </label>
                        <div class="edit-bukti-wrap" id="currentBuktiWrap">
                            <div id="currentBuktiInfo" class="current-bukti-info"></div>
                        </div>
                        <input type="file" id="e-bukti" name="bukti_file"
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                               style="margin-top:8px;width:100%;"
                               onchange="previewFile(this, 'previewEdit')">
                        <div id="previewEdit" class="file-preview hidden"></div>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <div class="modal-ft">
                <button type="button" class="btn-ghost" onclick="closeModal('modalEdit')">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
                <button type="submit" class="btn-warning-lg">
                    <i class="fa-solid fa-pen-to-square"></i> Perbarui Data
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; /* is_admin */ ?>

<?php
/* ═══════════════════════════════════════════════════════════════════════════
   JAVASCRIPT HALAMAN KAS: REAL-TIME AJAX FILTER & LIVE SEARCH
   ═══════════════════════════════════════════════════════════════════════════ */
$extra_scripts = '
<script>
/* ═══════════════════════════════════════════════════════════════════════════
   REAL-TIME AJAX LIVE SEARCH & FILTERING (ZERO PAGE RELOAD)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var searchInput   = document.getElementById("searchInput");
    var tbodyEl       = document.getElementById("kasTbody");
    var badgeWrap     = document.getElementById("kasBadgeWrap");
    var summaryWrap   = document.getElementById("kasFilterSummary");
    var periodeBtns   = document.getElementById("kasPeriodeBtns");
    var selJenis      = document.getElementById("filterJenis");
    var selMetode     = document.getElementById("filterMetode");
    var inputDari     = document.getElementById("filterDari");
    var inputSampai   = document.getElementById("filterSampai");
    var btnApplyDate  = document.getElementById("btnApplyDateKas");
    var resetArea     = document.getElementById("kasResetArea");
    var clearArea     = document.getElementById("kasClearArea");

    var currentQuery   = searchInput ? searchInput.value.trim() : "";
    var currentJenis   = selJenis ? selJenis.value : "";
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

    function updateResetUI(q, jenis, metode, dari, sampai, periode) {
        var hasFilter = (q !== "" || jenis !== "" || metode !== "" || dari !== "" || sampai !== "" || periode !== "");

        if (resetArea) {
            if (hasFilter) {
                if (!document.getElementById("btnResetKas")) {
                    resetArea.innerHTML = \'<a href="kas.php" class="btn-filter-reset" id="btnResetKas" title="Kembalikan semua filter ke awal">\' +
                        \'<i class="fa-solid fa-rotate-left"></i> <span>Reset Filter</span></a>\';
                }
            } else {
                resetArea.innerHTML = "";
            }
        }

        if (clearArea) {
            if (q !== "") {
                clearArea.innerHTML = \'<button type="button" class="sb-clear" id="searchClearBtnKas" title="Hapus pencarian">\' +
                    \'<i class="fa-solid fa-xmark"></i></button>\';
            } else {
                clearArea.innerHTML = "";
            }
        }
    }

    function executeAjaxFilterKas(q, jenis, metode, dari, sampai, periode, pushHistory) {
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = new AbortController();

        showProgress();

        var params = new URLSearchParams();
        params.append("ajax", "1");
        if (q) params.append("q", q);
        if (jenis) params.append("jenis", jenis);
        if (metode) params.append("metode", metode);
        if (periode) params.append("periode", periode);
        if (dari) params.append("dari", dari);
        if (sampai) params.append("sampai", sampai);

        var fetchUrl = "kas.php?" + params.toString();

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
                if (tbodyEl) {
                    tbodyEl.innerHTML = data.html_tbody;
                    tbodyEl.classList.add("ajax-fade-in");
                    setTimeout(function() {
                        tbodyEl.classList.remove("ajax-fade-in");
                    }, 300);
                }

                if (badgeWrap) {
                    badgeWrap.innerHTML = data.html_badge;
                }

                if (summaryWrap) {
                    summaryWrap.innerHTML = data.html_summary;
                }

                updatePillsUI(data.periode);
                updateResetUI(data.q, data.jenis, data.metode, data.dari, data.sampai, data.periode);

                if (pushHistory !== false && window.history && window.history.replaceState) {
                    var browserParams = new URLSearchParams();
                    if (data.q) browserParams.append("q", data.q);
                    if (data.jenis) browserParams.append("jenis", data.jenis);
                    if (data.metode) browserParams.append("metode", data.metode);
                    if (data.periode) browserParams.append("periode", data.periode);
                    if (data.dari) browserParams.append("dari", data.dari);
                    if (data.sampai) browserParams.append("sampai", data.sampai);
                    var cleanUrl = "kas.php" + (browserParams.toString() ? "?" + browserParams.toString() : "");
                    window.history.replaceState({ q: data.q, jenis: data.jenis, metode: data.metode, dari: data.dari, sampai: data.sampai, periode: data.periode }, "", cleanUrl);
                }
            }
        })
        .catch(function(err) {
            if (err.name !== "AbortError") {
                console.error("[Kas AJAX Filter Error]", err);
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
                executeAjaxFilterKas(currentQuery, currentJenis, currentMetode, currentDari, currentSampai, currentPeriode);
            }, 250);
        });

        searchInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(searchDebounce);
                currentQuery = searchInput.value.trim();
                executeAjaxFilterKas(currentQuery, currentJenis, currentMetode, currentDari, currentSampai, currentPeriode);
            }
        });
    }

    // 2. Dropdown Filter Jenis & Metode
    if (selJenis) {
        selJenis.addEventListener("change", function() {
            currentJenis = selJenis.value;
            executeAjaxFilterKas(currentQuery, currentJenis, currentMetode, currentDari, currentSampai, currentPeriode);
        });
    }

    if (selMetode) {
        selMetode.addEventListener("change", function() {
            currentMetode = selMetode.value;
            executeAjaxFilterKas(currentQuery, currentJenis, currentMetode, currentDari, currentSampai, currentPeriode);
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

            executeAjaxFilterKas(currentQuery, currentJenis, currentMetode, "", "", currentPeriode);
        });
    }

    // 4. Tombol Terapkan Rentang Tanggal
    if (btnApplyDate) {
        btnApplyDate.addEventListener("click", function(e) {
            e.preventDefault();
            currentDari    = inputDari ? inputDari.value : "";
            currentSampai  = inputSampai ? inputSampai.value : "";
            currentPeriode = "";
            executeAjaxFilterKas(currentQuery, currentJenis, currentMetode, currentDari, currentSampai, "");
        });
    }

    // 5. Tombol Clear Search & Reset Filter
    document.addEventListener("click", function(e) {
        var clearBtn = e.target.closest("#searchClearBtnKas, .sb-clear");
        if (clearBtn) {
            e.preventDefault();
            if (searchInput) {
                searchInput.value = "";
                searchInput.focus();
            }
            currentQuery = "";
            executeAjaxFilterKas("", currentJenis, currentMetode, currentDari, currentSampai, currentPeriode);
            return;
        }

        var rBtn = e.target.closest("#btnResetKas, .btn-filter-reset");
        if (rBtn) {
            e.preventDefault();
            if (searchInput) searchInput.value = "";
            if (selJenis) selJenis.value = "";
            if (selMetode) selMetode.value = "";
            if (inputDari) inputDari.value = "";
            if (inputSampai) inputSampai.value = "";
            currentQuery   = "";
            currentJenis   = "";
            currentMetode  = "";
            currentPeriode = "";
            currentDari    = "";
            currentSampai  = "";
            executeAjaxFilterKas("", "", "", "", "", "");
            return;
        }
    });

    // 6. Dukungan Back/Forward Browser
    window.addEventListener("popstate", function() {
        var params = new URLSearchParams(window.location.search);
        currentQuery   = params.get("q") || "";
        currentJenis   = params.get("jenis") || "";
        currentMetode  = params.get("metode") || "";
        currentPeriode = params.get("periode") || "";
        currentDari    = params.get("dari") || "";
        currentSampai  = params.get("sampai") || "";

        if (searchInput) searchInput.value = currentQuery;
        if (selJenis) selJenis.value = currentJenis;
        if (selMetode) selMetode.value = currentMetode;
        if (inputDari) inputDari.value = currentDari;
        if (inputSampai) inputSampai.value = currentSampai;

        executeAjaxFilterKas(currentQuery, currentJenis, currentMetode, currentDari, currentSampai, currentPeriode, false);
    });

    // Helper Global untuk reset saat tabel kosong
    window.resetKasFilter = function(e) {
        if (e) e.preventDefault();
        if (searchInput) searchInput.value = "";
        if (selJenis) selJenis.value = "";
        if (selMetode) selMetode.value = "";
        if (inputDari) inputDari.value = "";
        if (inputSampai) inputSampai.value = "";
        currentQuery   = "";
        currentJenis   = "";
        currentMetode  = "";
        currentPeriode = "";
        currentDari    = "";
        currentSampai  = "";
        executeAjaxFilterKas("", "", "", "", "", "");
    };

    // Helper Global untuk reload tabel kas secara asinkron
    window.reloadKasTable = function() {
        executeAjaxFilterKas(currentQuery, currentJenis, currentMetode, currentDari, currentSampai, currentPeriode, false);
    };
})();

/* ═══════════════════════════════════════════════════════════════════════════
   CUSTOM POPUP KONFIRMASI HAPUS KAS (AJAX REAL-TIME)
   ═══════════════════════════════════════════════════════════════════════════ */
function confirmHapusKas(e, id, csrf, info) {
    if (e) e.preventDefault();
    window.fintokoDeleteConfirm({
        title: "Hapus Transaksi Kas?",
        text: "Apakah Anda yakin ingin menghapus catatan transaksi kas ini? Bukti fisik (jika ada) dan data buku kas akan dihapus secara permanen.",
        itemName: "📒 " + info,
        onConfirm: function() {
            fetch("kas.php?hapus=" + id + "&csrf=" + encodeURIComponent(csrf), {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.status === "success") {
                    window.fintokoToast(data.message || "Transaksi kas berhasil dihapus.", "success");
                    if (typeof window.reloadKasTable === "function") {
                        window.reloadKasTable();
                    }
                } else {
                    window.fintokoToast(data.message || "Gagal menghapus transaksi.", "error");
                }
            })
            .catch(function() {
                window.location.href = "kas.php?deleted=1";
            });
        }
    });
}

/* ═══════════════════════════════════════════════════════════════════════════
   EDIT TRANSAKSI — ambil data via AJAX, isi modal
   ═══════════════════════════════════════════════════════════════════════════ */
function openEdit(id) {
    var btn = event ? event.currentTarget : null;
    if (btn) {
        btn.style.opacity = "0.5";
        btn.style.pointerEvents = "none";
    }

    fetch("kas.php?get_row=" + id)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (btn) { btn.style.opacity = ""; btn.style.pointerEvents = ""; }
            if (!d || !d.id) { alert("Data tidak ditemukan."); return; }

            document.getElementById("e-id").value        = d.id;
            document.getElementById("e-tanggal").value   = d.tanggal;
            document.getElementById("e-jenis").value     = d.jenis;
            document.getElementById("e-kategori").value  = d.kategori;

            var nominalInput = document.getElementById("e-nominal");
            var rawNominal   = parseInt(d.nominal, 10) || 0;
            if (nominalInput) {
                nominalInput.value = rawNominal > 0 ? RupiahInput.fmt(rawNominal) : "";
                var hiddenNominal = nominalInput.nextElementSibling;
                if (hiddenNominal && hiddenNominal.type === "hidden") {
                    hiddenNominal.value = rawNominal > 0 ? rawNominal : "";
                }
            }

            document.getElementById("e-metode").value    = d.metode || "Tunai";
            document.getElementById("e-keterangan").value = d.keterangan || "";

            var info = document.getElementById("currentBuktiInfo");
            if (d.bukti_file) {
                info.innerHTML = "📎 Bukti saat ini: <strong>" + d.bukti_file + "</strong> — upload file baru untuk mengganti.";
                info.style.display = "flex";
            } else {
                info.innerHTML = "📭 Belum ada bukti terlampir.";
                info.style.display = "flex";
            }

            document.getElementById("previewEdit").classList.add("hidden");
            document.getElementById("previewEdit").innerHTML = "";

            var fileInput = document.getElementById("e-bukti");
            if (fileInput) fileInput.value = "";

            openModal("modalEdit");
        })
        .catch(function() {
            if (btn) { btn.style.opacity = ""; btn.style.pointerEvents = ""; }
            alert("Gagal memuat data transaksi. Periksa koneksi jaringan.");
        });
}

/* ═══════════════════════════════════════════════════════════════════════════
   PREVIEW FILE SEBELUM UPLOAD
   ═══════════════════════════════════════════════════════════════════════════ */
function previewFile(input, previewId) {
    var container = document.getElementById(previewId);
    container.innerHTML = "";
    container.classList.add("hidden");

    if (!input.files || !input.files[0]) return;

    var file   = input.files[0];
    var sizeMB = (file.size / 1024 / 1024).toFixed(2);
    var maxMB  = ' . MAX_FILE_MB . ';

    if (file.size > maxMB * 1024 * 1024) {
        alert("Ukuran file terlalu besar (maks " + maxMB + "MB).\\nFile Anda: " + sizeMB + "MB");
        input.value = "";
        return;
    }

    if (file.type.startsWith("image/")) {
        var reader = new FileReader();
        reader.onload = function(e) {
            container.innerHTML =
                "<img src=\'" + e.target.result + "\' class=\'fp-img\' alt=\'Preview\'>" +
                "<p class=\'fp-name\'>📷 " + file.name + " (" + sizeMB + " MB)</p>";
            container.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
    } else {
        container.innerHTML = "<p class=\'fp-name\'>📄 " + file.name + " (" + sizeMB + " MB)</p>";
        container.classList.remove("hidden");
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   DRAG & DROP UNTUK FILE UPLOAD ZONE
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var dz = document.getElementById("dropZoneTambah");
    if (!dz) return;

    ["dragenter", "dragover"].forEach(function(eventName) {
        dz.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dz.classList.add("drag-over");
        });
    });

    ["dragleave", "drop"].forEach(function(eventName) {
        dz.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dz.classList.remove("drag-over");
        });
    });

    dz.addEventListener("drop", function(e) {
        var inp = document.getElementById("t-bukti");
        if (e.dataTransfer.files.length) {
            var dt = new DataTransfer();
            dt.items.add(e.dataTransfer.files[0]);
            inp.files = dt.files;
            previewFile(inp, "previewTambah");
        }
    });
})();

/* ═══════════════════════════════════════════════════════════════════════════
   VALIDASI FORM INPUT & ALERT FEEDBACK (CLIENT-SIDE)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    function setupKasFormValidation(formId, prefix, alertBoxId, alertListId) {
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
            ["tanggal", "jenis", "kategori", "nominal", "keterangan"].forEach(clearError);
        }

        ["tanggal", "jenis", "kategori", "nominal", "keterangan"].forEach(function(key) {
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
            var jnsInput = document.getElementById(prefix + "-jenis");
            var katInput = document.getElementById(prefix + "-kategori");
            var nomInput = document.getElementById(prefix + "-nominal");
            var ketInput = document.getElementById(prefix + "-keterangan");

            var tgl = tglInput ? tglInput.value.trim() : "";
            var jns = jnsInput ? jnsInput.value.trim() : "";
            var kat = katInput ? katInput.value.trim() : "";
            var ket = ketInput ? ketInput.value.trim() : "";

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

            // 2. Validasi Jenis
            if (!jns) {
                errors.push("Jenis transaksi wajib dipilih (Pemasukan / Pengeluaran).");
                setError("jenis", "Silakan pilih jenis transaksi.");
                if (!firstInvalid) firstInvalid = jnsInput;
            }

            // 3. Validasi Kategori
            if (!kat) {
                errors.push("Kategori transaksi wajib diisi.");
                setError("kategori", "Kategori transaksi wajib diisi.");
                if (!firstInvalid) firstInvalid = katInput;
            } else if (kat.length > 100) {
                errors.push("Kategori maksimal 100 karakter.");
                setError("kategori", "Maksimal 100 karakter.");
                if (!firstInvalid) firstInvalid = katInput;
            }

            // 4. Validasi Nominal
            if (isNaN(nomVal) || nomVal <= 0) {
                errors.push("Nominal transaksi harus lebih besar dari Rp 0.");
                setError("nominal", "Nominal harus lebih besar dari Rp 0.");
                if (!firstInvalid) firstInvalid = nomInput;
            }

            // 5. Validasi Keterangan
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

    setupKasFormValidation("formTambah", "t", "kasTambahAlert", "kasTambahAlertList");
    setupKasFormValidation("formEdit", "e", "kasEditAlert", "kasEditAlertList");
})();
</script>
<script src="<?= BASE_URL ?>assets/js/rupiah.js"></script>';

include __DIR__ . '/../includes/footer.php';

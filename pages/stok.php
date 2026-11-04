<?php
/**
 * =====================================================================
 * FINTOKO — Sistem Informasi Keuangan & Inventaris
 * =====================================================================
 * File    : pages/stok.php   (v7.0 — Real-Time AJAX Filtering & Search)
 * Halaman : Manajemen Stok Barang / Inventory
 * Fitur   : 1. Live Search & Filter Kategori berbasis AJAX (Zero Page Reload)
 *           2. Endpoint Asinkron JSON dengan partial HTML render
 *           3. Debounce 250ms & AbortController (Bebas Race Condition)
 *           4. Sinkronisasi History URL Browser (history.replaceState)
 *           5. 4 Kartu Metrik Glassmorphism — Icy Pastel Blue
 *           6. Modal Tambah/Edit Produk (Responsive, Anti-Cutoff Sticky Footer)
 *           7. Standard Input-Group Addon "Rp" (Bebas Tabrakan)
 *           8. Export CSV Inventory & Manajemen Stok Real-time
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');

/* ═══════════════════════════════════════════════════════════════════════════
   EKSPOR EXCEL (CSV UTF-8) — Inventory
   ═══════════════════════════════════════════════════════════════════════════ */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_admin('../');

    $rows_exp = db()->query("
        SELECT kode_produk, nama_produk, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum
        FROM produk ORDER BY id DESC
    ")->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="stok_barang_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8
    fputcsv($out, ['Kode', 'Nama Produk', 'Kategori', 'Satuan', 'Harga Beli', 'Harga Jual', 'Stok', 'Stok Minimum'], ';');

    foreach ($rows_exp as $r) {
        fputcsv($out, [
            $r['kode_produk'],
            $r['nama_produk'],
            $r['kategori'],
            $r['satuan'],
            number_format((float)$r['harga_beli'], 0, ',', '.'),
            number_format((float)$r['harga_jual'], 0, ',', '.'),
            $r['stok'],
            $r['stok_minimum'],
        ], ';');
    }
    fclose($out);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   POST: HAPUS PRODUK
   ═══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    require_admin('../');

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Token keamanan tidak valid. Silakan muat ulang halaman.']);
            exit;
        }
        redirect('stok.php?error=csrf');
    }
    $id = (int) $_POST['hapus'];
    if ($id > 0) {
        $stmt = db()->prepare('DELETE FROM produk WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Produk berhasil dihapus dari inventori.']);
            exit;
        }
        set_flash('success', 'Produk berhasil dihapus dari inventori.');
    }
    redirect('stok.php?deleted=1');
}

/* ═══════════════════════════════════════════════════════════════════════════
   POST: TAMBAH / EDIT PRODUK
   ═══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['hapus'])) {
    require_admin('../');

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        redirect('stok.php?error=csrf');
    }

    $kode     = strtoupper(trim($_POST['kode_produk'] ?? ''));
    $nama     = trim($_POST['nama_produk'] ?? '');
    $kategori = trim($_POST['kategori']    ?? 'Umum');
    $satuan   = trim($_POST['satuan']      ?? 'Pcs');
    $hbeli    = (float) ($_POST['harga_beli']  ?? 0);
    $hjual    = (float) ($_POST['harga_jual']  ?? 0);
    $stok     = (int)   ($_POST['stok']        ?? 0);
    $stok_min = (int)   ($_POST['stok_minimum'] ?? 5);
    $id       = (int)   ($_POST['id']          ?? 0);

    // Validasi String & Tipe Data Ketat di Sisi Server (PHP)
    $errors = [];

    if ($kode === '') {
        $errors[] = 'Kode produk wajib diisi.';
    } elseif (!preg_match('/^[A-Za-z0-9\-\.\_]{2,50}$/', $kode)) {
        $errors[] = 'Format kode produk tidak valid. Gunakan 2-50 karakter alfanumerik (huruf, angka, strip, titik).';
    }

    if ($nama === '') {
        $errors[] = 'Nama produk wajib diisi.';
    } elseif (mb_strlen($nama) < 3) {
        $errors[] = 'Nama produk terlalu pendek (minimal 3 karakter).';
    } elseif (mb_strlen($nama) > 150) {
        $errors[] = 'Nama produk terlalu panjang (maksimal 150 karakter).';
    }

    if ($hbeli < 0) {
        $errors[] = 'Harga beli tidak boleh bernilai negatif.';
    }
    if ($hjual < 0) {
        $errors[] = 'Harga jual tidak boleh bernilai negatif.';
    }
    if ($stok < 0) {
        $errors[] = 'Jumlah stok awal tidak boleh bernilai negatif.';
    }
    if ($stok_min < 0) {
        $errors[] = 'Batas stok minimum tidak boleh bernilai negatif.';
    }

    // Pengecekan Keunikan Kode Produk
    if (empty($errors)) {
        $stmt_check = db()->prepare('SELECT id FROM produk WHERE kode_produk = ? AND id != ? LIMIT 1');
        $stmt_check->bind_param('si', $kode, $id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->fetch_assoc()) {
            $errors[] = "Kode produk <strong>{$kode}</strong> sudah terdaftar pada produk lain. Harap gunakan kode unik.";
        }
        $stmt_check->close();
    }

    if (!empty($errors)) {
        set_flash('error', implode('<br>', $errors));
        redirect('stok.php?error=validation');
    }

    try {
        if ($id > 0) {
            $stmt = db()->prepare('UPDATE produk SET kode_produk=?, nama_produk=?, kategori=?, satuan=?, harga_beli=?, harga_jual=?, stok=?, stok_minimum=? WHERE id=?');
            $stmt->bind_param('ssssddiii', $kode, $nama, $kategori, $satuan, $hbeli, $hjual, $stok, $stok_min, $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', "Produk <strong>{$nama}</strong> berhasil diperbarui.");
            redirect('stok.php?updated=1');
        } else {
            $stmt = db()->prepare('INSERT INTO produk (kode_produk, nama_produk, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssddii', $kode, $nama, $kategori, $satuan, $hbeli, $hjual, $stok, $stok_min);
            $stmt->execute();
            $stmt->close();
            set_flash('success', "Produk <strong>{$nama}</strong> berhasil ditambahkan.");
            redirect('stok.php?success=1');
        }
    } catch (mysqli_sql_exception $e) {
        set_flash('error', 'Gagal menyimpan data produk ke basis data. Pastikan semua kolom terisi dengan benar.');
        redirect('stok.php?error=1');
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   HELPER RENDER PARSIAL (Digunakan SSR dan Endpoint AJAX)
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Render baris-baris <tbody> tabel daftar produk
 */
function render_stok_rows(array $produk, string $q_cari = '', string $f_kategori = ''): string
{
    ob_start();
    if ($produk):
        $no = 1;
        foreach ($produk as $p):
        ?>
        <tr>
            <!-- # -->
            <td class="stk-table__num"><?= $no++ ?></td>

            <!-- Kode Produk -->
            <td>
                <span class="stk-kode"><?= e($p['kode_produk']) ?></span>
            </td>

            <!-- Nama Produk -->
            <td class="stk-nama"><?= e($p['nama_produk']) ?></td>

            <!-- Kategori -->
            <td>
                <span class="stk-chip"><?= e($p['kategori']) ?></span>
            </td>

            <!-- Stok -->
            <td>
                <?php if ((int)$p['stok'] <= (int)$p['stok_minimum']): ?>
                    <span class="stk-stok stk-stok--kritis">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?= (int)$p['stok'] ?> <?= e($p['satuan']) ?>
                    </span>
                <?php else: ?>
                    <span class="stk-stok stk-stok--aman">
                        <i class="fa-solid fa-check-circle"></i>
                        <?= (int)$p['stok'] ?> <?= e($p['satuan']) ?>
                    </span>
                <?php endif; ?>
            </td>

            <!-- Harga Beli -->
            <td class="stk-table__col-right">
                <span class="stk-harga"><?= rupiah($p['harga_beli']) ?></span>
            </td>

            <!-- Harga Jual -->
            <td class="stk-table__col-right">
                <span class="stk-harga stk-harga--jual"><?= rupiah($p['harga_jual']) ?></span>
            </td>

            <!-- Aksi: Edit + Hapus (Khusus Administrator) -->
            <td>
                <?php if (is_admin()): ?>
                <div class="stk-actions">
                    <button type="button"
                        class="stk-actions__edit"
                        title="Edit produk"
                        onclick='editProduk(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8") ?>)'>
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button type="button"
                        class="stk-actions__delete"
                        title="Hapus produk"
                        onclick="confirmHapusProduk(event, <?= (int)$p['id'] ?>, '<?= csrf_token() ?>', '<?= addslashes(e($p['nama_produk'])) ?>')">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
                <?php else: ?>
                    <span style="font-size:12px;color:#94a3b8;" title="Hanya Administrator yang dapat mengubah inventori"><i class="fa-solid fa-lock"></i> Read-only</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    else: ?>
        <tr>
            <td colspan="8" class="stk-empty">
                <div class="stk-empty__inner">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>
                        <?= ($q_cari || $f_kategori)
                            ? 'Tidak ada produk yang cocok dengan filter. <a href="stok.php" class="stk-empty-reset" onclick="window.resetStokFilter(event)">Hapus filter &rarr;</a>'
                            : 'Belum ada produk terdaftar. Klik <strong>Tambah Produk</strong> untuk memulai.'
                        ?>
                    </p>
                </div>
            </td>
        </tr>
    <?php endif;
    return ob_get_clean();
}

/**
 * Render info total produk di bawah kotak pencarian
 */
function render_stok_total_info(int $count, bool $is_filtered): string
{
    $html = '<i class="fa-solid fa-boxes-stacked"></i> ';
    $html .= '<span>Total Produk: <strong>' . number_format($count, 0, ',', '.') . '</strong> produk ditemukan</span>';
    if ($is_filtered) {
        $html .= ' <span class="stk-badge-filtered"><i class="fa-solid fa-filter"></i> Terfilter</span>';
    }
    return $html;
}

/**
 * Render badge jumlah produk pada header panel tabel
 */
function render_stok_panel_count(int $count, bool $is_filtered): string
{
    $html = '<span class="stk-panel__count">' . number_format($count, 0, ',', '.') . ' produk</span>';
    if ($is_filtered) {
        $html .= ' <span class="stk-panel__count stk-panel__count--warn"><i class="fa-solid fa-filter"></i> Terfilter</span>';
    }
    return $html;
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTER & PENCARIAN (PARAMETER)
   ═══════════════════════════════════════════════════════════════════════════ */
$q_cari     = trim($_GET['q']        ?? '');
$f_kategori = trim($_GET['kategori'] ?? '');

// Ambil produk sesuai filter
$produk = daftar_produk($q_cari, $f_kategori);

/* ═══════════════════════════════════════════════════════════════════════════
   [FITUR AJAX] ENDPOINT ASINKRON TANPA REFRESH
   ═══════════════════════════════════════════════════════════════════════════ */
$is_ajax = (isset($_GET['ajax']) && $_GET['ajax'] == '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($is_ajax) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $is_filtered      = ($q_cari !== '' || $f_kategori !== '');
    $html_tbody       = render_stok_rows($produk, $q_cari, $f_kategori);
    $html_total_info  = render_stok_total_info(count($produk), $is_filtered);
    $html_panel_count = render_stok_panel_count(count($produk), $is_filtered);

    echo json_encode([
        'status'           => 'success',
        'total'            => count($produk),
        'is_filtered'      => $is_filtered,
        'html_tbody'       => $html_tbody,
        'html_total_info'  => $html_total_info,
        'html_panel_count' => $html_panel_count,
        'q'                => $q_cari,
        'kategori'         => $f_kategori,
    ]);
    exit;
}

// Ambil daftar kategori unik untuk filter pills (halaman penuh)
$kategori_list = [];
$kat_res = db()->query("SELECT DISTINCT kategori FROM produk ORDER BY kategori ASC");
if ($kat_res) {
    while ($kr = $kat_res->fetch_assoc()) {
        $kategori_list[] = $kr['kategori'];
    }
}

// Ringkasan stok (selalu total keseluruhan)
$ringkasan = ringkasan_stok();

/* ═══════════════════════════════════════════════════════════════════════════
   PAGE META & INCLUDES
   ═══════════════════════════════════════════════════════════════════════════ */
$page_title   = 'Stok Barang';
$page_eyebrow = NAMA_INSTANSI . ' · Inventory';
$root         = '../';
$active       = 'stok';

include __DIR__ . '/../includes/header.php';
?>

<!-- Stylesheet khusus halaman Stok v7 — Icy Azure Enterprise -->
<link rel="stylesheet" href="../assets/css/stok.css">

<!-- Style Inline Penyesuaian Modal & Input Group (Bebas Tabrakan & Sticky Footer) -->
<style>
/* Pastikan backdrop modal menengahkan konten dan bisa scroll bila layar kecil */
#modalStok.modal {
    display: none;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 24px 16px;
    z-index: 1050;
}
#modalStok.modal.show {
    display: flex !important;
}

/* Kontainer Modal Rapi & Terkunci Max-Height */
#modalStok .stk-modal {
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
#modalStok .stk-modal__form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
    margin: 0;
}

/* Header Modal */
#modalStok .stk-modal__header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(186, 230, 253, 0.5);
    background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
}

/* Body Modal dengan Scroll Halus */
#modalStok .stk-modal__body {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
    flex: 1;
    min-height: 0;
}

#modalStok .stk-modal__body::-webkit-scrollbar {
    width: 6px;
}
#modalStok .stk-modal__body::-webkit-scrollbar-track {
    background: #f8fafc;
}
#modalStok .stk-modal__body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}
#modalStok .stk-modal__body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Footer Modal: Selalu Muncul, Kontras, dan Sticky di Bagian Bawah */
#modalStok .stk-modal__footer {
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
#modalStok .stk-modal__btn-cancel {
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
#modalStok .stk-modal__btn-cancel:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}

/* Tombol Simpan Biru Korporat Kontras */
#modalStok .stk-modal__btn-save {
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
#modalStok .stk-modal__btn-save:hover {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
    box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
    transform: translateY(-1px);
}
#modalStok .stk-modal__btn-save:active {
    transform: translateY(0);
}

/* Input Group Addon "Rp" yang Bersih & Anti Bertabrakan */
#modalStok .stk-input-group {
    display: flex;
    align-items: stretch;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    transition: border-color 0.2s, box-shadow 0.2s;
}
#modalStok .stk-input-group:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 3.5px rgba(14, 165, 233, 0.15);
}
#modalStok .stk-input-addon {
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
#modalStok .stk-input-group .stk-form__input {
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
#modalStok .stk-input-group .stk-form__input:focus {
    outline: none;
}

/* ═══════════════════════════════════════════════════════════════════════════
   FILTER TOOLBAR & SEARCH BOX (PENATAAN RAPI, BEBAS TABRAKAN & HORIZONTAL PILLS)
   ═══════════════════════════════════════════════════════════════════════════ */
.stk-toolbar {
    margin-bottom: 24px;
    background: #ffffff;
    border: 1px solid rgba(186, 230, 253, 0.6);
    border-radius: 14px;
    padding: 20px 24px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
}

.stk-toolbar__form {
    display: flex;
    flex-direction: column;
    gap: 18px; /* Jarak vertikal tegas dan lapang antar baris */
    width: 100%;
}

/* Baris Atas: Kotak Pencarian di Sisi Kiri dengan Total di Bawahnya & Tombol Reset di Kanan */
.stk-toolbar__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    width: 100%;
}

.stk-search-block {
    display: flex;
    flex-direction: column;
    gap: 9px; /* Jarak nyaman antara input pencarian dan info total */
    flex: 1 1 360px;
    max-width: 500px;
    min-width: 260px;
}

/* Kotak Pencarian */
.stk-search {
    position: relative;
    width: 100%;
}

.stk-search .stk-search__icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: #0284c7;
    pointer-events: none;
    z-index: 2;
    line-height: 1;
}

.stk-search input.stk-search__input,
.stk-search input[type="text"].stk-search__input,
input[type="text"].stk-search__input,
.stk-search__input {
    width: 100%;
    height: 42px;
    padding: 0 38px 0 44px !important; /* Ruang 44px (2.75rem) menjamin teks placeholder bebas tabrakan */
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 500;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: all 0.2s ease;
    line-height: 42px;
    box-sizing: border-box !important;
}

.stk-search input.stk-search__input:focus,
.stk-search__input:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3.5px rgba(14, 165, 233, 0.14);
    background: #fafeff;
}

.stk-search input.stk-search__input::placeholder,
.stk-search__input::placeholder {
    color: #94a3b8;
    font-weight: 400;
}

.stk-search__clear {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    color: #94a3b8;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: color 0.18s, transform 0.18s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    z-index: 2;
    padding: 0;
}

.stk-search__clear:hover {
    color: #ef4444;
    transform: translateY(-50%) scale(1.1);
}

/* Informasi Total Produk Tepat di Bawah Kotak Pencarian */
.stk-total-info {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    padding-left: 2px;
}

.stk-total-info i {
    color: #0284c7;
    font-size: 13px;
}

.stk-total-info strong {
    color: #0369a1;
    font-weight: 800;
}

.stk-badge-filtered {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 800;
    color: #b45309;
    background: #fef3c7;
    border: 1px solid #fde68a;
    padding: 2px 8px;
    border-radius: 100px;
    margin-left: 6px;
}

/* Tombol Reset Filter */
.stk-btn-reset {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    background: #fff1f2;
    color: #e11d48;
    border: 1px solid #fecdd3;
    font-size: 12.5px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
}

.stk-btn-reset:hover {
    background: #ffe4e6;
    border-color: #fca5a5;
    transform: translateY(-1px);
}

/* Baris Bawah: Tombol Kategori Berjajar Rapi Horizontal ke Samping (Flex-Wrap) */
.stk-toolbar__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    padding-top: 16px;
    border-top: 1px solid rgba(226, 232, 240, 0.85); /* Garis batas tipis pemisah */
    width: 100%;
}

.stk-kategori-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    width: 100%;
}

.stk-filter-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #64748b;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.stk-filter-label i {
    color: #0284c7;
}

/* Tombol Pilihan Kategori Berjajar Rapi Horizontal */
.stk-filter-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.stk-pill {
    padding: 7px 16px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    white-space: nowrap;
    transition: all 0.2s cubic-bezier(.16,1,.3,1);
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.stk-pill i {
    font-size: 11px;
}

.stk-pill:hover {
    color: #0284c7;
    border-color: #7dd3fc;
    background: #f0f9ff;
    transform: translateY(-1px);
}

.stk-pill--active,
.stk-pill--active:hover {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: #ffffff !important;
    border-color: #0284c7;
    box-shadow: 0 3px 10px rgba(14, 165, 233, 0.28);
    transform: translateY(-1px);
}

/* Status Indikator Pemuatan AJAX */
.ajax-loading-panel {
    opacity: 0.55 !important;
    pointer-events: none !important;
    transition: opacity 0.15s ease;
}
.ajax-fade-in {
    animation: stkAjaxFadeIn 0.24s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes stkAjaxFadeIn {
    from {
        opacity: 0.45;
        transform: translateY(3px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsif Layar Kecil (Mobile) */
@media screen and (max-width: 768px) {
    .stk-toolbar {
        padding: 16px 18px;
    }
    .stk-search-block {
        width: 100%;
        max-width: 100%;
    }
    .stk-btn-reset {
        width: 100%;
        justify-content: center;
    }
    .stk-kategori-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .stk-filter-pills {
        width: 100%;
    }
}
</style>

<?php tampil_alert(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════
     PAGE HERO BANNER + TAMBAH & EXPORT BUTTONS
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="stk-hero" id="stkHero">
    <div class="stk-hero-left">
        <div class="stk-eyebrow">
            <i class="fa-solid fa-boxes-stacked"></i> MANAJEMEN INVENTORI
        </div>
        <h2 class="stk-hero-title">Stok Barang</h2>
        <p class="stk-hero-sub">
            Kelola data produk, harga beli &amp; jual, stok tersedia, dan pantau item kritis secara real-time.
        </p>
    </div>
    <div class="stk-hero-actions">
        <?php if (is_admin()): ?>
        <!-- Export -->
        <a href="stok.php?export=excel" class="stk-btn-outline" title="Ekspor data inventori ke CSV" id="btnExportStok">
            <i class="fa-solid fa-file-excel"></i> Export Excel
        </a>
        <!-- Tambah -->
        <button type="button" class="stk-btn-primary" onclick="openModal('modalStok')" id="btnTambahProduk">
            <i class="fa-solid fa-plus"></i> Tambah Produk
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     4 METRIC CARDS GLASSMORPHISM — Inventory
     Total Produk · Total Unit · Nilai Aset · Stok Kritis
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="stk-metrics" id="stkMetrics">

    <!-- 1. Total Produk -->
    <div class="stk-card stk-card--purple" id="cardTotalProduk">
        <div class="stk-card__accent"></div>
        <div class="stk-card__header">
            <div class="stk-card__icon stk-card__icon--purple">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <span class="stk-card__label">Total Produk</span>
        </div>
        <div class="stk-card__value">
            <span class="stk-card__number"><?= number_format((int)$ringkasan['total_produk']) ?></span>
            <span class="stk-card__unit">item</span>
        </div>
        <div class="stk-card__footer">
            <span class="stk-card__badge stk-card__badge--purple">
                <i class="fa-solid fa-database"></i> Produk terdaftar
            </span>
        </div>
    </div>

    <!-- 2. Total Unit Tersedia -->
    <div class="stk-card stk-card--green" id="cardTotalUnit">
        <div class="stk-card__accent"></div>
        <div class="stk-card__header">
            <div class="stk-card__icon stk-card__icon--green">
                <i class="fa-solid fa-cubes"></i>
            </div>
            <span class="stk-card__label">Total Unit Tersedia</span>
        </div>
        <div class="stk-card__value">
            <span class="stk-card__number"><?= number_format((int)$ringkasan['total_unit']) ?></span>
            <span class="stk-card__unit">unit</span>
        </div>
        <div class="stk-card__footer">
            <span class="stk-card__badge stk-card__badge--green">
                <i class="fa-solid fa-cubes"></i> Persediaan aktif
            </span>
        </div>
    </div>

    <!-- 3. Nilai Aset Stok -->
    <div class="stk-card stk-card--blue" id="cardNilaiAset">
        <div class="stk-card__accent"></div>
        <div class="stk-card__header">
            <div class="stk-card__icon stk-card__icon--blue">
                <i class="fa-solid fa-rupiah-sign"></i>
            </div>
            <span class="stk-card__label">Nilai Aset Stok</span>
        </div>
        <div class="stk-card__value">
            <span class="stk-card__currency">Rp</span>
            <span class="stk-card__number"><?= number_format((float)$ringkasan['total_nilai_aset'], 0, ',', '.') ?></span>
        </div>
        <div class="stk-card__footer">
            <span class="stk-card__detail">
                <i class="fa-solid fa-info-circle"></i> Berdasarkan harga beli × stok
            </span>
        </div>
    </div>

    <!-- 4. Stok Kritis -->
    <div class="stk-card stk-card--amber" id="cardStokKritis">
        <div class="stk-card__accent"></div>
        <div class="stk-card__header">
            <div class="stk-card__icon stk-card__icon--amber">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <span class="stk-card__label">Stok Kritis</span>
        </div>
        <div class="stk-card__value">
            <span class="stk-card__number stk-card__number--warn"><?= number_format((int)$ringkasan['stok_kritis']) ?></span>
            <span class="stk-card__unit">produk</span>
        </div>
        <div class="stk-card__footer">
            <span class="stk-card__badge stk-card__badge--amber">
                <i class="fa-solid fa-exclamation"></i> Perlu restok segera
            </span>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     TOOLBAR: SEARCH BAR + KATEGORI FILTER PILLS (CLEAN TWO-ROW SAAS LAYOUT)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="stk-toolbar" id="stkToolbar">
    <form method="GET" action="stok.php" class="stk-toolbar__form" id="filterFormStok" onsubmit="return false;">

        <!-- Baris Atas: Kotak Pencarian di Sisi Kiri, Total Produk di Bawahnya, dan Tombol Reset di Kanan -->
        <div class="stk-toolbar__top">
            <div class="stk-search-block">
                <!-- Search Box -->
                <div class="stk-search">
                    <i class="fa-solid fa-magnifying-glass stk-search__icon"></i>
                    <input
                        type="text"
                        name="q"
                        class="stk-search__input"
                        placeholder="Cari kode, nama produk, kategori..."
                        value="<?= e($q_cari) ?>"
                        autocomplete="off"
                        id="searchInputStok">
                    <span id="stkClearArea">
                        <?php if ($q_cari): ?>
                            <button type="button" class="stk-search__clear" id="searchClearBtn" title="Hapus pencarian">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Informasi Total Produk Tepat di Bawah Kotak Pencarian -->
                <div class="stk-total-info" id="stkTotalInfo">
                    <?= render_stok_total_info(count($produk), ($q_cari !== '' || $f_kategori !== '')) ?>
                </div>
            </div>

            <div id="stkResetArea">
                <?php if ($q_cari || $f_kategori): ?>
                    <a href="stok.php" class="stk-btn-reset" id="stkBtnReset" title="Kembalikan semua filter ke awal">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Reset Filter</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Baris Bawah: Tombol Kategori Berjajar Rapi Horizontal ke Samping (Flex-Wrap) -->
        <div class="stk-toolbar__bottom">
            <div class="stk-kategori-bar">
                <span class="stk-filter-label">
                    <i class="fa-solid fa-layer-group"></i> Kategori Produk:
                </span>
                <div class="stk-filter-pills" id="stkFilterPills">
                    <a href="stok.php<?= $q_cari ? '?q=' . urlencode($q_cari) : '' ?>"
                       class="stk-pill <?= $f_kategori === '' ? 'stk-pill--active' : '' ?>"
                       data-kategori="">
                        <i class="fa-solid fa-border-all"></i> Semua Kategori
                    </a>
                    <?php foreach ($kategori_list as $kat): ?>
                        <a href="stok.php?kategori=<?= urlencode($kat) ?><?= $q_cari ? '&q=' . urlencode($q_cari) : '' ?>"
                           class="stk-pill <?= $f_kategori === $kat ? 'stk-pill--active' : '' ?>"
                           data-kategori="<?= e($kat) ?>">
                            <?= e($kat) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </form>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     TABEL DAFTAR PRODUK
     Kolom: # · Kode · Nama Produk · Kategori · Stok · Harga Beli · Harga Jual · Aksi
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="stk-panel" id="stkPanel">

    <div class="stk-panel__header">
        <div class="stk-panel__title-group">
            <div class="stk-panel__eyebrow">
                <i class="fa-solid fa-boxes-stacked"></i> DAFTAR INVENTORI
            </div>
            <h3 class="stk-panel__title">
                Produk Terdaftar
                <span id="stkPanelCountGroup">
                    <?= render_stok_panel_count(count($produk), ($q_cari !== '' || $f_kategori !== '')) ?>
                </span>
            </h3>
        </div>
    </div>

    <div class="stk-table-wrap">
        <table class="stk-table" id="stkTable">
            <thead>
                <tr>
                    <th class="stk-table__col-num">#</th>
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th class="stk-table__col-right">Harga Beli</th>
                    <th class="stk-table__col-right">Harga Jual</th>
                    <th class="stk-table__col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody id="stkTbody">
                <?= render_stok_rows($produk, $q_cari, $f_kategori) ?>
            </tbody>
        </table>
    </div>

</div><!-- /.stk-panel -->

<?php if (is_admin()): ?>
<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL: TAMBAH / EDIT PRODUK
     Tinggi terkontrol (max-height), scroll halus, footer sticky, input group bersih
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal" id="modalStok">
    <div class="stk-modal">
        <!-- Header Modal -->
        <div class="stk-modal__header">
            <div class="stk-modal__header-icon">
                <i class="fa-solid fa-box"></i>
            </div>
            <div class="stk-modal__header-text">
                <h3 class="stk-modal__title" id="modalTitle">Tambah Produk Baru</h3>
                <p class="stk-modal__subtitle">
                    Isi semua kolom wajib (<span style="color:#ef4444">*</span>) dengan benar.
                </p>
            </div>
            <button type="button" class="stk-modal__close" onclick="closeModal('modalStok')" id="btnCloseModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Form Modal -->
        <form method="POST" action="stok.php" id="formProduk" class="stk-modal__form">
            <?= csrf_field() ?>
            <input type="hidden" id="prod_id" name="id" value="0">

            <div class="stk-modal__body">

                <!-- Alert Notifikasi Validasi Modal -->
                <div class="fintoko-modal-alert" id="stkModalAlert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Terjadi Kesalahan Pengisian:</strong>
                        <ul id="stkModalAlertList"></ul>
                    </div>
                </div>

                <!-- Section: Identitas Produk -->
                <div class="stk-form__section">
                    <i class="fa-solid fa-tag"></i> Identitas Produk
                </div>

                <!-- Baris 1: Kode + Nama -->
                <div class="stk-form__row">
                    <div class="stk-form__group">
                        <label class="stk-form__label" for="kode_produk">
                            <i class="fa-solid fa-barcode"></i> Kode Produk <span class="stk-form__req">*</span>
                        </label>
                        <input type="text" id="kode_produk" name="kode_produk" class="stk-form__input"
                               placeholder="PRD-007" maxlength="50" pattern="^[A-Za-z0-9\-\.\_]{2,50}$"
                               title="Format: 2-50 karakter alfanumerik (huruf, angka, -, ., _)" required autocomplete="off">
                        <span class="form-error-feedback" id="err_kode_produk">
                            <i class="fa-solid fa-circle-exclamation"></i> Format kode produk tidak valid (2-50 alfanumerik).
                        </span>
                    </div>
                    <div class="stk-form__group">
                        <label class="stk-form__label" for="nama_produk">
                            <i class="fa-solid fa-box"></i> Nama Produk <span class="stk-form__req">*</span>
                        </label>
                        <input type="text" id="nama_produk" name="nama_produk" class="stk-form__input"
                               placeholder="Nama produk lengkap..." minlength="3" maxlength="150" required autocomplete="off">
                        <span class="form-error-feedback" id="err_nama_produk">
                            <i class="fa-solid fa-circle-exclamation"></i> Nama produk minimal 3 karakter.
                        </span>
                    </div>
                </div>

                <!-- Baris 2: Kategori + Satuan -->
                <div class="stk-form__row">
                    <div class="stk-form__group">
                        <label class="stk-form__label" for="kategori">
                            <i class="fa-solid fa-layer-group"></i> Kategori <span class="stk-form__req">*</span>
                        </label>
                        <input type="text" id="kategori" name="kategori" class="stk-form__input"
                               list="katListModal" placeholder="Elektronik, ATK, dll." maxlength="100" required>
                        <datalist id="katListModal">
                            <option value="Elektronik">
                            <option value="Aksesoris Komputer">
                            <option value="Alat Tulis Kantor">
                            <option value="Perlengkapan Toko">
                            <option value="Makanan & Minuman">
                            <option value="Peralatan Rumah Tangga">
                        </datalist>
                        <span class="form-error-feedback" id="err_kategori">
                            <i class="fa-solid fa-circle-exclamation"></i> Kategori produk wajib diisi.
                        </span>
                    </div>
                    <div class="stk-form__group">
                        <label class="stk-form__label" for="satuan">
                            <i class="fa-solid fa-ruler-combined"></i> Satuan <span class="stk-form__req">*</span>
                        </label>
                        <input type="text" id="satuan" name="satuan" class="stk-form__input"
                               list="satListModal" placeholder="Pcs, Unit, Box..." maxlength="30" required>
                        <datalist id="satListModal">
                            <option value="Pcs">
                            <option value="Unit">
                            <option value="Box">
                            <option value="Rim">
                            <option value="Botol">
                            <option value="Buku">
                            <option value="Kg">
                            <option value="Liter">
                        </datalist>
                        <span class="form-error-feedback" id="err_satuan">
                            <i class="fa-solid fa-circle-exclamation"></i> Satuan produk wajib diisi.
                        </span>
                    </div>
                </div>

                <!-- Section: Harga -->
                <div class="stk-form__section">
                    <i class="fa-solid fa-coins"></i> Informasi Harga
                </div>

                <!-- Baris 3: Harga Beli + Harga Jual (Standard Input Group Addon Rp) -->
                <div class="stk-form__row">
                    <!-- Harga Beli -->
                    <div class="stk-form__group">
                        <label class="stk-form__label" for="harga_beli">
                            <i class="fa-solid fa-tags"></i> Harga Beli (Rp) <span class="stk-form__req">*</span>
                        </label>
                        <div class="stk-input-group">
                            <span class="stk-input-addon">Rp</span>
                            <input type="number" id="harga_beli" name="harga_beli" class="stk-form__input"
                                   placeholder="0" min="0" step="1" required>
                        </div>
                        <span class="form-error-feedback" id="err_harga_beli">
                            <i class="fa-solid fa-circle-exclamation"></i> Harga beli minimal 0.
                        </span>
                    </div>
                    <!-- Harga Jual -->
                    <div class="stk-form__group">
                        <label class="stk-form__label" for="harga_jual">
                            <i class="fa-solid fa-hand-holding-dollar"></i> Harga Jual (Rp) <span class="stk-form__req">*</span>
                        </label>
                        <div class="stk-input-group">
                            <span class="stk-input-addon">Rp</span>
                            <input type="number" id="harga_jual" name="harga_jual" class="stk-form__input"
                                   placeholder="0" min="0" step="1" required>
                        </div>
                        <span class="form-error-feedback" id="err_harga_jual">
                            <i class="fa-solid fa-circle-exclamation"></i> Harga jual minimal 0.
                        </span>
                    </div>
                </div>

                <!-- Section: Stok -->
                <div class="stk-form__section">
                    <i class="fa-solid fa-cubes"></i> Informasi Stok
                </div>

                <!-- Baris 4: Stok + Batas Kritis -->
                <div class="stk-form__row">
                    <div class="stk-form__group">
                        <label class="stk-form__label" for="stok">
                            <i class="fa-solid fa-warehouse"></i> Stok Awal <span class="stk-form__req">*</span>
                        </label>
                        <input type="number" id="stok" name="stok" class="stk-form__input"
                               placeholder="0" min="0" step="1" required>
                        <span class="form-error-feedback" id="err_stok">
                            <i class="fa-solid fa-circle-exclamation"></i> Jumlah stok tidak boleh negatif.
                        </span>
                    </div>
                    <div class="stk-form__group">
                        <label class="stk-form__label" for="stok_minimum">
                            <i class="fa-solid fa-triangle-exclamation"></i> Batas Stok Kritis <span class="stk-form__req">*</span>
                        </label>
                        <input type="number" id="stok_minimum" name="stok_minimum" class="stk-form__input"
                               placeholder="5" min="0" step="1" required>
                        <span class="form-error-feedback" id="err_stok_minimum">
                            <i class="fa-solid fa-circle-exclamation"></i> Batas stok minimum tidak boleh negatif.
                        </span>
                    </div>
                </div>

            </div><!-- /.stk-modal__body -->

            <!-- FOOTER MODAL: Sticky, Tombol Batal & Simpan Muncul dengan Jelas -->
            <div class="stk-modal__footer">
                <button type="button" class="stk-modal__btn-cancel" onclick="closeModal('modalStok')">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
                <button type="submit" class="stk-modal__btn-save" id="btnSaveProduk">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; /* is_admin */ ?>

<?php
/* ═══════════════════════════════════════════════════════════════════════════
   JAVASCRIPT HALAMAN STOK: REAL-TIME AJAX FILTER & LIVE SEARCH
   ═══════════════════════════════════════════════════════════════════════════ */
$extra_scripts = '
<script>
/* ═══════════════════════════════════════════════════════════════════════════
   REAL-TIME AJAX LIVE SEARCH & FILTERING (ZERO PAGE RELOAD)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var searchInput    = document.getElementById("searchInputStok");
    var totalInfoEl    = document.getElementById("stkTotalInfo");
    var countGroupEl   = document.getElementById("stkPanelCountGroup");
    var tbodyEl        = document.getElementById("stkTbody");
    var pillsContainer = document.getElementById("stkFilterPills");
    var resetArea      = document.getElementById("stkResetArea");
    var clearArea      = document.getElementById("stkClearArea");

    var currentCategory = ' . json_encode($f_kategori) . ';
    var currentQuery    = searchInput ? searchInput.value.trim() : "";
    var searchDebounce  = null;
    var abortCtrl       = null;

    // Helper indikator progress bar top
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

    // Perbarui active state pada tombol kategori
    function updatePillsUI(kategori) {
        if (!pillsContainer) return;
        var pills = pillsContainer.querySelectorAll(".stk-pill");
        pills.forEach(function(pill) {
            var pKat = pill.getAttribute("data-kategori") || "";
            if (pKat.toLowerCase() === (kategori || "").toLowerCase()) {
                pill.classList.add("stk-pill--active");
            } else {
                pill.classList.remove("stk-pill--active");
            }
        });
    }

    // Perbarui tombol Reset Filter dan Tombol X (Clear Search)
    function updateResetUI(q, kategori) {
        var hasFilter = (q !== "" || kategori !== "");

        // Tombol Reset Filter di Kanan Atas
        if (resetArea) {
            if (hasFilter) {
                if (!document.getElementById("stkBtnReset")) {
                    resetArea.innerHTML = \'<a href="stok.php" class="stk-btn-reset" id="stkBtnReset" title="Kembalikan semua filter ke awal">\' +
                        \'<i class="fa-solid fa-rotate-left"></i> <span>Reset Filter</span></a>\';
                }
            } else {
                resetArea.innerHTML = "";
            }
        }

        // Tombol Hapus Teks Pencarian (X)
        if (clearArea) {
            if (q !== "") {
                clearArea.innerHTML = \'<button type="button" class="stk-search__clear" id="searchClearBtn" title="Hapus pencarian">\' +
                    \'<i class="fa-solid fa-xmark"></i></button>\';
            } else {
                clearArea.innerHTML = "";
            }
        }
    }

    // Fungsi inti eksekusi AJAX fetch
    function executeAjaxFilter(q, kategori, pushHistory) {
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = new AbortController();

        showProgress();

        var params = new URLSearchParams();
        params.append("ajax", "1");
        if (q) params.append("q", q);
        if (kategori) params.append("kategori", kategori);

        var fetchUrl = "stok.php?" + params.toString();

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

                // 2. Perbarui Informasi Total Produk Ditemukan
                if (totalInfoEl) {
                    totalInfoEl.innerHTML = data.html_total_info;
                }

                // 3. Perbarui Badge Count pada Header Panel
                if (countGroupEl) {
                    countGroupEl.innerHTML = data.html_panel_count;
                }

                // 4. Perbarui Tombol Kategori Aktif
                updatePillsUI(data.kategori);

                // 5. Perbarui Tombol Reset & Clear Search
                updateResetUI(data.q, data.kategori);

                // 6. Sinkronisasi URL Address Bar (tanpa refresh)
                if (pushHistory !== false && window.history && window.history.replaceState) {
                    var browserParams = new URLSearchParams();
                    if (data.q) browserParams.append("q", data.q);
                    if (data.kategori) browserParams.append("kategori", data.kategori);
                    var cleanUrl = "stok.php" + (browserParams.toString() ? "?" + browserParams.toString() : "");
                    window.history.replaceState({ q: data.q, kategori: data.kategori }, "", cleanUrl);
                }
            }
        })
        .catch(function(err) {
            if (err.name !== "AbortError") {
                console.error("[Stok AJAX Filter Error]", err);
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
                executeAjaxFilter(currentQuery, currentCategory);
            }, 250);
        });

        searchInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(searchDebounce);
                currentQuery = searchInput.value.trim();
                executeAjaxFilter(currentQuery, currentCategory);
            }
        });
    }

    // 2. Event Klik Tombol Kategori
    if (pillsContainer) {
        pillsContainer.addEventListener("click", function(e) {
            var pill = e.target.closest(".stk-pill");
            if (!pill) return;
            e.preventDefault();

            var selectedKat = pill.getAttribute("data-kategori") || "";
            currentCategory = selectedKat;
            currentQuery    = searchInput ? searchInput.value.trim() : "";
            executeAjaxFilter(currentQuery, currentCategory);
        });
    }

    // 3. Event Klik Tombol Clear Search & Reset Filter
    document.addEventListener("click", function(e) {
        var clearBtn = e.target.closest("#searchClearBtn, .stk-search__clear");
        if (clearBtn) {
            e.preventDefault();
            if (searchInput) {
                searchInput.value = "";
                searchInput.focus();
            }
            currentQuery = "";
            executeAjaxFilter(currentQuery, currentCategory);
            return;
        }

        var rBtn = e.target.closest("#stkBtnReset, .stk-btn-reset, .stk-empty-reset");
        if (rBtn) {
            e.preventDefault();
            if (searchInput) searchInput.value = "";
            currentQuery    = "";
            currentCategory = "";
            executeAjaxFilter("", "");
            return;
        }
    });

    // 4. Dukungan Navigasi Tombol Back/Forward Browser
    window.addEventListener("popstate", function() {
        var params = new URLSearchParams(window.location.search);
        var q   = params.get("q") || "";
        var kat = params.get("kategori") || "";
        if (searchInput) searchInput.value = q;
        currentQuery    = q;
        currentCategory = kat;
        executeAjaxFilter(q, kat, false);
    });

    // Helper Global untuk link "Hapus filter →" saat data kosong
    window.resetStokFilter = function(e) {
        if (e) e.preventDefault();
        if (searchInput) searchInput.value = "";
        currentQuery    = "";
        currentCategory = "";
        executeAjaxFilter("", "");
    };

    window.reloadStokTable = function() {
        executeAjaxFilter(currentQuery, currentCategory);
    };
})();

/* ═══════════════════════════════════════════════════════════════════════════
   CUSTOM POPUP KONFIRMASI HAPUS PRODUK (AJAX REAL-TIME)
   ═══════════════════════════════════════════════════════════════════════════ */
function confirmHapusProduk(e, id, csrf, nama) {
    if (e) e.preventDefault();
    window.fintokoDeleteConfirm({
        title: "Hapus Produk Inventori?",
        text: "Apakah Anda yakin ingin menghapus produk ini dari katalog? Data transaksi dan mutasi stok terkait produk ini akan terpengaruh.",
        itemName: "📦 " + nama,
        onConfirm: function() {
            var formData = new FormData();
            formData.append("hapus", id);
            formData.append("csrf_token", csrf);

            fetch("stok.php", {
                method: "POST",
                body: formData,
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.status === "success") {
                    window.fintokoToast(data.message || "Produk berhasil dihapus.", "success");
                    if (typeof window.reloadStokTable === "function") {
                        window.reloadStokTable();
                    }
                } else {
                    window.fintokoToast(data.message || "Gagal menghapus produk.", "error");
                }
            })
            .catch(function() {
                window.location.href = "stok.php?deleted=1";
            });
        }
    });
}

/* ═══════════════════════════════════════════════════════════════════════════
   EDIT PRODUK — isi form modal dari data JSON inline
   ═══════════════════════════════════════════════════════════════════════════ */
function editProduk(p) {
    document.getElementById("modalTitle").textContent     = "Edit Produk";
    document.getElementById("btnSaveProduk").innerHTML     = "<i class=\"fa-solid fa-floppy-disk\"></i> Simpan Perubahan";

    document.getElementById("prod_id").value       = p.id;
    document.getElementById("kode_produk").value   = p.kode_produk;
    document.getElementById("nama_produk").value   = p.nama_produk;
    document.getElementById("kategori").value      = p.kategori;
    document.getElementById("satuan").value        = p.satuan;
    document.getElementById("harga_beli").value    = parseFloat(p.harga_beli);
    document.getElementById("harga_jual").value    = parseFloat(p.harga_jual);
    document.getElementById("stok").value          = parseInt(p.stok);
    document.getElementById("stok_minimum").value  = parseInt(p.stok_minimum);

    openModal("modalStok");
}

/* ═══════════════════════════════════════════════════════════════════════════
   RESET MODAL ketika membuka Tambah (bukan Edit)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var origOpenModal = window.openModal;
    if (origOpenModal) {
        window.openModal = function(id) {
            if (id === "modalStok") {
                var currentId = document.getElementById("prod_id").value;
                if (currentId === "0" || currentId === "") {
                    document.getElementById("modalTitle").textContent = "Tambah Produk Baru";
                    document.getElementById("btnSaveProduk").innerHTML = "<i class=\"fa-solid fa-floppy-disk\"></i> Simpan Produk";
                    document.getElementById("formProduk").reset();
                    document.getElementById("prod_id").value = "0";
                }
            }
            origOpenModal(id);
        };
    }
})();

/* ═══════════════════════════════════════════════════════════════════════════
   SAAT MODAL DITUTUP, RESET STATE
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var origCloseModal = window.closeModal;
    if (origCloseModal) {
        window.closeModal = function(id) {
            origCloseModal(id);
            if (id === "modalStok") {
                document.getElementById("prod_id").value = "0";
                document.getElementById("modalTitle").textContent = "Tambah Produk Baru";
                document.getElementById("btnSaveProduk").innerHTML = "<i class=\"fa-solid fa-floppy-disk\"></i> Simpan Produk";
                document.getElementById("formProduk").reset();
                document.getElementById("prod_id").value = "0";
            }
        };
    }
})();

/* ═══════════════════════════════════════════════════════════════════════════
   VALIDASI FORM INPUT & ALERT FEEDBACK (CLIENT-SIDE)
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {
    var form = document.getElementById("formProduk");
    if (!form) return;

    var alertBox = document.getElementById("stkModalAlert");
    var alertList = document.getElementById("stkModalAlertList");

    function clearFieldError(id) {
        var input = document.getElementById(id);
        var errEl = document.getElementById("err_" + id);
        if (input) input.classList.remove("is-invalid");
        if (errEl) errEl.classList.remove("is-visible");
    }

    function setFieldError(id, msg) {
        var input = document.getElementById(id);
        var errEl = document.getElementById("err_" + id);
        if (input) input.classList.add("is-invalid");
        if (errEl) {
            errEl.innerHTML = "<i class=\"fa-solid fa-circle-exclamation\"></i> " + msg;
            errEl.classList.add("is-visible");
        }
    }

    function resetAllErrors() {
        if (alertBox) alertBox.classList.remove("is-visible");
        if (alertList) alertList.innerHTML = "";
        ["kode_produk", "nama_produk", "kategori", "satuan", "harga_beli", "harga_jual", "stok", "stok_minimum"].forEach(clearFieldError);
    }

    // Event listener blur untuk validasi real-time
    ["kode_produk", "nama_produk", "kategori", "satuan", "harga_beli", "harga_jual", "stok", "stok_minimum"].forEach(function(fieldId) {
        var el = document.getElementById(fieldId);
        if (el) {
            el.addEventListener("input", function() {
                clearFieldError(fieldId);
            });
        }
    });

    form.addEventListener("submit", function(e) {
        resetAllErrors();
        var errors = [];
        var firstInvalid = null;

        var kode = document.getElementById("kode_produk").value.trim();
        var nama = document.getElementById("nama_produk").value.trim();
        var kat  = document.getElementById("kategori").value.trim();
        var sat  = document.getElementById("satuan").value.trim();
        var hb   = parseFloat(document.getElementById("harga_beli").value);
        var hj   = parseFloat(document.getElementById("harga_jual").value);
        var stk  = parseInt(document.getElementById("stok").value, 10);
        var min  = parseInt(document.getElementById("stok_minimum").value, 10);

        // 1. Validasi Kode Produk
        if (!kode) {
            errors.push("Kode Produk wajib diisi.");
            setFieldError("kode_produk", "Kode produk wajib diisi.");
            if (!firstInvalid) firstInvalid = document.getElementById("kode_produk");
        } else if (!/^[A-Za-z0-9\-\.\_]{2,50}$/.test(kode)) {
            errors.push("Format Kode Produk tidak valid! Gunakan 2-50 karakter alfanumerik (huruf, angka, -, ., _).");
            setFieldError("kode_produk", "Format tidak valid (2-50 alfanumerik tanpa spasi).");
            if (!firstInvalid) firstInvalid = document.getElementById("kode_produk");
        }

        // 2. Validasi Nama Produk
        if (!nama) {
            errors.push("Nama Produk wajib diisi.");
            setFieldError("nama_produk", "Nama produk wajib diisi.");
            if (!firstInvalid) firstInvalid = document.getElementById("nama_produk");
        } else if (nama.length < 3) {
            errors.push("Nama Produk terlalu pendek (minimal 3 karakter).");
            setFieldError("nama_produk", "Nama produk minimal 3 karakter.");
            if (!firstInvalid) firstInvalid = document.getElementById("nama_produk");
        }

        // 3. Validasi Kategori & Satuan
        if (!kat) {
            errors.push("Kategori Produk wajib diisi.");
            setFieldError("kategori", "Kategori wajib diisi.");
            if (!firstInvalid) firstInvalid = document.getElementById("kategori");
        }
        if (!sat) {
            errors.push("Satuan Produk wajib diisi.");
            setFieldError("satuan", "Satuan wajib diisi.");
            if (!firstInvalid) firstInvalid = document.getElementById("satuan");
        }

        // 4. Validasi Angka Harga
        if (isNaN(hb) || hb < 0) {
            errors.push("Harga Beli tidak boleh kosong atau negatif.");
            setFieldError("harga_beli", "Harga beli minimal 0.");
            if (!firstInvalid) firstInvalid = document.getElementById("harga_beli");
        }
        if (isNaN(hj) || hj < 0) {
            errors.push("Harga Jual tidak boleh kosong atau negatif.");
            setFieldError("harga_jual", "Harga jual minimal 0.");
            if (!firstInvalid) firstInvalid = document.getElementById("harga_jual");
        }

        // 5. Validasi Stok
        if (isNaN(stk) || stk < 0) {
            errors.push("Jumlah Stok Awal tidak boleh negatif.");
            setFieldError("stok", "Jumlah stok minimal 0.");
            if (!firstInvalid) firstInvalid = document.getElementById("stok");
        }
        if (isNaN(min) || min < 0) {
            errors.push("Batas Stok Kritis tidak boleh negatif.");
            setFieldError("stok_minimum", "Batas stok minimum minimal 0.");
            if (!firstInvalid) firstInvalid = document.getElementById("stok_minimum");
        }

        if (errors.length > 0) {
            e.preventDefault();
            if (alertList && alertBox) {
                alertList.innerHTML = errors.map(function(err) { return "<li>" + err + "</li>"; }).join("");
                alertBox.classList.add("is-visible");
            }
            var modalContent = form.closest(".stk-modal") || form;
            modalContent.classList.remove("shake-it");
            void modalContent.offsetWidth;
            modalContent.classList.add("shake-it");

            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
    });

    window.resetStokFormErrors = resetAllErrors;
})();
</script>';

include __DIR__ . '/../includes/footer.php';

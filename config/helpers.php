<?php
/**
 * ---------------------------------------------------------------------
 * HELPER FUNCTIONS - FinToko (Enterprise Accounting Edition)
 * ---------------------------------------------------------------------
 * Menyediakan fungsi pendukung inti di seluruh modul sistem:
 * - Koneksi DB & Environment
 * - CSRF & Session Security
 * - Autentikasi & Otorisasi Pengguna
 * - Format Mata Uang, Tanggal, & Tren
 * - Agregasi Ringkasan Kas & Stok
 * - Keamanan Unggah & Hapus File
 * - Rate Limiting Keamanan Login
 * ---------------------------------------------------------------------
 */

// ── Mulai session jika belum aktif ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Load koneksi database ──
require_once __DIR__ . '/koneksi.php';

// ── Konstanta Branding & Identitas Instansi ──
if (!defined('NAMA_INSTANSI'))    define('NAMA_INSTANSI',    'FinToko');
if (!defined('NAMA_APLIKASI'))    define('NAMA_APLIKASI',    'FinToko — Sistem Informasi Keuangan');
if (!defined('JUDUL_SISTEM'))     define('JUDUL_SISTEM',     'Sistem Informasi Manajemen Keuangan dan Akuntansi Toko Modern');
if (!defined('ALAMAT_INSTANSI'))  define('ALAMAT_INSTANSI',  'Jl. Contoh No. 1, Kota Anda, Provinsi, 00000');
if (!defined('TELEPON_INSTANSI')) define('TELEPON_INSTANSI', '(021) 0000-0000');
if (!defined('EMAIL_INSTANSI'))   define('EMAIL_INSTANSI',   'info@fintoko.example.com');
if (!defined('WEBSITE_INSTANSI')) define('WEBSITE_INSTANSI', 'www.fintoko.example.com');

/**
 * Singleton koneksi database.
 * Mengembalikan instance mysqli yang sudah terhubung ke database.
 */
function db(): mysqli
{
    global $koneksi;
    return $koneksi;
}

/**
 * Redirect ke URL lain dan hentikan eksekusi.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Escape output untuk mencegah XSS (HTML Special Characters).
 */
function e(mixed $val): string
{
    return htmlspecialchars((string) ($val ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ═══════════════════════════════════════════════════════════════════════════
   CSRF & FLASH MESSAGES
   ═══════════════════════════════════════════════════════════════════════════ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(string $token): bool
{
    if (empty($_SESSION['csrf_token']) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash(string $type, string $msg): void
{
    $_SESSION['flash'][$type] = $msg;
}

function get_flash(string $type): ?string
{
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function tampil_alert(): void
{
    $types = ['success', 'error', 'warning', 'info'];
    foreach ($types as $type) {
        $msg = get_flash($type);
        if ($msg) {
            $cls = match ($type) {
                'success' => 'alert-success',
                'error'   => 'alert-danger',
                'warning' => 'alert-warning',
                default   => 'alert-info',
            };
            $ico = match ($type) {
                'success' => 'fa-circle-check',
                'error'   => 'fa-circle-xmark',
                'warning' => 'fa-triangle-exclamation',
                default   => 'fa-circle-info',
            };
            echo '<div class="alert ' . $cls . ' mb-4" role="alert" style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:18px;">';
            echo '<i class="fa-solid ' . $ico . '"></i>';
            echo '<div>' . e($msg) . '</div>';
            echo '</div>';
        }
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   AUTENTIKASI & PENGGUNA
   ═══════════════════════════════════════════════════════════════════════════ */

function is_logged_in(): bool
{
    return isset($_SESSION['login']) && $_SESSION['login'] === true;
}

function require_login(string $root = ''): void
{
    if (!is_logged_in()) {
        redirect($root . 'login.php');
    }
}

function is_admin(): bool
{
    return isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'admin';
}

function require_admin(string $root = ''): void
{
    if (!is_admin()) {
        set_flash('error', 'Akses ditolak. Fitur ini memerlukan hak akses Administrator.');
        redirect($root . 'index.php');
    }
}

function nama_user(): string
{
    return $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Pengguna';
}

function inisial_user(): string
{
    $nama = trim(nama_user());
    if ($nama === '') return 'U';
    $words = explode(' ', $nama);
    if (count($words) >= 2) {
        return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
    return strtoupper(mb_substr($nama, 0, 2));
}

function label_role_user(): string
{
    return is_admin() ? 'Administrator' : 'Customer';
}

function nama_container(): string
{
    return gethostname() ?: 'fintoko-app';
}

/* ═══════════════════════════════════════════════════════════════════════════
   FORMAT MATA UANG, TANGGAL, & TREN
   ═══════════════════════════════════════════════════════════════════════════ */

function rupiah(float|int|string|null $angka): string
{
    return 'Rp ' . number_format((float)($angka ?? 0), 0, ',', '.');
}

function tanggal_id(?string $tanggal = null): string
{
    if (!$tanggal) $tanggal = date('Y-m-d');
    $ts = strtotime($tanggal);
    if (!$ts) return $tanggal;
    $bulan_id = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $d = date('j', $ts);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);
    return $d . ' ' . ($bulan_id[$m] ?? date('M', $ts)) . ' ' . $y;
}

function tanggal_panjang_id(?string $tanggal = null): string
{
    if (!$tanggal) $tanggal = date('Y-m-d');
    $ts = strtotime($tanggal);
    if (!$ts) return $tanggal;
    $hari_id = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    $bulan_id = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $h = $hari_id[date('l', $ts)] ?? date('l', $ts);
    $d = date('j', $ts);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);
    return $h . ', ' . $d . ' ' . ($bulan_id[$m] ?? date('F', $ts)) . ' ' . $y;
}

function bulan_tahun_id(?string $bulan_str = null): string
{
    if (!$bulan_str) $bulan_str = date('Y-m');
    $parts = explode('-', $bulan_str);
    if (count($parts) < 2) return $bulan_str;
    $y = $parts[0];
    $m = (int)$parts[1];
    $bulan_id = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return ($bulan_id[$m] ?? $parts[1]) . ' ' . $y;
}

function tren_html(float $curr, float $prev): string
{
    if ($prev <= 0) {
        return '<span class="mc-trend trend-neutral"><i class="fa-solid fa-minus"></i> 0%</span>';
    }
    $diff = $curr - $prev;
    $pct  = round(($diff / $prev) * 100, 1);
    if ($pct > 0) {
        return '<span class="mc-trend trend-up" style="color:#10b981;"><i class="fa-solid fa-arrow-trend-up"></i> +' . $pct . '%</span>';
    } elseif ($pct < 0) {
        return '<span class="mc-trend trend-down" style="color:#ef4444;"><i class="fa-solid fa-arrow-trend-down"></i> ' . $pct . '%</span>';
    }
    return '<span class="mc-trend trend-neutral"><i class="fa-solid fa-minus"></i> 0%</span>';
}

/* ═══════════════════════════════════════════════════════════════════════════
   RINGKASAN METRIK KAS & STOK
   ═══════════════════════════════════════════════════════════════════════════ */

function ringkasan_kas(): array
{
    try {
        $res = db()->query("
            SELECT
                COALESCE(SUM(CASE WHEN jenis = 'Pemasukan' THEN nominal ELSE 0 END), 0) AS masuk,
                COALESCE(SUM(CASE WHEN jenis = 'Pengeluaran' THEN nominal ELSE 0 END), 0) AS keluar
            FROM transaksi
        ");
        $data = $res ? $res->fetch_assoc() : ['masuk' => 0, 'keluar' => 0];
        $masuk = (float)($data['masuk'] ?? 0);
        $keluar = (float)($data['keluar'] ?? 0);
        return [
            'masuk'  => $masuk,
            'keluar' => $keluar,
            'saldo'  => $masuk - $keluar,
        ];
    } catch (Throwable $e) {
        return ['masuk' => 0, 'keluar' => 0, 'saldo' => 0];
    }
}

function ringkasan_stok(): array
{
    try {
        $res = db()->query("
            SELECT
                COUNT(*) AS total_produk,
                COALESCE(SUM(stok), 0) AS total_unit,
                COALESCE(SUM(stok * harga_beli), 0) AS total_nilai_aset,
                COALESCE(SUM(CASE WHEN stok <= stok_minimum THEN 1 ELSE 0 END), 0) AS stok_kritis
            FROM produk
        ");
        $data = $res ? $res->fetch_assoc() : [];
        return [
            'total_produk'     => (int)($data['total_produk'] ?? 0),
            'total_unit'       => (int)($data['total_unit'] ?? 0),
            'total_nilai_aset' => (float)($data['total_nilai_aset'] ?? 0),
            'stok_kritis'      => (int)($data['stok_kritis'] ?? 0),
        ];
    } catch (Throwable $e) {
        return [
            'total_produk'     => 0,
            'total_unit'       => 0,
            'total_nilai_aset' => 0,
            'stok_kritis'      => 0,
        ];
    }
}

/**
 * Mengambil daftar produk inventori dengan opsi filter pencarian dan kategori.
 *
 * @param  string $q_cari   Kata kunci pencarian (kode / nama produk)
 * @param  string $kategori Filter kategori produk
 * @return array            Array daftar produk
 */
function daftar_produk(string $q_cari = '', string $kategori = ''): array
{
    try {
        $where  = [];
        $params = [];
        $types  = '';

        if ($q_cari !== '') {
            $where[]  = '(kode_produk LIKE ? OR nama_produk LIKE ?)';
            $like     = "%{$q_cari}%";
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        if ($kategori !== '') {
            $where[]  = 'kategori = ?';
            $params[] = $kategori;
            $types   .= 's';
        }

        $sql = 'SELECT * FROM produk';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY id DESC';

        if ($params) {
            $stmt = db()->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        }

        $res = db()->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Memproses pencatatan mutasi stok (Masuk / Keluar) beserta penyesuaian kas dan stok produk secara otomatis.
 * Menggunakan database transaction agar integritas data terjamin.
 *
 * @param  int    $produk_id    ID Produk
 * @param  string $jenis        'Masuk' atau 'Keluar'
 * @param  int    $jumlah       Jumlah unit barang (> 0)
 * @param  float  $harga_satuan Harga satuan (>= 0)
 * @param  string $keterangan   Catatan keterangan
 * @param  string $tanggal      Tanggal transaksi (YYYY-MM-DD)
 * @return bool                 true jika sukses, false jika gagal / stok tidak mencukupi
 */
function proses_mutasi_stok(
    int $produk_id,
    string $jenis,
    int $jumlah,
    float $harga_satuan,
    string $keterangan = '',
    string $tanggal = ''
): bool {
    if ($produk_id <= 0 || !in_array($jenis, ['Masuk', 'Keluar'], true) || $jumlah <= 0) {
        return false;
    }

    if ($tanggal === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $tanggal = date('Y-m-d');
    }

    $db = db();
    $db->begin_transaction();

    try {
        // Ambil data produk saat ini (dengan FOR UPDATE untuk concurrency safety)
        $stmt_prd = $db->prepare('SELECT id, kode_produk, nama_produk, satuan, harga_beli, harga_jual, stok FROM produk WHERE id = ? FOR UPDATE');
        if (!$stmt_prd) {
            $db->rollback();
            return false;
        }
        $stmt_prd->bind_param('i', $produk_id);
        $stmt_prd->execute();
        $produk = $stmt_prd->get_result()->fetch_assoc();
        $stmt_prd->close();

        if (!$produk) {
            $db->rollback();
            return false;
        }

        $stok_sekarang = (int)$produk['stok'];
        if ($harga_satuan <= 0) {
            $harga_satuan = ($jenis === 'Masuk') ? (float)$produk['harga_beli'] : (float)$produk['harga_jual'];
        }
        $total_biaya = $jumlah * $harga_satuan;

        if ($jenis === 'Keluar') {
            if ($jumlah > $stok_sekarang) {
                // Stok tidak mencukupi
                $db->rollback();
                return false;
            }
            $stok_baru = $stok_sekarang - $jumlah;
        } else {
            $stok_baru = $stok_sekarang + $jumlah;
        }

        // 1. Update stok produk
        $stmt_up = $db->prepare('UPDATE produk SET stok = ? WHERE id = ?');
        if (!$stmt_up) {
            $db->rollback();
            return false;
        }
        $stmt_up->bind_param('ii', $stok_baru, $produk_id);
        $stmt_up->execute();
        $stmt_up->close();

        // 2. Catat riwayat_stok
        $stmt_rw = $db->prepare('
            INSERT INTO riwayat_stok (produk_id, tanggal, jenis, jumlah, harga_satuan, total_biaya, keterangan)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        if (!$stmt_rw) {
            $db->rollback();
            return false;
        }
        $stmt_rw->bind_param('issidds', $produk_id, $tanggal, $jenis, $jumlah, $harga_satuan, $total_biaya, $keterangan);
        $stmt_rw->execute();
        $stmt_rw->close();

        // 3. Catat transaksi keuangan otomatis
        if ($total_biaya > 0) {
            $jenis_kas  = ($jenis === 'Masuk') ? 'Pengeluaran' : 'Pemasukan';
            $kategori   = ($jenis === 'Masuk') ? 'Pembelian Stok' : 'Penjualan Produk';
            $ket_kas    = ($jenis === 'Masuk')
                ? 'Pembelian stok: ' . $produk['nama_produk'] . ' (' . $jumlah . ' ' . $produk['satuan'] . ')'
                : 'Penjualan produk: ' . $produk['nama_produk'] . ' (' . $jumlah . ' ' . $produk['satuan'] . ')';
            if ($keterangan !== '') {
                $ket_kas .= ' - ' . $keterangan;
            }
            $metode     = 'Tunai';
            $input_oleh = nama_user();

            $stmt_tx = $db->prepare('
                INSERT INTO transaksi (tanggal, jenis, kategori, keterangan, nominal, metode, input_oleh)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            if ($stmt_tx) {
                $stmt_tx->bind_param('ssssdss', $tanggal, $jenis_kas, $kategori, $ket_kas, $total_biaya, $metode, $input_oleh);
                $stmt_tx->execute();
                $stmt_tx->close();
            }
        }

        $db->commit();
        return true;
    } catch (Throwable $e) {
        $db->rollback();
        return false;
    }
}

/**
 * Hapus transaksi keuangan berdasarkan ID (opsional beserta validasi jenis transaksi).
 * Menggunakan Prepared Statement untuk keamanan database SQL injection.
 *
 * @param  int         $id    ID transaksi yang akan dihapus
 * @param  string|null $jenis Filter jenis transaksi ('Pemasukan' / 'Pengeluaran') opsional
 * @return bool               true jika berhasil dihapus, false jika gagal
 */
function hapus_transaksi(int $id, ?string $jenis = null): bool
{
    if ($id <= 0) {
        return false;
    }

    try {
        if ($jenis !== null && in_array($jenis, ['Pemasukan', 'Pengeluaran'], true)) {
            $stmt = db()->prepare('DELETE FROM transaksi WHERE id = ? AND jenis = ?');
            if (!$stmt) return false;
            $stmt->bind_param('is', $id, $jenis);
        } else {
            $stmt = db()->prepare('DELETE FROM transaksi WHERE id = ?');
            if (!$stmt) return false;
            $stmt->bind_param('i', $id);
        }

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        return false;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   PENGELOLAAN FILE & UPLOAD
   ═══════════════════════════════════════════════════════════════════════════ */

function secure_upload(array $file, string $target_dir, int $max_mb = 5): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > ($max_mb * 1024 * 1024)) {
        return null;
    }
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed_mimes[$mime])) {
        return null;
    }
    $ext = $allowed_mimes[$mime];
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0755, true);
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = rtrim($target_dir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    return null;
}

function safe_delete_file(?string $filename, string $dir): void
{
    if (!$filename) return;
    $clean_name = basename($filename);
    $target_file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $clean_name;
    if (file_exists($target_file) && is_file($target_file)) {
        @unlink($target_file);
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   RATE LIMITING PERCOBAAN LOGIN
   ═══════════════════════════════════════════════════════════════════════════ */

function is_login_rate_limited(string $identifier): bool
{
    $max_attempts   = 5;
    $window_minutes = 15;

    try {
        $stmt = db()->prepare('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $window_minutes);
        $stmt->execute();
        $stmt->close();

        $stmt = db()->prepare('SELECT COUNT(*) AS cnt FROM login_attempts WHERE identifier = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $identifier, $window_minutes);
        $stmt->execute();
        $count = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        return $count >= $max_attempts;
    } catch (Throwable $e) {
        return false;
    }
}

function record_failed_login(string $identifier): void
{
    try {
        $stmt = db()->prepare('INSERT INTO login_attempts (identifier) VALUES (?)');
        if (!$stmt) return;
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
    }
}

function clear_login_attempts(string $identifier): void
{
    try {
        $stmt = db()->prepare('DELETE FROM login_attempts WHERE identifier = ?');
        if (!$stmt) return;
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
    }
}

/**
 * =====================================================================
 * EKSPOR EXCEL NATIVE DENGAN AUTO-FIT LEBAR KOLOM & PADDING
 * =====================================================================
 * Menghitung panjang karakter terpanjang dari setiap kolom (judul & isi sel),
 * lalu menambahkan ruang ekstra (padding) agar tampilan tidak terpotong
 * dan angka moneter tidak menampilkan tanda ### di Microsoft Excel.
 *
 * @param string $filename    Nama berkas unduhan
 * @param array  $headers     Daftar judul kolom
 * @param array  $rows        Data baris-baris tabel
 * @param array  $col_types   Tipe kolom spesifik (misal [6 => 'number'])
 * @param string $sheet_name  Nama lembar kerja (worksheet)
 */
function export_excel_auto_fit(string $filename, array $headers, array $rows, array $col_types = [], string $sheet_name = 'Data'): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $num_cols = count($headers);
    $char_lengths = array_fill(0, $num_cols, 0);

    // 1. Hitung panjang karakter judul header
    foreach (array_values($headers) as $col_idx => $header_text) {
        $char_lengths[$col_idx] = max($char_lengths[$col_idx], mb_strlen((string)$header_text, 'UTF-8'));
    }

    // 2. Format baris dan hitung panjang karakter isi data
    $formatted_rows = [];
    foreach ($rows as $r_idx => $row) {
        $row_vals = array_values($row);
        $clean_row = [];

        foreach ($row_vals as $col_idx => $val) {
            $is_number = false;
            $type_key = $col_types[$col_idx] ?? null;

            if ($type_key === 'number' || (is_numeric($val) && !preg_match('/^0\d+/', (string)$val))) {
                $is_number = true;
                $num_val = (float)$val;
                $clean_row[] = ['type' => 'number', 'val' => $num_val];
                // Estimasi panjang tampilan angka dengan pemisah ribuan
                $display_len = mb_strlen(number_format($num_val, 0, ',', '.'), 'UTF-8');
            } else {
                $str_val = (string)$val;
                $clean_row[] = ['type' => 'string', 'val' => $str_val];
                $display_len = mb_strlen($str_val, 'UTF-8');
            }

            if ($col_idx < $num_cols) {
                $char_lengths[$col_idx] = max($char_lengths[$col_idx], $display_len);
            }
        }
        $formatted_rows[] = $clean_row;
    }

    // 3. Konversi panjang karakter ke Points lebar kolom Excel + Padding Ekstra (+4 karakter)
    // Formula kalibrasi: (karakter + padding) * 7.8pt + margin sel 14pt (minimum 65pt)
    $col_widths = [];
    $padding_chars = 4;
    foreach ($char_lengths as $col_idx => $len) {
        $effective_chars = $len + $padding_chars;
        $col_widths[$col_idx] = max((int)round($effective_chars * 7.8 + 14), 65);
    }

    // Set header HTTP attachment Excel
    $safe_filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
    if (!str_ends_with(strtolower($safe_filename), '.xls')) {
        $safe_filename .= '_' . date('Ymd_His') . '.xls';
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: public');

    // Output XML SpreadsheetML (Format resmi Microsoft Excel)
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    ?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Borders/>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#0F172A"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <!-- Header Icy Azure Corporate FinToko -->
  <Style ss:ID="sHeader">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0284C7"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BAE6FD"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BAE6FD"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0284C7"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#0EA5E9" ss:Pattern="Solid"/>
  </Style>
  <!-- Baris Data Teks (Genap & Ganjil) -->
  <Style ss:ID="sDataText">
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1E293B"/>
   <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="sDataTextAlt">
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1E293B"/>
   <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
  </Style>
  <!-- Baris Data Angka/Nominal (Format Ribuan #,##0, Bebas Tanda ###) -->
  <Style ss:ID="sDataNum">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#0F172A" ss:Bold="1"/>
   <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="#,##0"/>
  </Style>
  <Style ss:ID="sDataNumAlt">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#0F172A" ss:Bold="1"/>
   <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="#,##0"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="<?= htmlspecialchars($sheet_name, ENT_QUOTES, 'UTF-8') ?>">
  <Table ss:DefaultRowHeight="20">
   <!-- Deklarasi Lebar Kolom Otomatis Hasil Auto-Fit + Padding -->
   <?php foreach ($col_widths as $idx => $w): ?>
   <Column ss:Index="<?= $idx + 1 ?>" ss:Width="<?= $w ?>"/>
   <?php endforeach; ?>

   <!-- Baris Header -->
   <Row ss:Height="26">
    <?php foreach (array_values($headers) as $h): ?>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String"><?= htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8') ?></Data></Cell>
    <?php endforeach; ?>
   </Row>

   <!-- Baris Data -->
   <?php foreach ($formatted_rows as $r_idx => $row_cells): 
       $is_alt = ($r_idx % 2 === 1);
   ?>
   <Row ss:Height="21">
    <?php foreach ($row_cells as $c): 
        if ($c['type'] === 'number') {
            $style_id = $is_alt ? 'sDataNumAlt' : 'sDataNum';
            echo '<Cell ss:StyleID="' . $style_id . '"><Data ss:Type="Number">' . $c['val'] . '</Data></Cell>';
        } else {
            $style_id = $is_alt ? 'sDataTextAlt' : 'sDataText';
            echo '<Cell ss:StyleID="' . $style_id . '"><Data ss:Type="String">' . htmlspecialchars((string)$c['val'], ENT_QUOTES, 'UTF-8') . '</Data></Cell>';
        }
    endforeach; ?>
   </Row>
   <?php endforeach; ?>
  </Table>
 </Worksheet>
</Workbook>
<?php
    exit;
}

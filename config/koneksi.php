<?php
/**
 * ---------------------------------------------------------------------
 * KONEKSI DATABASE & ENVIRONMENT SISTEM FinToko
 * ---------------------------------------------------------------------
 * Semua kredensial WAJIB diambil dari environment variable.
 * Tidak ada fallback default untuk keamanan.
 */

// ── Validasi environment variable wajib ──
$host     = getenv('DB_HOST');
$user     = getenv('DB_USER');
$password = getenv('DB_PASS');
$database = getenv('DB_NAME');

// Untuk kompatibilitas development lokal (Laragon/XAMPP),
// baca file .env jika environment variable sistem belum disetel.
if ($host === false && $user === false && $database === false) {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$key, $val] = array_map('trim', explode('=', $line, 2));
            $val = trim($val, '"\'');
            putenv("$key=$val");
        }
        $host     = getenv('DB_HOST');
        $user     = getenv('DB_USER');
        $password = getenv('DB_PASS');
        $database = getenv('DB_NAME');
    }
}

// Validasi final: semua kredensial wajib ada
if (empty($host) || empty($user) || empty($database)) {
    http_response_code(500);
    die(
        '<div style="font-family:sans-serif;padding:40px;color:#7a1f1f;background:#fdecec;">' .
        '<h2>Konfigurasi Database Tidak Lengkap</h2>' .
        '<p>Environment variable <code>DB_HOST</code>, <code>DB_USER</code>, dan <code>DB_NAME</code> wajib diatur.</p>' .
        '<p>Untuk development lokal, buat file <code>.env</code> di root project. Lihat <code>.env.example</code> sebagai panduan.</p>' .
        '</div>'
    );
}

if ($password === false) {
    $password = '';
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli($host, $user, $password, $database);
    $koneksi->set_charset('utf8mb4');
    // Koneksi berhasil.
    // Migrasi skema dan seed data dipisahkan ke database/migrate.php (CLI / startup).
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    // ⛔ SECURITY: Jangan tampilkan detail error SQL di production
    $is_debug = strtolower((string)getenv('APP_DEBUG')) === 'true';
    $detail   = $is_debug
        ? '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
        : '<p>Terjadi kesalahan koneksi database. Hubungi administrator sistem.</p>';

    die(
        '<div style="font-family:sans-serif;padding:40px;color:#7a1f1f;background:#fdecec;">' .
        '<h2>Koneksi database gagal</h2>' .
        $detail .
        '</div>'
    );
}

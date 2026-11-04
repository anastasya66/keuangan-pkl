<?php
/**
 * =====================================================================
 * FINTOKO — Sistem Informasi Keuangan & Inventaris
 * =====================================================================
 * File    : pages/dashboard.php
 * Halaman : Dashboard Ringkasan Eksekutif
 * =====================================================================
 */

require_once __DIR__ . '/../config/helpers.php';
require_login('../');

/* ── Data Keuangan ── */
$ringkasan         = ringkasan_kas();
$total_pemasukan   = (float)($ringkasan['masuk']  ?? 0);
$total_pengeluaran = (float)($ringkasan['keluar'] ?? 0);
$total_saldo       = (float)($ringkasan['saldo']  ?? 0);

/* ── Data Stok ── */
$ring_stok    = ringkasan_stok();
$total_produk = (int)($ring_stok['total_produk'] ?? 0);
$stok_kritis  = (int)($ring_stok['stok_kritis']  ?? 0);

/* ── Grafik 7 Bulan ── */
$bulan_labels      = [];
$bulan_pemasukan   = [];
$bulan_pengeluaran = [];

$stmt = db()->prepare("
    SELECT COALESCE(SUM(nominal), 0) AS total
    FROM transaksi
    WHERE jenis = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
");

for ($i = 6; $i >= 0; $i--) {
    $ts    = strtotime("-{$i} months");
    $tahun = (int)date('Y', $ts);
    $bulan = (int)date('m', $ts);
    $bulan_labels[] = date('M Y', $ts);

    $j = 'Pemasukan';
    $stmt->bind_param('sii', $j, $tahun, $bulan);
    $stmt->execute();
    $bulan_pemasukan[] = (float)$stmt->get_result()->fetch_assoc()['total'];

    $j = 'Pengeluaran';
    $stmt->bind_param('sii', $j, $tahun, $bulan);
    $stmt->execute();
    $bulan_pengeluaran[] = (float)$stmt->get_result()->fetch_assoc()['total'];
}
$stmt->close();

/* ── 5 Transaksi Terakhir ── */
$transaksi_list = db()->query('
    SELECT * FROM transaksi ORDER BY tanggal DESC, id DESC LIMIT 5
')->fetch_all(MYSQLI_ASSOC);

/* ── Variabel Header ── */
$page_title   = 'Dashboard';
$page_eyebrow = NAMA_INSTANSI;
$root         = '../';
$active       = 'dashboard';

include __DIR__ . '/../includes/header.php';
?>

<?php tampil_alert(); ?>

<!-- ===================================================================
     BANNER SAMBUTAN
     =================================================================== -->
<div class="welcome-bar">
    <div class="wb-left">
        <div class="wb-tag">
            <span class="wb-dot"></span>
            <span>RINGKASAN EKSEKUTIF &bull; <?= strtoupper(e(label_role_user())) ?></span>
        </div>
        <h2 class="wb-title">Selamat Datang, <?= e(nama_user()) ?> 👋</h2>
        <p class="wb-sub">Pantau arus kas, neraca, dan stok <strong><?= e(NAMA_INSTANSI) ?></strong> secara <em>real-time</em>.</p>
    </div>
    <div class="wb-right">
        <div class="wb-date">
            <i class="fa-regular fa-calendar-check"></i>
            <span><?= date('l, d F Y') ?></span>
        </div>
    </div>
</div>

<!-- ===================================================================
     METRIC CARDS — 5 KARTU SEJAJAR (4 kolom)
     =================================================================== -->
<div class="metric-grid">

    <!-- [1] Total Pemasukan -->
    <div class="mc income">
        <div class="mc-accent income-accent"></div>
        <div class="mc-header">
            <div>
                <span class="mc-eyebrow">KAS MASUK</span>
                <span class="mc-lbl">Total Pemasukan</span>
            </div>
            <div class="mc-ico ico-green"><i class="fa-solid fa-arrow-trend-up"></i></div>
        </div>
        <div class="mc-val">
            <span class="mc-rp">Rp</span><span class="mc-num"><?= number_format($total_pemasukan, 0, ',', '.') ?></span>
        </div>
        <div class="mc-foot">
            <?= tren_html($bulan_pemasukan[6], $bulan_pemasukan[5]) ?>
            <span class="mc-foot-txt">vs bulan lalu</span>
        </div>
    </div>

    <!-- [2] Total Pengeluaran -->
    <div class="mc expense">
        <div class="mc-accent expense-accent"></div>
        <div class="mc-header">
            <div>
                <span class="mc-eyebrow">KAS KELUAR</span>
                <span class="mc-lbl">Total Pengeluaran</span>
            </div>
            <div class="mc-ico ico-red"><i class="fa-solid fa-arrow-trend-down"></i></div>
        </div>
        <div class="mc-val">
            <span class="mc-rp">Rp</span><span class="mc-num"><?= number_format($total_pengeluaran, 0, ',', '.') ?></span>
        </div>
        <div class="mc-foot">
            <?= tren_html($bulan_pengeluaran[6], $bulan_pengeluaran[5]) ?>
            <span class="mc-foot-txt">vs bulan lalu</span>
        </div>
    </div>

    <!-- [3] Saldo Kas -->
    <div class="mc balance">
        <div class="mc-accent balance-accent"></div>
        <div class="mc-header">
            <div>
                <span class="mc-eyebrow">POSISI KAS</span>
                <span class="mc-lbl">Saldo Kas Aktif</span>
            </div>
            <div class="mc-ico ico-blue"><i class="fa-solid fa-wallet"></i></div>
        </div>
        <div class="mc-val">
            <span class="mc-rp">Rp</span>
            <span class="mc-num <?= $total_saldo < 0 ? 'num-red' : '' ?>"><?= number_format(abs($total_saldo), 0, ',', '.') ?></span>
        </div>
        <div class="mc-foot">
            <?php if ($total_saldo >= 0): ?>
                <span class="badge-ok"><i class="fa-solid fa-circle-check"></i> Likuiditas Aman</span>
            <?php else: ?>
                <span class="badge-warn"><i class="fa-solid fa-circle-exclamation"></i> Saldo Minus</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- [4] Total Produk -->
    <div class="mc products">
        <div class="mc-accent products-accent"></div>
        <div class="mc-header">
            <div>
                <span class="mc-eyebrow">INVENTARIS</span>
                <span class="mc-lbl">Total Produk</span>
            </div>
            <div class="mc-ico ico-purple"><i class="fa-solid fa-boxes-stacked"></i></div>
        </div>
        <div class="mc-val">
            <span class="mc-num"><?= number_format($total_produk, 0, ',', '.') ?></span>
            <span class="mc-unit">Item</span>
        </div>
        <div class="mc-foot">
            <?php if ($stok_kritis > 0): ?>
                <span class="badge-warn"><i class="fa-solid fa-triangle-exclamation"></i> <?= $stok_kritis ?> kritis</span>
            <?php else: ?>
                <span class="badge-ok"><i class="fa-solid fa-warehouse"></i> Stok Aman</span>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.metric-grid -->

<!-- ===================================================================
     GRAFIK ARUS KAS + AKSES CEPAT
     =================================================================== -->
<div class="two-col">

    <!-- Chart Panel -->
    <section class="panel">
        <div class="panel-head">
            <div>
                <div class="panel-eyebrow"><i class="fa-solid fa-chart-line"></i> ANALISIS KEUANGAN</div>
                <h3 class="panel-title">Tren Arus Kas <span class="sub">(7 Bulan Terakhir)</span></h3>
            </div>
            <div class="chart-legend">
                <span><span class="ld blue"></span> Masuk</span>
                <span><span class="ld red"></span> Keluar</span>
            </div>
        </div>
        <div class="chart-box">
            <canvas id="chartArusKas"></canvas>
        </div>
    </section>

    <!-- Quick Access Panel -->
    <section class="panel">
        <div class="panel-head">
            <div>
                <div class="panel-eyebrow"><i class="fa-solid fa-bolt"></i> PINTASAN SISTEM</div>
                <h3 class="panel-title">Akses Cepat</h3>
            </div>
        </div>
        <nav class="quick-nav">
            <a href="../pages/pemasukan.php" class="qn-item">
                <div class="qn-ico qni-green"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <div class="qn-text"><strong>Tambah Pemasukan</strong><span>Catat kas masuk harian</span></div>
                <i class="fa-solid fa-chevron-right qn-arr"></i>
            </a>
            <a href="../pages/pengeluaran.php" class="qn-item">
                <div class="qn-ico qni-red"><i class="fa-solid fa-arrow-trend-down"></i></div>
                <div class="qn-text"><strong>Tambah Pengeluaran</strong><span>Catat beban operasional</span></div>
                <i class="fa-solid fa-chevron-right qn-arr"></i>
            </a>
            <a href="../pages/stok.php" class="qn-item">
                <div class="qn-ico qni-purple"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="qn-text"><strong>Manajemen Stok</strong><span>Kelola produk &amp; mutasi</span></div>
                <i class="fa-solid fa-chevron-right qn-arr"></i>
            </a>
            <a href="../pages/jurnal.php" class="qn-item">
                <div class="qn-ico qni-blue"><i class="fa-solid fa-book-bookmark"></i></div>
                <div class="qn-text"><strong>Jurnal Umum</strong><span>Pembukuan debit &amp; kredit</span></div>
                <i class="fa-solid fa-chevron-right qn-arr"></i>
            </a>
            <a href="../pages/laporan.php" class="qn-item">
                <div class="qn-ico qni-amber"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <div class="qn-text"><strong>Laporan Keuangan</strong><span>Neraca &amp; laba rugi</span></div>
                <i class="fa-solid fa-chevron-right qn-arr"></i>
            </a>
        </nav>
    </section>

</div>

<!-- ===================================================================
     TABEL TRANSAKSI TERBARU
     =================================================================== -->
<section class="panel panel-tbl">

    <div class="panel-head">
        <div>
            <div class="panel-eyebrow"><i class="fa-solid fa-clock-rotate-left"></i> RIWAYAT MUTASI</div>
            <h3 class="panel-title">Transaksi Kas Terbaru</h3>
        </div>
        <a href="../pages/kas.php" class="btn-outline">
            Buka Kas Lengkap <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    <div class="tbl-scroll">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th class="text-right">Nominal</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($transaksi_list): ?>
                <?php foreach ($transaksi_list as $t): ?>
                <tr>
                    <td class="td-date">
                        <i class="fa-regular fa-calendar"></i>
                        <?= tanggal_id($t['tanggal']) ?>
                    </td>
                    <td>
                        <?php if ($t['jenis'] === 'Pemasukan'): ?>
                            <span class="pill pill-in"><i class="fa-solid fa-arrow-trend-up"></i> Pemasukan</span>
                        <?php else: ?>
                            <span class="pill pill-out"><i class="fa-solid fa-arrow-trend-down"></i> Pengeluaran</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="chip"><?= e($t['kategori'] ?: 'Umum') ?></span></td>
                    <td class="td-desc"><?= e($t['keterangan'] ?: '—') ?></td>
                    <td class="td-amount <?= $t['jenis'] === 'Pemasukan' ? 'ta-in' : 'ta-out' ?>">
                        <?= $t['jenis'] === 'Pemasukan' ? '+' : '−' ?> <?= rupiah($t['nominal']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="td-empty">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>Belum ada catatan transaksi saat ini.</p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

<?php
$extra_scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const el = document.getElementById("chartArusKas");
    if (!el) return;
    new Chart(el, {
        type: "line",
        data: {
            labels: ' . json_encode($bulan_labels) . ',
            datasets: [
                {
                    label: "Pemasukan",
                    data: ' . json_encode($bulan_pemasukan) . ',
                    borderColor: "#0ea5e9",
                    backgroundColor: "rgba(14,165,233,0.08)",
                    fill: true, tension: 0.42, borderWidth: 2.5,
                    pointBackgroundColor: "#fff", pointBorderColor: "#0ea5e9",
                    pointBorderWidth: 2.5, pointRadius: 4.5, pointHoverRadius: 7
                },
                {
                    label: "Pengeluaran",
                    data: ' . json_encode($bulan_pengeluaran) . ',
                    borderColor: "#ef4444",
                    backgroundColor: "rgba(239,68,68,0.05)",
                    fill: true, tension: 0.42, borderWidth: 2.5,
                    pointBackgroundColor: "#fff", pointBorderColor: "#ef4444",
                    pointBorderWidth: 2.5, pointRadius: 4.5, pointHoverRadius: 7
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#0f172a", cornerRadius: 10, padding: 12,
                    titleFont: { family: "Plus Jakarta Sans", size: 12, weight: "700" },
                    bodyFont:  { family: "Plus Jakarta Sans", size: 12 },
                    callbacks: {
                        label: c => c.dataset.label + ": Rp " + new Intl.NumberFormat("id-ID").format(c.parsed.y)
                    }
                }
            },
            scales: {
                y: {
                    grid: { color: "rgba(226,232,240,0.5)", drawBorder: false },
                    border: { display: false },
                    ticks: {
                        color: "#94a3b8",
                        font: { family: "Plus Jakarta Sans", size: 11, weight: "600" },
                        callback: v => v >= 1e6 ? (v/1e6).toFixed(1)+"Jt" : v >= 1e3 ? (v/1e3).toFixed(0)+"Rb" : v
                    }
                },
                x: {
                    grid: { display: false }, border: { display: false },
                    ticks: { color: "#64748b", font: { family: "Plus Jakarta Sans", size: 11, weight: "600" } }
                }
            }
        }
    });
});
</script>';

include __DIR__ . '/../includes/footer.php';
?>

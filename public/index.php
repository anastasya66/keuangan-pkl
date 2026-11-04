<?php
session_start();
$page      = 'dashboard';
$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head><?php include 'includes/head.php'; ?></head>
<body>
<div class="app">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <header class="topbar">
            <div class="topbar-title">
                <h2>Dashboard Analitik</h2>
                <p>Ringkasan kinerja keuangan hari ini</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('l, d F Y') ?></span>
                <div class="user-chip">
                    <div class="user-avatar">A</div>
                    <span class="user-name">Admin</span>
                </div>
            </div>
        </header>

        <div class="content">

            <!-- ── Metric Cards ─────────────────────────────── -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-top">
                        <span class="metric-label">Kas Masuk Bulan Ini</span>
                        <div class="metric-icon icon-green">
                            <svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        </div>
                    </div>
                    <div class="metric-value">
                        <span class="metric-rp">Rp</span>
                        <span class="metric-amount">142.500.000</span>
                    </div>
                    <div class="metric-trend trend-up">
                        <svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
                        +12,5% dari bulan lalu
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-top">
                        <span class="metric-label">Kas Keluar Bulan Ini</span>
                        <div class="metric-icon icon-red">
                            <svg viewBox="0 0 24 24"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                        </div>
                    </div>
                    <div class="metric-value">
                        <span class="metric-rp">Rp</span>
                        <span class="metric-amount">84.200.000</span>
                    </div>
                    <div class="metric-trend trend-down">
                        <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        -3,2% dari bulan lalu
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-top">
                        <span class="metric-label">Nilai Stok Barang</span>
                        <div class="metric-icon icon-blue">
                            <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        </div>
                    </div>
                    <div class="metric-value">
                        <span class="metric-rp">Rp</span>
                        <span class="metric-amount">210.750.000</span>
                    </div>
                    <div class="metric-trend trend-up">
                        <svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
                        Stok aman &amp; termonitor
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-top">
                        <span class="metric-label">Laba Bersih Bulan Ini</span>
                        <div class="metric-icon icon-amber">
                            <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                    </div>
                    <div class="metric-value">
                        <span class="metric-rp">Rp</span>
                        <span class="metric-amount">58.300.000</span>
                    </div>
                    <div class="metric-trend trend-up">
                        <svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
                        +8,1% dari bulan lalu
                    </div>
                </div>
            </div>

            <!-- ── Journal Table ────────────────────────────── -->
            <div class="section">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-info">
                            <div class="card-header-title">Transaksi Jurnal Terbaru</div>
                            <div class="card-header-sub">10 entri jurnal paling baru</div>
                        </div>
                        <a href="accounting.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Jurnal</th>
                                    <th>Keterangan</th>
                                    <th>Jenis</th>
                                    <th class="col-num">Debit (Rp)</th>
                                    <th class="col-num">Kredit (Rp)</th>
                                    <th class="col-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rows = [
                                    ['30 Sep 2026','JU-00124','Pembayaran Supplier PT Makmur','Keluar','12.500.000','—','Selesai','green'],
                                    ['30 Sep 2026','JU-00125','Pendapatan Penjualan Shift Pagi','Masuk','—','4.250.000','Selesai','green'],
                                    ['29 Sep 2026','JU-00126','Biaya Listrik & Air September','Keluar','850.000','—','Pending','amber'],
                                    ['29 Sep 2026','JU-00127','Pembelian Stok Beras Premium 100 Sak','Keluar','6.500.000','—','Selesai','green'],
                                    ['28 Sep 2026','JU-00128','Pendapatan Penjualan Shift Siang','Masuk','—','2.900.000','Selesai','green'],
                                ];
                                foreach ($rows as [$tgl,$no,$ket,$jenis,$db,$cr,$status,$sc]):
                                ?>
                                <tr>
                                    <td><?= $tgl ?></td>
                                    <td class="col-mono"><?= $no ?></td>
                                    <td style="font-weight:500;color:var(--slate-900);"><?= htmlspecialchars($ket) ?></td>
                                    <td>
                                        <span class="badge <?= $jenis==='Masuk' ? 'badge-green' : 'badge-red' ?>">
                                            <?= $jenis ?>
                                        </span>
                                    </td>
                                    <td class="col-num"><?= $db ?></td>
                                    <td class="col-num"><?= $cr ?></td>
                                    <td class="col-center">
                                        <span class="badge badge-<?= $sc ?>"><?= $status ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /content -->
    </div><!-- /main -->
</div><!-- /app -->
</body>
</html>

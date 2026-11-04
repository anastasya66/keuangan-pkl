<?php
session_start();
$page      = 'finance';
$pageTitle = 'Arus Kas';
$txns = [
    ['30 Sep 2026','KM-0089','Penjualan Toko Shift Pagi','Pendapatan Usaha',3500000,0,'Selesai','green'],
    ['30 Sep 2026','KK-0055','Bayar Supplier PT Makmur Jaya','Pembelian Barang',0,12500000,'Selesai','green'],
    ['29 Sep 2026','KM-0088','Penjualan Toko Shift Siang','Pendapatan Usaha',4200000,0,'Selesai','green'],
    ['29 Sep 2026','KK-0054','Biaya Listrik & Air Sep 2026','Biaya Operasional',0,850000,'Pending','amber'],
    ['28 Sep 2026','KM-0087','Penjualan Toko Shift Pagi','Pendapatan Usaha',2900000,0,'Selesai','green'],
    ['28 Sep 2026','KK-0053','Gaji Karyawan September','Penggajian',0,18000000,'Selesai','green'],
    ['27 Sep 2026','KK-0052','Biaya Sewa Tempat Sep 2026','Sewa & Fasilitas',0,3500000,'Selesai','green'],
    ['27 Sep 2026','KM-0086','Penjualan Toko Shift Malam','Pendapatan Usaha',1800000,0,'Selesai','green'],
];
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
                <h2>Arus Kas — Masuk &amp; Keluar</h2>
                <p>Rekap keuangan operasional harian</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('d F Y') ?></span>
                <div class="user-chip"><div class="user-avatar">A</div><span class="user-name">Admin</span></div>
            </div>
        </header>
        <div class="content">

            <div class="page-header">
                <div class="page-header-text">
                    <h1>Catatan Arus Kas</h1>
                    <p>Periode: September 2026</p>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-success">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Kas Masuk
                    </button>
                    <button class="btn btn-danger">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Kas Keluar
                    </button>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--s5);margin-bottom:var(--s8);">
                <div class="metric-card">
                    <div class="metric-top"><span class="metric-label">Total Kas Masuk</span><div class="metric-icon icon-green"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div></div>
                    <div class="metric-value"><span class="metric-rp">Rp</span><span class="metric-amount">142.500.000</span></div>
                    <div class="metric-trend trend-up"><svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>September 2026</div>
                </div>
                <div class="metric-card">
                    <div class="metric-top"><span class="metric-label">Total Kas Keluar</span><div class="metric-icon icon-red"><svg viewBox="0 0 24 24"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg></div></div>
                    <div class="metric-value"><span class="metric-rp">Rp</span><span class="metric-amount">84.200.000</span></div>
                    <div class="metric-trend trend-down"><svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>September 2026</div>
                </div>
                <div class="metric-card">
                    <div class="metric-top"><span class="metric-label">Saldo Bersih</span><div class="metric-icon icon-blue"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div></div>
                    <div class="metric-value"><span class="metric-rp">Rp</span><span class="metric-amount">58.300.000</span></div>
                    <div class="metric-trend trend-up"><svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>Surplus positif</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-info">
                        <div class="card-header-title">Riwayat Transaksi Kas</div>
                        <div class="card-header-sub"><?= count($txns) ?> transaksi terakhir</div>
                    </div>
                    <button class="btn btn-secondary btn-sm">Filter Periode</button>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Referensi</th>
                                <th>Keterangan</th>
                                <th>Kategori</th>
                                <th class="col-num">Masuk (Rp)</th>
                                <th class="col-num">Keluar (Rp)</th>
                                <th class="col-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($txns as [$tgl,$ref,$ket,$cat,$masuk,$keluar,$status,$sc]): ?>
                            <tr>
                                <td><?= $tgl ?></td>
                                <td class="col-mono"><?= $ref ?></td>
                                <td style="font-weight:500;color:var(--slate-900);"><?= htmlspecialchars($ket) ?></td>
                                <td><span class="badge badge-blue"><?= htmlspecialchars($cat) ?></span></td>
                                <td class="col-num" style="color:var(--green-600);font-weight:600;">
                                    <?= $masuk > 0 ? number_format($masuk) : '<span style="color:var(--slate-300)">—</span>' ?>
                                </td>
                                <td class="col-num" style="color:var(--red-500);font-weight:600;">
                                    <?= $keluar > 0 ? number_format($keluar) : '<span style="color:var(--slate-300)">—</span>' ?>
                                </td>
                                <td class="col-center"><span class="badge badge-<?= $sc ?>"><?= $status ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>

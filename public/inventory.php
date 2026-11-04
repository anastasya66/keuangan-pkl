<?php
session_start();
$page      = 'inventory';
$pageTitle = 'Manajemen Stok';

$items = [
    ['SKU-1001','Beras Premium 5 kg','Sembako',45,10,60000,65000,'ok'],
    ['SKU-1002','Minyak Goreng 2 L','Sembako',12,20,28000,32000,'low'],
    ['SKU-1003','Indomie Goreng Karton','Makanan',2,5,110000,115000,'critical'],
    ['SKU-1004','Gula Pasir 1 kg','Sembako',80,15,13500,15000,'ok'],
    ['SKU-1005','Sabun Mandi Batang','Kebersihan',55,10,3500,5000,'ok'],
    ['SKU-1006','Air Mineral 600 ml Dus','Minuman',18,20,18000,20000,'low'],
    ['SKU-1007','Kopi Sachet Dus','Minuman',42,10,40000,45000,'ok'],
    ['SKU-1008','Teh Celup Kotak','Minuman',30,10,12000,15000,'ok'],
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
                <h2>Manajemen Stok Barang</h2>
                <p>Master data inventaris real-time</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('d F Y') ?></span>
                <div class="user-chip"><div class="user-avatar">A</div><span class="user-name">Admin</span></div>
            </div>
        </header>
        <div class="content">

            <div class="page-header">
                <div class="page-header-text">
                    <h1>Daftar Barang (SKU)</h1>
                    <p>Total <?= count($items) ?> SKU terdaftar · 3 item perlu perhatian</p>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-secondary">
                        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Ekspor CSV
                    </button>
                    <button class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Tambah Barang
                    </button>
                </div>
            </div>

            <!-- Mini metrics -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--s5);margin-bottom:var(--s8);">
                <div class="metric-card">
                    <div class="metric-top"><span class="metric-label">Total SKU Aktif</span><div class="metric-icon icon-blue"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div></div>
                    <div class="metric-value"><span class="metric-amount"><?= count($items) ?></span></div>
                    <div class="metric-trend">Jenis produk terdaftar</div>
                </div>
                <div class="metric-card">
                    <div class="metric-top"><span class="metric-label">Nilai Total Inventaris</span><div class="metric-icon icon-green"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div></div>
                    <div class="metric-value"><span class="metric-rp">Rp</span><span class="metric-amount">210.750.000</span></div>
                    <div class="metric-trend trend-up"><svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>Nilai aset berjalan</div>
                </div>
                <div class="metric-card">
                    <div class="metric-top"><span class="metric-label">Stok Kritis / Restock</span><div class="metric-icon icon-red"><svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div></div>
                    <div class="metric-value"><span class="metric-amount">3</span></div>
                    <div class="metric-trend trend-down">Item perlu restock segera</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-info">
                        <div class="card-header-title">Master Data Barang</div>
                        <div class="card-header-sub">Data inventaris per <?= date('d F Y') ?></div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode SKU</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th class="col-num">Stok</th>
                                <th class="col-num">Min. Stok</th>
                                <th class="col-num">Harga Beli (Rp)</th>
                                <th class="col-num">Harga Jual (Rp)</th>
                                <th class="col-center">Status</th>
                                <th class="col-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as [$sku,$name,$cat,$stok,$min,$buy,$sell,$s]):
                                $bc = match($s){ 'ok'=>'badge-green','low'=>'badge-amber','critical'=>'badge-red',default=>'badge-gray' };
                                $bl = match($s){ 'ok'=>'Aman','low'=>'Restock','critical'=>'Kritis',default=>'N/A' };
                            ?>
                            <tr>
                                <td class="col-mono"><?= $sku ?></td>
                                <td style="font-weight:500;color:var(--slate-900);"><?= htmlspecialchars($name) ?></td>
                                <td><span class="badge badge-blue"><?= $cat ?></span></td>
                                <td class="col-num"><?= number_format($stok) ?></td>
                                <td class="col-num" style="color:var(--slate-400);"><?= number_format($min) ?></td>
                                <td class="col-num"><?= number_format($buy) ?></td>
                                <td class="col-num"><?= number_format($sell) ?></td>
                                <td class="col-center"><span class="badge <?= $bc ?>"><?= $bl ?></span></td>
                                <td class="col-center">
                                    <div style="display:flex;gap:var(--s2);justify-content:center;">
                                        <button class="btn btn-secondary btn-sm">Edit</button>
                                        <button class="btn btn-danger btn-sm">Hapus</button>
                                    </div>
                                </td>
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

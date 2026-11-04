<?php
session_start();
$page      = 'accounting';
$pageTitle = 'Jurnal & Buku Besar';
$entries = [
    ['30 Sep 2026','JU-00124','Kas','Penerimaan penjualan shift pagi','3.500.000','—'],
    ['','JU-00124','Pendapatan Penjualan','Penerimaan penjualan shift pagi','—','3.500.000'],
    ['30 Sep 2026','JU-00125','Hutang Dagang','Pembayaran Supplier PT Makmur','12.500.000','—'],
    ['','JU-00125','Kas','Pembayaran Supplier PT Makmur','—','12.500.000'],
    ['29 Sep 2026','JU-00126','Biaya Listrik','Biaya listrik & air September','850.000','—'],
    ['','JU-00126','Kas','Biaya listrik & air September','—','850.000'],
    ['29 Sep 2026','JU-00127','Persediaan Barang','Pembelian Beras Premium 100 Sak','6.500.000','—'],
    ['','JU-00127','Kas','Pembelian Beras Premium 100 Sak','—','6.500.000'],
    ['28 Sep 2026','JU-00128','Kas','Penerimaan penjualan shift siang','2.900.000','—'],
    ['','JU-00128','Pendapatan Penjualan','Penerimaan penjualan shift siang','—','2.900.000'],
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
                <h2>Jurnal Umum &amp; Buku Besar</h2>
                <p>Sistem pencatatan akuntansi double-entry</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('d F Y') ?></span>
                <div class="user-chip"><div class="user-avatar">A</div><span class="user-name">Admin</span></div>
            </div>
        </header>
        <div class="content">

            <div class="page-header">
                <div class="page-header-text">
                    <h1>Jurnal Umum</h1>
                    <p>Metode Double-Entry · Periode 1–30 September 2026</p>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-secondary">Buku Besar</button>
                    <button class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Entri Jurnal Baru
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-info">
                        <div class="card-header-title">Daftar Entri Jurnal</div>
                        <div class="card-header-sub">Total Debit = Total Kredit (Balance Check ✓)</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Jurnal</th>
                                <th>Nama Akun</th>
                                <th>Keterangan</th>
                                <th class="col-num">Debit (Rp)</th>
                                <th class="col-num">Kredit (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $prevNo = '';
                            foreach ($entries as [$tgl,$no,$akun,$ket,$db,$cr]):
                                $isFirst = $no !== $prevNo;
                                $prevNo  = $no;
                            ?>
                            <tr style="<?= $isFirst && $tgl ? 'border-top:2px solid var(--slate-200);' : '' ?>">
                                <td style="color:var(--slate-400);"><?= $isFirst ? $tgl : '' ?></td>
                                <td><?= $isFirst ? '<span class="col-mono">'.$no.'</span>' : '' ?></td>
                                <td style="font-weight:500;color:var(--slate-900);<?= $isFirst ? '' : 'padding-left:2rem;' ?>"><?= htmlspecialchars($akun) ?></td>
                                <td style="color:var(--slate-500);"><?= htmlspecialchars($ket) ?></td>
                                <td class="col-num" style="color:var(--slate-900);font-weight:600;"><?= $db ?></td>
                                <td class="col-num" style="color:var(--slate-500);font-weight:500;"><?= $cr ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="font-weight:700;color:var(--slate-700);font-size:var(--text-sm);">TOTAL</td>
                                <td class="col-num" style="font-weight:700;color:var(--slate-900);">26.250.000</td>
                                <td class="col-num" style="font-weight:700;color:var(--slate-900);">26.250.000</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>

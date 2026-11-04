<?php
session_start();
$page      = 'reports';
$pageTitle = 'Laporan Keuangan';
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
                <h2>Laporan Keuangan Otomatis</h2>
                <p>Laba Rugi &amp; Neraca — September 2026</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('d F Y') ?></span>
                <div class="user-chip"><div class="user-avatar">A</div><span class="user-name">Admin</span></div>
            </div>
        </header>
        <div class="content">

            <div class="page-header">
                <div class="page-header-text">
                    <h1>Laporan Keuangan</h1>
                    <p>Periode: 1 – 30 September 2026</p>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-secondary">
                        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Ekspor PDF
                    </button>
                    <button class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Ekspor Excel
                    </button>
                </div>
            </div>

            <!-- Two-column: Laba Rugi | Neraca -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--s8);align-items:start;">

                <!-- ── Laporan Laba Rugi ── -->
                <div>
                    <p style="font-size:var(--text-xs);font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--slate-400);margin-bottom:var(--s4);">Laporan Laba Rugi</p>

                    <div class="report-box" style="margin-bottom:var(--s4);">
                        <div class="report-box-head">Pendapatan</div>
                        <div class="report-row"><span class="report-label">Pendapatan Penjualan</span><span class="report-amount">142.500.000</span></div>
                        <div class="report-row"><span class="report-label">Pendapatan Lain-lain</span><span class="report-amount">1.200.000</span></div>
                        <div class="report-row report-row-total"><span>Total Pendapatan</span><span class="report-amount">Rp 143.700.000</span></div>
                    </div>

                    <div class="report-box" style="margin-bottom:var(--s4);">
                        <div class="report-box-head">Beban Usaha</div>
                        <div class="report-row"><span class="report-label">Harga Pokok Penjualan (HPP)</span><span class="report-amount">72.000.000</span></div>
                        <div class="report-row"><span class="report-label">Biaya Gaji Karyawan</span><span class="report-amount">18.000.000</span></div>
                        <div class="report-row"><span class="report-label">Biaya Listrik &amp; Air</span><span class="report-amount">850.000</span></div>
                        <div class="report-row"><span class="report-label">Biaya Sewa Tempat</span><span class="report-amount">3.500.000</span></div>
                        <div class="report-row"><span class="report-label">Biaya Angkut &amp; Lain-lain</span><span class="report-amount">150.000</span></div>
                        <div class="report-row report-row-total" style="background:var(--red-50);color:var(--red-600);">
                            <span>Total Beban</span>
                            <span class="report-amount" style="color:var(--red-600);">Rp 94.500.000</span>
                        </div>
                    </div>

                    <!-- Laba Bersih highlight box -->
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:var(--s5) var(--s6);background:var(--brand-600);border-radius:var(--r-lg);color:white;">
                        <span style="font-weight:700;font-size:var(--text-base);">LABA BERSIH</span>
                        <span style="font-weight:700;font-size:var(--text-xl);letter-spacing:-.02em;white-space:nowrap;">Rp 49.200.000</span>
                    </div>
                </div>

                <!-- ── Neraca Keuangan ── -->
                <div>
                    <p style="font-size:var(--text-xs);font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--slate-400);margin-bottom:var(--s4);">Neraca (Balance Sheet)</p>

                    <div class="report-box" style="margin-bottom:var(--s4);">
                        <div class="report-box-head">Aset (Harta)</div>
                        <div class="report-row"><span class="report-label">Kas &amp; Setara Kas</span><span class="report-amount">58.300.000</span></div>
                        <div class="report-row"><span class="report-label">Persediaan Barang Dagang</span><span class="report-amount">210.750.000</span></div>
                        <div class="report-row"><span class="report-label">Piutang Dagang</span><span class="report-amount">5.000.000</span></div>
                        <div class="report-row"><span class="report-label">Aset Tetap (Peralatan)</span><span class="report-amount">35.000.000</span></div>
                        <div class="report-row report-row-total"><span>Total Aset</span><span class="report-amount">Rp 309.050.000</span></div>
                    </div>

                    <div class="report-box" style="margin-bottom:var(--s4);">
                        <div class="report-box-head">Kewajiban (Hutang)</div>
                        <div class="report-row"><span class="report-label">Hutang Dagang</span><span class="report-amount">12.500.000</span></div>
                        <div class="report-row"><span class="report-label">Hutang Gaji Karyawan</span><span class="report-amount">18.000.000</span></div>
                        <div class="report-row report-row-total" style="background:var(--red-50);color:var(--red-600);"><span>Total Kewajiban</span><span class="report-amount" style="color:var(--red-600);">Rp 30.500.000</span></div>
                    </div>

                    <div class="report-box" style="margin-bottom:var(--s4);">
                        <div class="report-box-head">Modal (Ekuitas)</div>
                        <div class="report-row"><span class="report-label">Modal Pemilik</span><span class="report-amount">229.350.000</span></div>
                        <div class="report-row"><span class="report-label">Laba Ditahan Sep 2026</span><span class="report-amount">49.200.000</span></div>
                        <div class="report-row report-row-total"><span>Total Modal</span><span class="report-amount">Rp 278.550.000</span></div>
                    </div>

                    <!-- Balance check -->
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:var(--s5) var(--s6);background:var(--slate-900);border-radius:var(--r-lg);color:white;">
                        <span style="font-weight:700;font-size:var(--text-base);">TOTAL PASIVA</span>
                        <span style="font-weight:700;font-size:var(--text-xl);letter-spacing:-.02em;white-space:nowrap;">Rp 309.050.000</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>

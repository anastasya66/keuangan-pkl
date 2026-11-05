<?php

namespace App\Exports;

use Illuminate\Http\Response;

class InventoryReportExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xls) berformat tabel terstruktur.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-stok-persediaan-' . date('Ymd') . '.xls';

        $html = $this->buildHtmlTable();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    protected function buildHtmlTable(): string
    {
        $generatedAt = htmlspecialchars($this->data['generated_at'] ?? date('Y-m-d H:i:s'));
        $totalProducts = number_format($this->data['summary']['total_products'] ?? 0, 0, ',', '.');
        $totalStockUnits = number_format($this->data['summary']['total_stock_units'] ?? 0, 2, ',', '.');
        $totalInventoryValue = number_format($this->data['summary']['total_inventory_value'] ?? 0, 2, ',', '.');

        ob_start();
        ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Stok Persediaan</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body, table { font-family: 'Segoe UI', Calibri, Arial, sans-serif; font-size: 10pt; color: #16323B; }
        .doc-title { font-size: 15pt; font-weight: bold; color: #10262E; text-align: left; }
        .doc-subtitle { font-size: 12pt; font-weight: bold; color: #167A93; text-align: left; }
        .doc-meta { font-size: 10pt; color: #577782; text-align: left; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th { background-color: #27C5E5; color: #FFFFFF; font-weight: bold; border: 1px solid #1A9DB8; padding: 9px 8px; text-align: left; font-size: 10pt; }
        th.center { text-align: center; }
        th.right { text-align: right; }
        td { border: 1px solid #D7EBEF; padding: 6px 8px; vertical-align: middle; }
        td.center { text-align: center; }
        td.right { text-align: right; mso-number-format: "\#\,\#\#0\.00"; }
        td.mono { font-family: monospace; font-weight: bold; }
        .row-grand-total { background-color: #D6F4FA; color: #10262E; font-weight: bold; font-size: 11pt; border-top: 2px solid #27C5E5; border-bottom: 2px solid #27C5E5; }
        .status-normal { background-color: #E8F8F0; color: #1B8751; font-weight: bold; text-align: center; }
        .status-low { background-color: #FEF8EC; color: #B7791F; font-weight: bold; text-align: center; }
        .status-empty { background-color: #FDEBED; color: #C53030; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="10" class="doc-title">FINTOKO — SISTEM MANAJEMEN KEUANGAN &amp; PERSEDIAAN TOKO</td>
        </tr>
        <tr>
            <td colspan="10" class="doc-subtitle">LAPORAN STOK &amp; VALUASI PERSEDIAAN BARANG</td>
        </tr>
        <tr>
            <td colspan="10" class="doc-meta">
                Tanggal Cetak: <?= $generatedAt ?> &bull; Total Item: <?= $totalProducts ?> &bull; Total Fisik: <?= $totalStockUnits ?> unit &bull; Total Nilai: Rp <?= $totalInventoryValue ?>
            </td>
        </tr>
        <tr><td colspan="10" style="border:none; height:15px;"></td></tr>
        <thead>
            <tr>
                <th class="center" style="width: 40px;">No</th>
                <th style="width: 120px;">Kode SKU</th>
                <th style="width: 120px;">Barcode</th>
                <th style="width: 250px;">Nama Produk</th>
                <th style="width: 140px;">Kategori</th>
                <th class="center" style="width: 80px;">Satuan</th>
                <th class="right" style="width: 100px;">Stok Fisik</th>
                <th class="center" style="width: 110px;">Status</th>
                <th class="right" style="width: 140px;">Harga Beli (Rp)</th>
                <th class="right" style="width: 160px;">Nilai Stok (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($this->data['products'])): ?>
                <tr>
                    <td colspan="10" style="text-align: center; color: #8EA6AF; padding: 20px;">Belum ada data barang terdaftar.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($this->data['products'] as $p): 
                    $statusClass = 'status-normal';
                    if (($p['stock'] ?? 0) <= 0) {
                        $statusClass = 'status-empty';
                    } elseif (($p['stock'] ?? 0) <= 10) {
                        $statusClass = 'status-low';
                    }
                ?>
                    <tr>
                        <td class="center"><?= $no++ ?></td>
                        <td class="mono"><?= htmlspecialchars($p['sku']) ?></td>
                        <td style="font-family: monospace;"><?= htmlspecialchars($p['barcode'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['category_name']) ?></td>
                        <td class="center"><?= htmlspecialchars($p['unit_symbol']) ?></td>
                        <td class="right"><?= number_format($p['stock'], 2, ',', '.') ?></td>
                        <td class="<?= $statusClass ?>"><?= htmlspecialchars($p['stock_status_label']) ?></td>
                        <td class="right"><?= number_format($p['purchase_price'], 2, ',', '.') ?></td>
                        <td class="right"><?= number_format($p['inventory_value'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <tr class="row-grand-total">
                <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL PERSEDIAAN:</td>
                <td class="right"><?= $totalStockUnits ?></td>
                <td></td>
                <td></td>
                <td class="right">Rp <?= $totalInventoryValue ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

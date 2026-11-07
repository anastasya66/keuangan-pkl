<?php

namespace App\Exports;

use Illuminate\Http\Response;

class InventoryValuationExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xls) berformat tabel terstruktur untuk Nilai Persediaan.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'nilai-persediaan-' . date('Ymd') . '.xls';

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
        $totalValuation = number_format($this->data['summary']['total_valuation'] ?? 0, 2, ',', '.');
        $potentialRevenue = number_format($this->data['summary']['potential_revenue'] ?? 0, 2, ',', '.');
        $potentialProfit = number_format($this->data['summary']['potential_profit'] ?? 0, 2, ',', '.');

        $products = $this->data['products'] ?? [];

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
                    <x:Name>Nilai Persediaan</x:Name>
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
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="11" class="doc-title">FINTOKO — SISTEM MANAJEMEN KEUANGAN &amp; PERSEDIAAN TOKO</td>
        </tr>
        <tr>
            <td colspan="11" class="doc-subtitle">LAPORAN NILAI PERSEDIAAN BARANG (METODE MOVING WEIGHTED AVERAGE)</td>
        </tr>
        <tr>
            <td colspan="11" class="doc-meta">
                Tanggal Cetak: <?= $generatedAt ?> &bull; Total Item: <?= $totalProducts ?> &bull; Total Stok Fisik: <?= $totalStockUnits ?> unit &bull; Total Nilai Aset: Rp <?= $totalValuation ?>
            </td>
        </tr>
        <tr><td colspan="11" style="border:none; height:15px;"></td></tr>
        <thead>
            <tr>
                <th class="center" style="width: 40px;">No</th>
                <th style="width: 120px;">Kode SKU</th>
                <th style="width: 120px;">Barcode</th>
                <th style="width: 250px;">Nama Produk</th>
                <th style="width: 140px;">Kategori</th>
                <th class="center" style="width: 80px;">Satuan</th>
                <th class="right" style="width: 90px;">Stok Fisik</th>
                <th class="right" style="width: 120px;">HPP Rata-rata</th>
                <th class="right" style="width: 130px;">Total Nilai Aset</th>
                <th class="right" style="width: 120px;">Harga Jual</th>
                <th class="right" style="width: 120px;">Potensi Laba</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($products as $item): 
                $stock = max(0.0, (float) $item->stock);
                $avgCost = (float) $item->effective_cost;
                $sellPrice = (float) $item->selling_price;
                $valTotal = round($stock * $avgCost, 2);
                $profit = round(($stock * $sellPrice) - $valTotal, 2);
            ?>
            <tr>
                <td class="center"><?= $no++ ?></td>
                <td class="mono"><?= htmlspecialchars($item->sku ?? '-') ?></td>
                <td class="mono"><?= htmlspecialchars($item->barcode ?? '-') ?></td>
                <td><?= htmlspecialchars($item->name ?? '-') ?></td>
                <td><?= htmlspecialchars($item->category?->name ?? '-') ?></td>
                <td class="center"><?= htmlspecialchars($item->unit?->symbol ?? 'pcs') ?></td>
                <td class="right"><?= number_format($stock, 2, ',', '.') ?></td>
                <td class="right">Rp <?= number_format($avgCost, 2, ',', '.') ?></td>
                <td class="right">Rp <?= number_format($valTotal, 2, ',', '.') ?></td>
                <td class="right">Rp <?= number_format($sellPrice, 2, ',', '.') ?></td>
                <td class="right">Rp <?= number_format($profit, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="row-grand-total">
                <td colspan="6" class="center">TOTAL KESELURUHAN</td>
                <td class="right"><?= $totalStockUnits ?></td>
                <td class="right">-</td>
                <td class="right">Rp <?= $totalValuation ?></td>
                <td class="right">Rp <?= $potentialRevenue ?></td>
                <td class="right">Rp <?= $potentialProfit ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

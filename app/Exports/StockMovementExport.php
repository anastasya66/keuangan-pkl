<?php

namespace App\Exports;

use Illuminate\Http\Response;

class StockMovementExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xls) berformat tabel terstruktur.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'riwayat-mutasi-persediaan-' . date('Ymd') . '.xls';

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
        $totalMovements = number_format($this->data['summary']['total_movements'] ?? 0, 0, ',', '.');
        $totalStockIn = number_format($this->data['summary']['total_stock_in'] ?? 0, 2, ',', '.');
        $totalStockOut = number_format($this->data['summary']['total_stock_out'] ?? 0, 2, ',', '.');
        $totalAdjustments = number_format($this->data['summary']['total_adjustments'] ?? 0, 0, ',', '.');

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
                    <x:Name>Mutasi Stok</x:Name>
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
        .type-in { background-color: #E8F8F0; color: #1B8751; font-weight: bold; text-align: center; }
        .type-out { background-color: #FDEBED; color: #C53030; font-weight: bold; text-align: center; }
        .type-adj { background-color: #EAF8FA; color: #167A93; font-weight: bold; text-align: center; }
        .row-grand-total { background-color: #D6F4FA; color: #10262E; font-weight: bold; font-size: 11pt; border-top: 2px solid #27C5E5; border-bottom: 2px solid #27C5E5; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="12" class="doc-title">FINTOKO — SISTEM MANAJEMEN KEUANGAN &amp; PERSEDIAAN TOKO</td>
        </tr>
        <tr>
            <td colspan="12" class="doc-subtitle">LAPORAN RIWAYAT MUTASI PERSEDIAAN (STOCK MOVEMENTS)</td>
        </tr>
        <tr>
            <td colspan="12" class="doc-meta">
                Tanggal Cetak: <?= $generatedAt ?> &bull; Total Mutasi: <?= $totalMovements ?> &bull; Masuk (+): <?= $totalStockIn ?> &bull; Keluar (-): <?= $totalStockOut ?> &bull; Penyesuaian: <?= $totalAdjustments ?>
            </td>
        </tr>
        <tr><td colspan="12" style="border:none; height:15px;"></td></tr>
        <thead>
            <tr>
                <th class="center" style="width: 40px;">No</th>
                <th style="width: 150px;">Kode Mutasi</th>
                <th class="center" style="width: 95px;">Tanggal</th>
                <th style="width: 110px;">SKU Barang</th>
                <th style="width: 220px;">Nama Barang</th>
                <th class="center" style="width: 130px;">Jenis Mutasi</th>
                <th class="right" style="width: 100px;">Jumlah (Qty)</th>
                <th class="center" style="width: 70px;">Satuan</th>
                <th class="right" style="width: 95px;">Sebelum</th>
                <th class="right" style="width: 95px;">Sesudah</th>
                <th style="width: 130px;">No. Referensi</th>
                <th style="width: 130px;">Pencatat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($this->data['movements'])): ?>
                <tr>
                    <td colspan="12" style="text-align: center; color: #8EA6AF; padding: 20px;">Belum ada riwayat mutasi stok tercatat.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($this->data['movements'] as $m): 
                    $typeClass = 'type-in';
                    $rawType = $m['movement_type'] ?? '';
                    if (str_contains($rawType, 'out')) {
                        $typeClass = 'type-out';
                    } elseif (str_contains($rawType, 'adj')) {
                        $typeClass = 'type-adj';
                    }
                ?>
                    <tr>
                        <td class="center"><?= $no++ ?></td>
                        <td class="mono"><?= htmlspecialchars($m['movement_code']) ?></td>
                        <td class="center"><?= htmlspecialchars($m['movement_date']) ?></td>
                        <td class="mono"><?= htmlspecialchars($m['product_sku']) ?></td>
                        <td><?= htmlspecialchars($m['product_name']) ?></td>
                        <td class="<?= $typeClass ?>"><?= htmlspecialchars($m['movement_type_label']) ?></td>
                        <td class="right"><?= number_format($m['quantity'], 2, ',', '.') ?></td>
                        <td class="center"><?= htmlspecialchars($m['unit_symbol']) ?></td>
                        <td class="right"><?= number_format($m['stock_before'], 2, ',', '.') ?></td>
                        <td class="right"><?= number_format($m['stock_after'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($m['reference_number'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($m['creator_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

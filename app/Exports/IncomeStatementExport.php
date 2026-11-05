<?php

namespace App\Exports;

use Illuminate\Http\Response;

class IncomeStatementExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xls) berformat tabel terstruktur.
     */
    public function download(?string $filename = null): Response
    {
        $startDate = $this->data['period']['start_date'] ?? date('Y-m-d');
        $endDate = $this->data['period']['end_date'] ?? date('Y-m-d');
        $filename = $filename ?: "laporan-laba-rugi-{$startDate}-sd-{$endDate}.xls";

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
        $periodFormatted = htmlspecialchars($this->data['period']['formatted'] ?? '-');
        $generatedAt = htmlspecialchars($this->data['generated_at'] ?? date('Y-m-d H:i:s'));
        $isProfit = $this->data['is_profit'] ?? true;
        $statusLabel = $isProfit ? 'LABA BERSIH (NET PROFIT)' : 'RUGI BERSIH (NET LOSS)';
        $statusBg = $isProfit ? '#E8F8F0' : '#FDEBED';
        $statusColor = $isProfit ? '#1B8751' : '#C53030';

        $totalRevenue = number_format($this->data['revenues']['total'] ?? 0, 2, ',', '.');
        $totalExpense = number_format($this->data['expenses']['total'] ?? 0, 2, ',', '.');
        $netProfit = number_format($this->data['net_profit'] ?? 0, 2, ',', '.');

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
                    <x:Name>Laba Rugi</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body, table { font-family: 'Segoe UI', Calibri, Arial, sans-serif; font-size: 11pt; color: #16323B; }
        .doc-title { font-size: 15pt; font-weight: bold; color: #10262E; text-align: left; }
        .doc-subtitle { font-size: 12pt; font-weight: bold; color: #167A93; text-align: left; }
        .doc-meta { font-size: 10pt; color: #577782; text-align: left; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th { background-color: #27C5E5; color: #FFFFFF; font-weight: bold; border: 1px solid #1A9DB8; padding: 10px; text-align: left; }
        th.right { text-align: right; }
        td { border: 1px solid #D7EBEF; padding: 7px 10px; vertical-align: middle; }
        td.right { text-align: right; mso-number-format: "\#\,\#\#0\.00"; }
        .section-header { background-color: #EAF8FA; color: #167A93; font-weight: bold; border: 1px solid #D7EBEF; }
        .row-total { background-color: #F3FBFC; font-weight: bold; border-top: 2px solid #27C5E5; border-bottom: 2px solid #27C5E5; }
        .row-grand-total { background-color: <?= $statusBg ?>; color: <?= $statusColor ?>; font-weight: bold; font-size: 12pt; border: 2px solid <?= $statusColor ?>; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="3" class="doc-title">FINTOKO — SISTEM MANAJEMEN KEUANGAN TOKO</td>
        </tr>
        <tr>
            <td colspan="3" class="doc-subtitle">LAPORAN LABA RUGI (INCOME STATEMENT)</td>
        </tr>
        <tr>
            <td colspan="3" class="doc-meta">Periode: <?= $periodFormatted ?> &bull; Tanggal Cetak: <?= $generatedAt ?></td>
        </tr>
        <tr><td colspan="3" style="border:none; height:15px;"></td></tr>
        <thead>
            <tr>
                <th style="width: 140px;">Kode Akun</th>
                <th style="width: 380px;">Nama Rekening Akun</th>
                <th class="right" style="width: 220px;">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- 1. PENDAPATAN -->
            <tr>
                <td colspan="3" class="section-header">1. PENDAPATAN TOKO (REVENUES)</td>
            </tr>
            <?php if (empty($this->data['revenues']['items'])): ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #8EA6AF;">Belum ada catatan pendapatan pada periode ini.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($this->data['revenues']['items'] as $item): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($item['account_code']) ?></td>
                        <td><?= htmlspecialchars($item['account_name']) ?></td>
                        <td class="right"><?= number_format($item['amount'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="row-total">
                <td colspan="2">TOTAL PENDAPATAN</td>
                <td class="right">Rp <?= $totalRevenue ?></td>
            </tr>

            <tr><td colspan="3" style="border:none; height:10px;"></td></tr>

            <!-- 2. BEBAN -->
            <tr>
                <td colspan="3" class="section-header">2. BEBAN OPERASIONAL TOKO (EXPENSES)</td>
            </tr>
            <?php if (empty($this->data['expenses']['items'])): ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #8EA6AF;">Belum ada catatan beban operasional pada periode ini.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($this->data['expenses']['items'] as $item): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($item['account_code']) ?></td>
                        <td><?= htmlspecialchars($item['account_name']) ?></td>
                        <td class="right"><?= number_format($item['amount'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="row-total">
                <td colspan="2">TOTAL BEBAN OPERASIONAL</td>
                <td class="right">Rp <?= $totalExpense ?></td>
            </tr>

            <tr><td colspan="3" style="border:none; height:15px;"></td></tr>

            <!-- 3. LABA / RUGI BERSIH -->
            <tr class="row-grand-total">
                <td colspan="2"><?= $statusLabel ?></td>
                <td class="right">Rp <?= $netProfit ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

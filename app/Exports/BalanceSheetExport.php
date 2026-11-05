<?php

namespace App\Exports;

use Illuminate\Http\Response;

class BalanceSheetExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xls) berformat tabel terstruktur.
     */
    public function download(?string $filename = null): Response
    {
        $asOfDate = $this->data['as_of_date'] ?? date('Y-m-d');
        $filename = $filename ?: "laporan-neraca-per-{$asOfDate}.xls";

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
        $asOfFormatted = htmlspecialchars($this->data['formatted_as_of'] ?? '-');
        $generatedAt = htmlspecialchars($this->data['generated_at'] ?? date('Y-m-d H:i:s'));
        $isBalanced = $this->data['is_balanced'] ?? false;
        $statusLabel = $isBalanced ? 'SEIMBANG (EQUILIBRIUM)' : 'TIDAK SEIMBANG';
        $statusBg = $isBalanced ? '#E8F8F0' : '#FDEBED';
        $statusColor = $isBalanced ? '#1B8751' : '#C53030';

        $totalAssets = number_format($this->data['total_assets'] ?? 0, 2, ',', '.');
        $totalLiabilities = number_format($this->data['total_liabilities'] ?? 0, 2, ',', '.');
        $totalEquity = number_format($this->data['total_equity'] ?? 0, 2, ',', '.');
        $totalLiabAndEquity = number_format($this->data['total_liabilities_and_equity'] ?? 0, 2, ',', '.');
        $difference = number_format($this->data['difference'] ?? 0, 2, ',', '.');
        $currentEarnings = number_format($this->data['equity']['current_earnings'] ?? 0, 2, ',', '.');

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
                    <x:Name>Neraca</x:Name>
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
        .row-grand-total { background-color: #D6F4FA; color: #10262E; font-weight: bold; font-size: 12pt; border: 2px solid #27C5E5; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="3" class="doc-title">FINTOKO — SISTEM MANAJEMEN KEUANGAN TOKO</td>
        </tr>
        <tr>
            <td colspan="3" class="doc-subtitle">LAPORAN NERACA (BALANCE SHEET)</td>
        </tr>
        <tr>
            <td colspan="3" class="doc-meta">
                Posisi per: <?= $asOfFormatted ?> &bull; Status: <span style="color: <?= $statusColor ?>; font-weight: bold;"><?= $statusLabel ?></span> &bull; Cetak: <?= $generatedAt ?>
            </td>
        </tr>
        <tr><td colspan="3" style="border:none; height:15px;"></td></tr>
        <thead>
            <tr>
                <th style="width: 140px;">Kode Akun</th>
                <th style="width: 380px;">Nama Rekening Akun</th>
                <th class="right" style="width: 220px;">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- 1. ASET -->
            <tr>
                <td colspan="3" class="section-header">1. ASET TOKO (ASSETS)</td>
            </tr>
            <?php foreach ($this->data['assets']['items'] as $item): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($item['account_code']) ?></td>
                    <td><?= htmlspecialchars($item['account_name']) ?></td>
                    <td class="right"><?= number_format($item['amount'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="row-total">
                <td colspan="2">TOTAL ASET</td>
                <td class="right">Rp <?= $totalAssets ?></td>
            </tr>

            <tr><td colspan="3" style="border:none; height:10px;"></td></tr>

            <!-- 2. KEWAJIBAN -->
            <tr>
                <td colspan="3" class="section-header">2. KEWAJIBAN / HUTANG TOKO (LIABILITIES)</td>
            </tr>
            <?php if (empty($this->data['liabilities']['items'])): ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #8EA6AF;">Tidak ada kewajiban / hutang aktif.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($this->data['liabilities']['items'] as $item): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($item['account_code']) ?></td>
                        <td><?= htmlspecialchars($item['account_name']) ?></td>
                        <td class="right"><?= number_format($item['amount'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="row-total">
                <td colspan="2">TOTAL KEWAJIBAN</td>
                <td class="right">Rp <?= $totalLiabilities ?></td>
            </tr>

            <tr><td colspan="3" style="border:none; height:10px;"></td></tr>

            <!-- 3. EKUITAS -->
            <tr>
                <td colspan="3" class="section-header">3. EKUITAS / MODAL PEMILIK (EQUITY)</td>
            </tr>
            <?php foreach ($this->data['equity']['items'] as $item): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($item['account_code']) ?></td>
                    <td><?= htmlspecialchars($item['account_name']) ?></td>
                    <td class="right"><?= number_format($item['amount'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td style="font-family: monospace; font-weight: bold;">-</td>
                <td>Laba / (Rugi) Berjalan Kumulatif Toko</td>
                <td class="right"><?= $currentEarnings ?></td>
            </tr>
            <tr class="row-total">
                <td colspan="2">TOTAL EKUITAS</td>
                <td class="right">Rp <?= $totalEquity ?></td>
            </tr>

            <tr><td colspan="3" style="border:none; height:15px;"></td></tr>

            <!-- TOTAL KEWAJIBAN & EKUITAS -->
            <tr class="row-grand-total">
                <td colspan="2">TOTAL KEWAJIBAN DAN EKUITAS</td>
                <td class="right">Rp <?= $totalLiabAndEquity ?></td>
            </tr>
            <tr>
                <td colspan="2" style="font-weight: bold;">SELISIH KESEIMBANGAN (DIFFERENCE)</td>
                <td class="right" style="font-weight: bold; color: <?= $isBalanced ? '#1B8751' : '#C53030' ?>;">
                    Rp <?= $difference ?> (<?= $statusLabel ?>)
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

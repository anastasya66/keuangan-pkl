<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;

class SalesReportExport extends BaseExcelExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xlsx) berformat workbook OpenXML valid.
     */
    public function download(?string $filename = null): Response
    {
        $mode = $this->data['mode'] ?? 'daily';
        $periodFormatted = $this->data['period']['formatted'] ?? date('Y-m-d');
        $defaultFilename = $mode === 'monthly'
            ? 'laporan-penjualan-bulanan-' . ($this->data['period']['year'] ?? date('Y')) . '.xlsx'
            : 'laporan-penjualan-harian-' . date('Y-m-d') . '.xlsx';

        $filename = $filename ?: $defaultFilename;

        $spreadsheet = $this->createSpreadsheet('Laporan Penjualan', 'Laporan Penjualan');
        $sheet = $spreadsheet->getActiveSheet();

        $store = $this->getStoreInfo();
        $generatedAt = $this->data['generated_at'] ?? date('d/m/Y H:i');
        $summary = $this->data['summary'] ?? [];

        // 1. Header Banner Dokumen
        $sheet->setCellValue('A1', mb_strtoupper($store['name']) . ' — SISTEM MANAJEMEN KEUANGAN TOKO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::COLOR_DARK);

        $sheet->setCellValue('A2', 'LAPORAN ANALISIS PENJUALAN, LABA & MARGIN (' . ($mode === 'monthly' ? 'BULANAN' : 'HARIAN') . ')');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::COLOR_PRIMARY);

        $metaText = "Periode: {$periodFormatted} | Tanggal Cetak: {$generatedAt}";
        if (!empty($store['address'])) {
            $metaText .= " | {$store['address']}";
        }
        $sheet->setCellValue('A3', $metaText);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);

        $row = 5;

        // 2. Table Header
        $headers = [
            'A' => 'No',
            'B' => $mode === 'monthly' ? 'Bulan & Tahun' : 'Tanggal Transaksi',
            'C' => $mode === 'monthly' ? 'Keterangan' : 'Hari',
            'D' => 'Jml Transaksi',
            'E' => 'Total Omzet (Rp)',
            'F' => 'Total HPP / Modal (Rp)',
            'G' => 'Laba Kotor (Rp)',
            'H' => 'Margin (%)',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;

        // 3. Table Rows
        $items = $this->data['items'] ?? [];
        $no = 1;

        if (empty($items)) {
            $sheet->setCellValue("A{$row}", 'Tidak ada data transaksi penjualan pada periode ini.');
            $sheet->mergeCells("A{$row}:H{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            foreach ($items as $item) {
                $isZebra = ($no % 2 === 0);
                $bgColor = $isZebra ? 'F9FCFD' : 'FFFFFF';

                $sheet->setCellValueExplicit("A{$row}", (string) $no, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("B{$row}", $item['label'] ?? '', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", $item['sublabel'] ?? '', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", (string) ($item['transaction_count'] ?? 0), DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("E{$row}", (string) ($item['total_revenue'] ?? 0), DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("F{$row}", (string) ($item['total_cogs'] ?? 0), DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("G{$row}", (string) ($item['gross_profit'] ?? 0), DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("H{$row}", number_format((float) ($item['profit_margin'] ?? 0), 2) . '%', DataType::TYPE_STRING);

                // Styling row
                $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgColor);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Formats
                $sheet->getStyle("E{$row}:G{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);

                // Highlight profit color
                $profit = (float) ($item['gross_profit'] ?? 0);
                if ($profit > 0) {
                    $sheet->getStyle("G{$row}")->getFont()->setColor((new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB(self::COLOR_PROFIT));
                } elseif ($profit < 0) {
                    $sheet->getStyle("G{$row}")->getFont()->setColor((new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB(self::COLOR_LOSS));
                }

                $sheet->getStyle("A{$row}:H{$row}")->getBorders()->getAllBorders()->applyFromArray([
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::COLOR_BORDER],
                ]);

                $no++;
                $row++;
            }
        }

        // 4. Summary Row (Total Akumulasi)
        $sheet->setCellValue("A{$row}", 'TOTAL AKUMULASI');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValueExplicit("D{$row}", (string) ($summary['total_transactions'] ?? 0), DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("E{$row}", (string) ($summary['total_revenue'] ?? 0), DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("F{$row}", (string) ($summary['total_cogs'] ?? 0), DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("G{$row}", (string) ($summary['gross_profit'] ?? 0), DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("H{$row}", number_format((float) ($summary['profit_margin'] ?? 0), 2) . '%', DataType::TYPE_STRING);

        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_DARK], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_SECTION]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$row}:G{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);

        $sheet->getStyle("A{$row}:H{$row}")->getBorders()->getAllBorders()->applyFromArray([
            'borderStyle' => Border::BORDER_MEDIUM,
            'color' => ['rgb' => self::COLOR_PRIMARY],
        ]);

        // Auto width columns
        $this->autoFitColumns($sheet, 'H');

        return $this->streamXlsx($spreadsheet, $filename);
    }
}


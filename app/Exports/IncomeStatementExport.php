<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;

class IncomeStatementExport extends BaseExcelExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xlsx) berformat workbook OpenXML valid.
     */
    public function download(?string $filename = null): Response
    {
        $startDate = $this->data['period']['start_date'] ?? date('Y-m-d');
        $endDate = $this->data['period']['end_date'] ?? date('Y-m-d');
        $filename = $filename ?: "laporan-laba-rugi-{$startDate}-sd-{$endDate}.xlsx";

        $spreadsheet = $this->createSpreadsheet('Laporan Laba Rugi', 'Laba Rugi');
        $sheet = $spreadsheet->getActiveSheet();

        $store = $this->getStoreInfo();
        $periodFormatted = $this->data['period']['formatted'] ?? "{$startDate} s/d {$endDate}";
        $generatedAt = $this->data['generated_at'] ?? date('d/m/Y H:i');
        $isProfit = $this->data['is_profit'] ?? true;
        $netProfit = (float) ($this->data['net_profit'] ?? 0);
        $totalRevenue = (float) ($this->data['revenues']['total'] ?? 0);
        $totalExpense = (float) ($this->data['expenses']['total'] ?? 0);

        // 1. Header Banner Dokumen
        $sheet->setCellValue('A1', mb_strtoupper($store['name']) . ' — SISTEM MANAJEMEN KEUANGAN TOKO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::COLOR_DARK);

        $sheet->setCellValue('A2', 'LAPORAN LABA RUGI (INCOME STATEMENT)');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::COLOR_PRIMARY);

        $metaText = "Periode: {$periodFormatted} | Tanggal Cetak: {$generatedAt}";
        if (!empty($store['address'])) {
            $metaText .= " | {$store['address']}";
        }
        $sheet->setCellValue('A3', $metaText);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);

        $row = 5;

        // 2. Section 1: Pendapatan (Revenue)
        $sheet->setCellValue("A{$row}", '1. PENDAPATAN (REVENUE)');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(self::COLOR_PRIMARY);
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_SECTION);
        $row++;

        // Table Header
        $sheet->setCellValue("A{$row}", 'Kode Akun');
        $sheet->setCellValue("B{$row}", 'Nama Rekening Akun');
        $sheet->setCellValue("C{$row}", 'Nominal (Rp)');
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $revenues = $this->data['revenues']['items'] ?? [];
        if (empty($revenues)) {
            $sheet->setCellValue("A{$row}", 'Tidak ada transaksi pendapatan pada periode ini');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            foreach ($revenues as $item) {
                $sheet->setCellValueExplicit("A{$row}", (string) ($item['account_code'] ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValue("B{$row}", (string) ($item['account_name'] ?? ''));
                $sheet->setCellValue("C{$row}", (float) ($item['amount'] ?? 0));

                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
                $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
                $row++;
            }
        }

        // Subtotal Pendapatan
        $sheet->setCellValue("A{$row}", 'TOTAL PENDAPATAN');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $totalRevenue);
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_PROFIT], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_LIGHT]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
        $row += 2;

        // 3. Section 2: Beban Operasional (Expenses)
        $sheet->setCellValue("A{$row}", '2. BEBAN OPERASIONAL (EXPENSES)');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(self::COLOR_PRIMARY);
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_SECTION);
        $row++;

        // Table Header
        $sheet->setCellValue("A{$row}", 'Kode Akun');
        $sheet->setCellValue("B{$row}", 'Nama Rekening Akun');
        $sheet->setCellValue("C{$row}", 'Nominal (Rp)');
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $expenses = $this->data['expenses']['items'] ?? [];
        if (empty($expenses)) {
            $sheet->setCellValue("A{$row}", 'Tidak ada transaksi beban pada periode ini');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            foreach ($expenses as $item) {
                $sheet->setCellValueExplicit("A{$row}", (string) ($item['account_code'] ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValue("B{$row}", (string) ($item['account_name'] ?? ''));
                $sheet->setCellValue("C{$row}", (float) ($item['amount'] ?? 0));

                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
                $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
                $row++;
            }
        }

        // Subtotal Beban
        $sheet->setCellValue("A{$row}", 'TOTAL BEBAN');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $totalExpense);
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_LOSS], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_LIGHT]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
        $row += 2;

        // 4. Grand Total: Laba / Rugi Bersih
        $labelProfit = $isProfit ? 'LABA BERSIH PERIODE (NET PROFIT)' : 'RUGI BERSIH PERIODE (NET LOSS)';
        $sheet->setCellValue("A{$row}", $labelProfit);
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $netProfit);

        $colorProfit = $isProfit ? self::COLOR_PROFIT : self::COLOR_LOSS;
        $bgProfit = $isProfit ? self::COLOR_PROFIT_BG : self::COLOR_LOSS_BG;

        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $colorProfit]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgProfit]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $colorProfit]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => $colorProfit]],
                'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $colorProfit]],
                'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $colorProfit]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);

        // Auto-fit column widths
        $this->autoFitColumns($sheet, 'C');

        return $this->streamXlsx($spreadsheet, $filename);
    }
}

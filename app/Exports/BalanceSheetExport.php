<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;

class BalanceSheetExport extends BaseExcelExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xlsx) berformat workbook OpenXML valid.
     */
    public function download(?string $filename = null): Response
    {
        $asOfDate = $this->data['as_of_date'] ?? date('Y-m-d');
        $filename = $filename ?: "laporan-neraca-per-{$asOfDate}.xlsx";

        $spreadsheet = $this->createSpreadsheet('Laporan Neraca', 'Neraca');
        $sheet = $spreadsheet->getActiveSheet();

        $store = $this->getStoreInfo();
        $asOfFormatted = $this->data['formatted_as_of'] ?? $asOfDate;
        $generatedAt = $this->data['generated_at'] ?? date('d/m/Y H:i');
        $isBalanced = $this->data['is_balanced'] ?? false;
        $totalAssets = (float) ($this->data['total_assets'] ?? 0);
        $totalLiabilities = (float) ($this->data['total_liabilities'] ?? 0);
        $totalEquity = (float) ($this->data['total_equity'] ?? 0);
        $totalLiabAndEquity = (float) ($this->data['total_liabilities_and_equity'] ?? 0);
        $currentEarnings = (float) ($this->data['equity']['current_earnings'] ?? 0);
        $difference = (float) ($this->data['difference'] ?? 0);

        // 1. Header Banner
        $sheet->setCellValue('A1', mb_strtoupper($store['name']) . ' — SISTEM MANAJEMEN KEUANGAN TOKO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::COLOR_DARK);

        $sheet->setCellValue('A2', 'LAPORAN NERACA (BALANCE SHEET)');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::COLOR_PRIMARY);

        $statusText = $isBalanced ? 'SEIMBANG (EQUILIBRIUM)' : 'TIDAK SEIMBANG';
        $metaText = "Posisi per: {$asOfFormatted} | Status: {$statusText} | Tanggal Cetak: {$generatedAt}";
        if (!empty($store['address'])) {
            $metaText .= " | {$store['address']}";
        }
        $sheet->setCellValue('A3', $metaText);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);

        $row = 5;

        // 2. Section 1: Aset (Assets)
        $sheet->setCellValue("A{$row}", '1. ASET (ASSETS)');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(self::COLOR_PRIMARY);
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_SECTION);
        $row++;

        // Table Header
        $sheet->setCellValue("A{$row}", 'Kode Akun');
        $sheet->setCellValue("B{$row}", 'Nama Rekening Aset');
        $sheet->setCellValue("C{$row}", 'Saldo (Rp)');
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $assets = $this->data['assets']['items'] ?? [];
        if (empty($assets)) {
            $sheet->setCellValue("A{$row}", 'Tidak ada data akun aset');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            foreach ($assets as $item) {
                $sheet->setCellValueExplicit("A{$row}", (string) ($item['account_code'] ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValue("B{$row}", (string) ($item['account_name'] ?? ''));
                $sheet->setCellValue("C{$row}", (float) ($item['amount'] ?? 0));

                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
                $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
                $row++;
            }
        }

        // Subtotal Aset
        $sheet->setCellValue("A{$row}", 'TOTAL ASET');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $totalAssets);
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_PRIMARY], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_LIGHT]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
        $row += 2;

        // 3. Section 2: Kewajiban (Liabilities)
        $sheet->setCellValue("A{$row}", '2. KEWAJIBAN (LIABILITIES)');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(self::COLOR_PRIMARY);
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_SECTION);
        $row++;

        // Table Header
        $sheet->setCellValue("A{$row}", 'Kode Akun');
        $sheet->setCellValue("B{$row}", 'Nama Rekening Kewajiban');
        $sheet->setCellValue("C{$row}", 'Saldo (Rp)');
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $liabilities = $this->data['liabilities']['items'] ?? [];
        if (empty($liabilities)) {
            $sheet->setCellValue("A{$row}", 'Tidak ada data akun kewajiban / hutang');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            foreach ($liabilities as $item) {
                $sheet->setCellValueExplicit("A{$row}", (string) ($item['account_code'] ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValue("B{$row}", (string) ($item['account_name'] ?? ''));
                $sheet->setCellValue("C{$row}", (float) ($item['amount'] ?? 0));

                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
                $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
                $row++;
            }
        }

        // Subtotal Kewajiban
        $sheet->setCellValue("A{$row}", 'TOTAL KEWAJIBAN');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $totalLiabilities);
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'D97706'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_LIGHT]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
        $row += 2;

        // 4. Section 3: Ekuitas / Modal (Equity)
        $sheet->setCellValue("A{$row}", '3. EKUITAS / MODAL (EQUITY)');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(self::COLOR_PRIMARY);
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_SECTION);
        $row++;

        // Table Header
        $sheet->setCellValue("A{$row}", 'Kode Akun');
        $sheet->setCellValue("B{$row}", 'Komponen Ekuitas');
        $sheet->setCellValue("C{$row}", 'Saldo (Rp)');
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $equityItems = $this->data['equity']['items'] ?? [];
        foreach ($equityItems as $item) {
            $sheet->setCellValueExplicit("A{$row}", (string) ($item['account_code'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("B{$row}", (string) ($item['account_name'] ?? ''));
            $sheet->setCellValue("C{$row}", (float) ($item['amount'] ?? 0));

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
            $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
            $row++;
        }

        // Laba / (Rugi) Berjalan Kumulatif
        $sheet->setCellValueExplicit("A{$row}", '-', DataType::TYPE_STRING);
        $sheet->setCellValue("B{$row}", 'Laba / (Rugi) Berjalan Kumulatif');
        $sheet->setCellValue("C{$row}", $currentEarnings);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}")->getFont()->setItalic(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
        $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
        $row++;

        // Subtotal Ekuitas
        $sheet->setCellValue("A{$row}", 'TOTAL EKUITAS');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $totalEquity);
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '7C3AED'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_LIGHT]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
        $row += 2;

        // 5. Grand Total: Total Kewajiban & Ekuitas
        $sheet->setCellValue("A{$row}", 'TOTAL KEWAJIBAN & EKUITAS');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $totalLiabAndEquity);
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::COLOR_DARK]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_SECTION]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
        $row++;

        // 6. Baris Status Keseimbangan Neraca
        $statusLabel = $isBalanced ? 'STATUS: SEIMBANG (EQUILIBRIUM)' : "STATUS: TIDAK SEIMBANG (SELISIH: Rp " . number_format($difference, 2, ',', '.') . ")";
        $sheet->setCellValue("A{$row}", $statusLabel);
        $sheet->mergeCells("A{$row}:C{$row}");

        $colorStatus = $isBalanced ? self::COLOR_PROFIT : self::COLOR_LOSS;
        $bgStatus = $isBalanced ? self::COLOR_PROFIT_BG : self::COLOR_LOSS_BG;

        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $colorStatus]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgStatus]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $colorStatus]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => $colorStatus]],
            ],
        ]);

        // Auto-fit column widths
        $this->autoFitColumns($sheet, 'C');

        return $this->streamXlsx($spreadsheet, $filename);
    }
}

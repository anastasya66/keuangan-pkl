<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;

class StockMovementExport extends BaseExcelExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xlsx) berformat workbook OpenXML valid.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'riwayat-mutasi-persediaan-' . date('Ymd') . '.xlsx';

        $spreadsheet = $this->createSpreadsheet('Laporan Riwayat Mutasi Persediaan', 'Mutasi Stok');
        $sheet = $spreadsheet->getActiveSheet();

        $store = $this->getStoreInfo();
        $generatedAt = $this->data['generated_at'] ?? date('d/m/Y H:i');
        $totalMovements = (int) ($this->data['summary']['total_movements'] ?? 0);
        $totalStockIn = (float) ($this->data['summary']['total_stock_in'] ?? 0);
        $totalStockOut = (float) ($this->data['summary']['total_stock_out'] ?? 0);
        $totalAdjustments = (float) ($this->data['summary']['total_adjustments'] ?? 0);

        // 1. Header Banner
        $sheet->setCellValue('A1', mb_strtoupper($store['name']) . ' — SISTEM MANAJEMEN KEUANGAN & PERSEDIAAN TOKO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::COLOR_DARK);

        $sheet->setCellValue('A2', 'LAPORAN RIWAYAT MUTASI PERSEDIAAN (STOCK MOVEMENTS)');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::COLOR_PRIMARY);

        $metaText = "Tanggal Cetak: {$generatedAt} | Total Transaksi: {$totalMovements} catatan | Total Masuk (+): " . number_format($totalStockIn, 2, ',', '.') . " unit | Total Keluar (-): " . number_format($totalStockOut, 2, ',', '.') . " unit | Penyesuaian: " . number_format($totalAdjustments, 2, ',', '.');
        if (!empty($store['address'])) {
            $metaText .= " | {$store['address']}";
        }
        $sheet->setCellValue('A3', $metaText);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);

        $row = 5;

        // Table Headers
        $headers = [
            'A' => 'No',
            'B' => 'Tanggal & Waktu',
            'C' => 'Kode Mutasi',
            'D' => 'No. Referensi',
            'E' => 'SKU Produk',
            'F' => 'Nama Produk',
            'G' => 'Kategori',
            'H' => 'Jenis Mutasi',
            'I' => 'Masuk (+)',
            'J' => 'Keluar (-)',
            'K' => 'Satuan',
            'L' => 'Saldo Sebelum',
            'M' => 'Saldo Sesudah',
            'N' => 'Keterangan / Alasan',
            'O' => 'Petugas',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("L{$row}:M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("N{$row}:O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $row++;

        $movements = $this->data['movements'] ?? [];
        if (empty($movements)) {
            $sheet->setCellValue("A{$row}", 'Tidak ada data riwayat mutasi persediaan');
            $sheet->mergeCells("A{$row}:O{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            $calcIn = 0;
            $calcOut = 0;

            foreach ($movements as $idx => $m) {
                $code = (string) ($m['movement_code'] ?? $m['code'] ?? '');
                $ref = (string) ($m['reference_number'] ?? $m['ref'] ?? '');
                $dateFormatted = (string) ($m['date'] ?? $m['date_formatted'] ?? $m['movement_date'] ?? '');
                $sku = (string) ($m['product_sku'] ?? $m['sku'] ?? '');
                $productName = (string) ($m['product_name'] ?? '');
                $category = (string) ($m['category_name'] ?? $m['category'] ?? '-');
                $typeLabel = (string) ($m['movement_type_label'] ?? $m['type_label'] ?? 'Mutasi');
                $rawType = (string) ($m['movement_type'] ?? $m['type'] ?? '');
                $qty = (float) ($m['quantity'] ?? 0);
                $unit = (string) ($m['unit_symbol'] ?? $m['unit'] ?? 'unit');
                $stockBefore = isset($m['stock_before']) ? (float) $m['stock_before'] : null;
                $stockAfter = isset($m['stock_after']) ? (float) $m['stock_after'] : null;
                $desc = (string) ($m['description'] ?? $m['notes'] ?? '-');
                $creator = (string) ($m['creator_name'] ?? $m['creator'] ?? 'Administrator');

                $isIn = in_array($rawType, ['stock_in', 'purchase', 'initial_stock', 'sale_return']) || ($m['type_category'] ?? '') === 'in';
                $isOut = in_array($rawType, ['stock_out', 'sale', 'purchase_reversal', 'purchase_return']) || ($m['type_category'] ?? '') === 'out';

                $qtyIn = $isIn ? $qty : 0;
                $qtyOut = $isOut ? $qty : 0;
                $calcIn += $qtyIn;
                $calcOut += $qtyOut;

                $sheet->setCellValue("A{$row}", $idx + 1);
                $sheet->setCellValue("B{$row}", $dateFormatted);
                $sheet->setCellValueExplicit("C{$row}", $code, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", $ref, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$row}", $sku, DataType::TYPE_STRING);
                $sheet->setCellValue("F{$row}", $productName);
                $sheet->setCellValue("G{$row}", $category);
                $sheet->setCellValue("H{$row}", $typeLabel);
                $sheet->setCellValue("I{$row}", $qtyIn > 0 ? $qtyIn : 0);
                $sheet->setCellValue("J{$row}", $qtyOut > 0 ? $qtyOut : 0);
                $sheet->setCellValue("K{$row}", $unit);
                $sheet->setCellValue("L{$row}", $stockBefore !== null ? $stockBefore : 0);
                $sheet->setCellValue("M{$row}", $stockAfter !== null ? $stockAfter : 0);
                $sheet->setCellValue("N{$row}", $desc);
                $sheet->setCellValue("O{$row}", $creator);

                // Alignments
                $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("I{$row}:J{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_QTY);
                $sheet->getStyle("L{$row}:M{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_QTY);

                // Type label text color
                if ($isIn) {
                    $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB(self::COLOR_PROFIT);
                    $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB(self::COLOR_PROFIT);
                } elseif ($isOut) {
                    $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB(self::COLOR_LOSS);
                    $sheet->getStyle("J{$row}")->getFont()->getColor()->setRGB(self::COLOR_LOSS);
                } else {
                    $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB(self::COLOR_PRIMARY);
                }

                $sheet->getStyle("A{$row}:O{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
                $row++;
            }

            if ($totalStockIn <= 0) $totalStockIn = $calcIn;
            if ($totalStockOut <= 0) $totalStockOut = $calcOut;
        }

        // Summary Row
        $sheet->setCellValue("A{$row}", 'GRAND TOTAL');
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->setCellValue("I{$row}", $totalStockIn);
        $sheet->setCellValue("J{$row}", $totalStockOut);
        $sheet->setCellValue("K{$row}", '');
        $sheet->setCellValue("L{$row}", '');
        $sheet->setCellValue("M{$row}", '');
        $sheet->setCellValue("N{$row}", '');
        $sheet->setCellValue("O{$row}", '');

        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_DARK], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_SECTION]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$row}:J{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_QTY);

        // Auto-fit column widths
        $this->autoFitColumns($sheet, 'O');

        return $this->streamXlsx($spreadsheet, $filename);
    }
}

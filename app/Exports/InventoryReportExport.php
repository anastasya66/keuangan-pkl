<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;

class InventoryReportExport extends BaseExcelExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xlsx) berformat workbook OpenXML valid.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-stok-persediaan-' . date('Ymd') . '.xlsx';

        $spreadsheet = $this->createSpreadsheet('Laporan Stok Persediaan', 'Stok Persediaan');
        $sheet = $spreadsheet->getActiveSheet();

        $store = $this->getStoreInfo();
        $generatedAt = $this->data['generated_at'] ?? date('d/m/Y H:i');
        $totalProducts = (int) ($this->data['summary']['total_products'] ?? 0);
        $totalStockUnits = (float) ($this->data['summary']['total_stock_units'] ?? $this->data['summary']['total_units'] ?? 0);
        $totalValuation = (float) ($this->data['summary']['total_inventory_value'] ?? $this->data['summary']['total_valuation'] ?? 0);
        $totalIn = (float) ($this->data['summary']['total_in'] ?? 0);
        $totalOut = (float) ($this->data['summary']['total_out'] ?? 0);

        // 1. Header Banner
        $sheet->setCellValue('A1', mb_strtoupper($store['name']) . ' — SISTEM MANAJEMEN KEUANGAN & PERSEDIAAN TOKO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::COLOR_DARK);

        $sheet->setCellValue('A2', 'LAPORAN SISA STOK & NILAI PERSEDIAAN TOKO');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::COLOR_PRIMARY);

        $metaText = "Tanggal Cetak: {$generatedAt} | Total Produk: {$totalProducts} item | Total Sisa Fisik: " . number_format($totalStockUnits, 2, ',', '.') . " unit | Total Nilai Aset: Rp " . number_format($totalValuation, 2, ',', '.');
        if (!empty($store['address'])) {
            $metaText .= " | {$store['address']}";
        }
        $sheet->setCellValue('A3', $metaText);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);

        $row = 5;

        // Table Headers
        $headers = [
            'A' => 'No',
            'B' => 'Kode / SKU',
            'C' => 'Barcode',
            'D' => 'Nama Produk',
            'E' => 'Kategori',
            'F' => 'Satuan',
            'G' => 'Harga Beli (HPP)',
            'H' => 'Harga Jual',
            'I' => 'Masuk (+)',
            'J' => 'Keluar (-)',
            'K' => 'Sisa Stok',
            'L' => 'Nilai Persediaan (Rp)',
            'M' => 'Status Stok',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $products = $this->data['products'] ?? $this->data['items'] ?? [];
        if (empty($products)) {
            $sheet->setCellValue("A{$row}", 'Tidak ada data produk persediaan');
            $sheet->mergeCells("A{$row}:M{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            $calcTotalIn = 0;
            $calcTotalOut = 0;
            $calcTotalStock = 0;
            $calcTotalVal = 0;

            foreach ($products as $idx => $p) {
                // Cast model atau array
                $sku = (string) (is_array($p) ? ($p['sku'] ?? '') : $p->sku);
                $barcode = (string) (is_array($p) ? ($p['barcode'] ?? '') : $p->barcode);
                $name = (string) (is_array($p) ? ($p['name'] ?? '') : $p->name);
                $category = (string) (is_array($p) ? ($p['category'] ?? $p['category_name'] ?? '-') : ($p->category?->name ?? '-'));
                $unit = (string) (is_array($p) ? ($p['unit'] ?? $p['unit_symbol'] ?? 'unit') : ($p->unit?->symbol ?? 'unit'));
                $purchasePrice = (float) (is_array($p) ? ($p['purchase_price'] ?? $p['average_cost'] ?? 0) : ($p->purchase_price ?? 0));
                $sellingPrice = (float) (is_array($p) ? ($p['selling_price'] ?? 0) : ($p->selling_price ?? 0));
                $qtyIn = (float) (is_array($p) ? ($p['total_in'] ?? 0) : 0);
                $qtyOut = (float) (is_array($p) ? ($p['total_out'] ?? 0) : 0);
                $stock = (float) (is_array($p) ? ($p['stock'] ?? 0) : ($p->stock ?? 0));
                $invValue = (float) (is_array($p) ? ($p['inventory_value'] ?? $p['valuation'] ?? ($stock * $purchasePrice)) : ($stock * $purchasePrice));
                $statusLabel = (string) (is_array($p) ? ($p['status_label'] ?? ($stock <= 0 ? 'Habis' : ($stock <= 10 ? 'Menipis' : 'Tersedia'))) : ($stock <= 0 ? 'Habis' : ($stock <= 10 ? 'Menipis' : 'Tersedia')));

                $calcTotalIn += $qtyIn;
                $calcTotalOut += $qtyOut;
                $calcTotalStock += $stock;
                $calcTotalVal += $invValue;

                $sheet->setCellValue("A{$row}", $idx + 1);
                $sheet->setCellValueExplicit("B{$row}", $sku, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", $barcode, DataType::TYPE_STRING);
                $sheet->setCellValue("D{$row}", $name);
                $sheet->setCellValue("E{$row}", $category);
                $sheet->setCellValue("F{$row}", $unit);
                $sheet->setCellValue("G{$row}", $purchasePrice);
                $sheet->setCellValue("H{$row}", $sellingPrice);
                $sheet->setCellValue("I{$row}", $qtyIn);
                $sheet->setCellValue("J{$row}", $qtyOut);
                $sheet->setCellValue("K{$row}", $stock);
                $sheet->setCellValue("L{$row}", $invValue);
                $sheet->setCellValue("M{$row}", $statusLabel);

                // Alignments & Formats
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$row}:H{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);
                $sheet->getStyle("I{$row}:K{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_QTY);
                $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);

                // Status coloring
                if ($stock <= 0) {
                    $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB(self::COLOR_LOSS);
                } elseif ($stock <= 10) {
                    $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB('D97706');
                } else {
                    $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB(self::COLOR_PROFIT);
                }

                $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
                $row++;
            }

            if ($totalIn <= 0) $totalIn = $calcTotalIn;
            if ($totalOut <= 0) $totalOut = $calcTotalOut;
            if ($totalStockUnits <= 0) $totalStockUnits = $calcTotalStock;
            if ($totalValuation <= 0) $totalValuation = $calcTotalVal;
        }

        // Summary Row
        $sheet->setCellValue("A{$row}", 'GRAND TOTAL');
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->setCellValue("I{$row}", $totalIn);
        $sheet->setCellValue("J{$row}", $totalOut);
        $sheet->setCellValue("K{$row}", $totalStockUnits);
        $sheet->setCellValue("L{$row}", $totalValuation);
        $sheet->setCellValue("M{$row}", '');

        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_DARK], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_SECTION]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            ],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$row}:K{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_QTY);
        $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);

        // Auto-fit column widths
        $this->autoFitColumns($sheet, 'M');

        return $this->streamXlsx($spreadsheet, $filename);
    }
}

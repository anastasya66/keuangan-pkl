<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;

class InventoryValuationExport extends BaseExcelExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xlsx) berformat workbook OpenXML valid.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'nilai-persediaan-' . date('Ymd-His') . '.xlsx';

        $spreadsheet = $this->createSpreadsheet('Laporan Nilai Persediaan', 'Nilai Persediaan');
        $sheet = $spreadsheet->getActiveSheet();

        $store = $this->getStoreInfo();
        $generatedAt = $this->data['generated_at'] ?? date('d/m/Y H:i');
        $totalProducts = (int) ($this->data['summary']['total_products'] ?? 0);
        $totalStockUnits = (float) ($this->data['summary']['total_stock_units'] ?? 0);
        $totalValuation = (float) ($this->data['summary']['total_valuation'] ?? 0);
        $potentialRevenue = (float) ($this->data['summary']['potential_revenue'] ?? 0);
        $potentialProfit = (float) ($this->data['summary']['potential_profit'] ?? 0);

        // 1. Header Banner
        $sheet->setCellValue('A1', mb_strtoupper($store['name']) . ' — SISTEM MANAJEMEN KEUANGAN & PERSEDIAAN TOKO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::COLOR_DARK);

        $sheet->setCellValue('A2', 'LAPORAN NILAI PERSEDIAAN BARANG (MOVING WEIGHTED AVERAGE)');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::COLOR_PRIMARY);

        $metaText = "Tanggal Cetak: {$generatedAt} | Metode: Moving Weighted Average | Total Produk: {$totalProducts} jenis | Total Nilai Aset: Rp " . number_format($totalValuation, 2, ',', '.') . " | Total Fisik: " . number_format($totalStockUnits, 2, ',', '.') . " unit";
        if (!empty($store['address'])) {
            $metaText .= " | {$store['address']}";
        }
        $sheet->setCellValue('A3', $metaText);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);

        $row = 5;

        // Table Headers
        $headers = [
            'A' => 'No',
            'B' => 'Kode SKU',
            'C' => 'Barcode',
            'D' => 'Nama Produk',
            'E' => 'Kategori',
            'F' => 'Stok Fisik',
            'G' => 'Satuan',
            'H' => 'HPP Rata-rata (Rp)',
            'I' => 'Harga Jual (Rp)',
            'J' => 'Total Nilai Aset (Rp)',
            'K' => 'Potensi Omzet (Rp)',
            'L' => 'Potensi Laba Kotor (Rp)',
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
        $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $products = $this->data['products'] ?? [];
        if (empty($products)) {
            $sheet->setCellValue("A{$row}", 'Tidak ada data produk');
            $sheet->mergeCells("A{$row}:M{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        } else {
            $calcStock = 0;
            $calcVal = 0;
            $calcRev = 0;
            $calcProfit = 0;

            foreach ($products as $idx => $p) {
                // Support baik Eloquent Model Product maupun Associative Array
                $sku = (string) (is_array($p) ? ($p['sku'] ?? '') : $p->sku);
                $barcode = (string) (is_array($p) ? ($p['barcode'] ?? '') : $p->barcode);
                $name = (string) (is_array($p) ? ($p['name'] ?? '') : $p->name);
                $category = (string) (is_array($p) ? ($p['category'] ?? $p['category_name'] ?? '-') : ($p->category?->name ?? '-'));
                $unit = (string) (is_array($p) ? ($p['unit'] ?? $p['unit_symbol'] ?? 'unit') : ($p->unit?->symbol ?? 'unit'));
                $stock = max(0.0, (float) (is_array($p) ? ($p['stock'] ?? 0) : $p->stock));

                $cost = (float) (is_array($p)
                    ? ($p['average_cost'] ?? $p['purchase_price'] ?? 0)
                    : ($p->effective_cost ?? $p->average_cost ?? $p->purchase_price ?? 0));

                $price = (float) (is_array($p) ? ($p['selling_price'] ?? 0) : $p->selling_price);
                $totalVal = round($stock * $cost, 2);
                $rev = round($stock * $price, 2);
                $profit = round($rev - $totalVal, 2);

                $calcStock += $stock;
                $calcVal += $totalVal;
                $calcRev += $rev;
                $calcProfit += $profit;

                $minStock = (float) (is_array($p) ? ($p['min_stock'] ?? 5) : ($p->min_stock ?? 5));
                if ($stock <= 0) {
                    $status = 'Habis';
                } elseif ($stock <= $minStock) {
                    $status = 'Menipis';
                } else {
                    $status = 'Tersedia';
                }

                $sheet->setCellValue("A{$row}", $idx + 1);
                $sheet->setCellValueExplicit("B{$row}", $sku, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", $barcode, DataType::TYPE_STRING);
                $sheet->setCellValue("D{$row}", $name);
                $sheet->setCellValue("E{$row}", $category);
                $sheet->setCellValue("F{$row}", $stock);
                $sheet->setCellValue("G{$row}", $unit);
                $sheet->setCellValue("H{$row}", $cost);
                $sheet->setCellValue("I{$row}", $price);
                $sheet->setCellValue("J{$row}", $totalVal);
                $sheet->setCellValue("K{$row}", $rev);
                $sheet->setCellValue("L{$row}", $profit);
                $sheet->setCellValue("M{$row}", $status);

                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_QTY);
                $sheet->getStyle("H{$row}:L{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);

                if ($status === 'Habis') {
                    $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB(self::COLOR_LOSS);
                } elseif ($status === 'Menipis') {
                    $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB('D97706');
                } else {
                    $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB(self::COLOR_PROFIT);
                }

                $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
                $row++;
            }

            if ($totalStockUnits <= 0) $totalStockUnits = $calcStock;
            if ($totalValuation <= 0) $totalValuation = $calcVal;
            if ($potentialRevenue <= 0) $potentialRevenue = $calcRev;
            if ($potentialProfit <= 0) $potentialProfit = $calcProfit;
        }

        // Summary Row
        $sheet->setCellValue("A{$row}", 'GRAND TOTAL');
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("F{$row}", $totalStockUnits);
        $sheet->setCellValue("G{$row}", '');
        $sheet->setCellValue("H{$row}", '');
        $sheet->setCellValue("I{$row}", '');
        $sheet->setCellValue("J{$row}", $totalValuation);
        $sheet->setCellValue("K{$row}", $potentialRevenue);
        $sheet->setCellValue("L{$row}", $potentialProfit);
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
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_QTY);
        $sheet->getStyle("J{$row}:L{$row}")->getNumberFormat()->setFormatCode(self::FORMAT_CURRENCY);

        // Auto-fit column widths
        $this->autoFitColumns($sheet, 'M');

        return $this->streamXlsx($spreadsheet, $filename);
    }
}

<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;

class ConsignmentExport extends BaseExcelExport
{
    public function __construct(
        protected array $data
    ) {}

    /**
     * Generate response file Excel (.xlsx) untuk laporan penjualan & bagi hasil konsinyasi.
     */
    public function download(?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-konsinyasi-' . date('Ymd') . '.xlsx';

        $spreadsheet = $this->createSpreadsheet('Laporan Konsinyasi Toko', 'Konsinyasi');
        $sheet = $spreadsheet->getActiveSheet();

        $store = $this->getStoreInfo();
        $generatedAt = date('d/m/Y H:i');
        $summary = $this->data['summary'] ?? [];

        // 1. Header Banner
        $sheet->setCellValue('A1', mb_strtoupper($store['name']) . ' — SISTEM MANAJEMEN KEUANGAN & PERSEDIAAN TOKO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::COLOR_DARK);

        $sheet->setCellValue('A2', 'LAPORAN PENJUALAN & BAGI HASIL BARANG KONSINYASI');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::COLOR_PRIMARY);

        $metaText = "Tanggal Cetak: {$generatedAt} | Total Terjual: " . number_format($summary['total_sold_qty'] ?? 0, 0, ',', '.') . " unit | Komisi Toko: Rp " . number_format($summary['total_store_commission'] ?? 0, 0, ',', '.') . " | Tagihan Belum Disetor: Rp " . number_format($summary['unsettled_payable'] ?? 0, 0, ',', '.');
        $sheet->setCellValue('A3', $metaText);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);

        $row = 5;

        // Table Headers
        $headers = [
            'A' => 'No',
            'B' => 'Tanggal Penjualan',
            'C' => 'No Faktur POS',
            'D' => 'Nama Produk Konsinyasi',
            'E' => 'Mitra (Konsinyor)',
            'F' => 'Qty Terjual',
            'G' => 'Harga Jual',
            'H' => 'Total Penjualan',
            'I' => 'Harga Modal Mitra (HPP Kns)',
            'J' => 'Bagi Hasil Komisi Toko',
            'K' => 'Status Setor (Settlement)',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);

        // Data Rows
        $sales = $this->data['sales'] ?? [];
        $no = 1;

        foreach ($sales as $item) {
            $row++;
            $saleDate = !empty($item['sale']['sale_date']) ? date('d/m/Y', strtotime($item['sale']['sale_date'])) : '-';
            $invoiceNumber = $item['sale']['transaction_number'] ?? $item['sale']['invoice_number'] ?? '-';
            $productName = $item['product']['name'] ?? '-';
            $consignorName = $item['consignor']['name'] ?? '-';
            $qty = (float) ($item['quantity'] ?? 0);
            $sellingPrice = (float) ($item['unit_price'] ?? 0);
            $totalSales = (float) ($item['subtotal'] ?? 0);
            $consignorCost = (float) ($item['consignment_cost'] ?? 0);
            $commissionAmount = (float) ($item['commission_amount'] ?? 0);
            $status = ($item['settlement_status'] ?? '') === 'settled' ? 'Sudah Disetor' : 'Belum Disetor';

            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $saleDate);
            $sheet->setCellValue("C{$row}", $invoiceNumber);
            $sheet->setCellValue("D{$row}", $productName);
            $sheet->setCellValue("E{$row}", $consignorName);
            $sheet->setCellValue("F{$row}", $qty);
            $sheet->setCellValue("G{$row}", $sellingPrice);
            $sheet->setCellValue("H{$row}", $totalSales);
            $sheet->setCellValue("I{$row}", $consignorCost);
            $sheet->setCellValue("J{$row}", $commissionAmount);
            $sheet->setCellValue("K{$row}", $status);

            // Alignment & Number formats
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("G{$row}:J{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Alternate row background
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:K{$row}")->getFill()->applyFromArray([
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_ZEBRA],
                ]);
            }
        }

        // Summary Total Row
        $row++;
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("F{$row}", "=SUM(F6:F" . ($row - 1) . ")");
        $sheet->setCellValue("H{$row}", "=SUM(H6:H" . ($row - 1) . ")");
        $sheet->setCellValue("I{$row}", "=SUM(I6:I" . ($row - 1) . ")");
        $sheet->setCellValue("J{$row}", "=SUM(J6:J" . ($row - 1) . ")");

        $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("H{$row}:J{$row}")->getNumberFormat()->setFormatCode('#,##0');

        // Border
        $sheet->getStyle("A5:K{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
        ]);

        $this->autoFitColumns($sheet, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K']);

        return $this->streamDownload($spreadsheet, $filename);
    }
}


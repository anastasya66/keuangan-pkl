<?php

namespace App\Exports;

use App\Models\SystemSetting;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseExcelExport
{
    // Color Palette FinToko
    public const COLOR_PRIMARY = '167A93';
    public const COLOR_DARK = '16323B';
    public const COLOR_LIGHT = 'F8FDFE';
    public const COLOR_SECTION = 'EAF8FA';
    public const COLOR_MUTED = '577782';
    public const COLOR_BORDER = 'D7EBEF';
    public const COLOR_PROFIT = '1B8751';
    public const COLOR_PROFIT_BG = 'E8F8F0';
    public const COLOR_LOSS = 'C53030';
    public const COLOR_LOSS_BG = 'FDEBED';

    // Format Numbering
    public const FORMAT_CURRENCY = '#,##0.00';
    public const FORMAT_CURRENCY_INT = '#,##0';
    public const FORMAT_QTY = '#,##0.00';

    /**
     * Dapatkan informasi toko dari SystemSetting.
     */
    protected function getStoreInfo(): array
    {
        try {
            return [
                'name' => SystemSetting::where('key', 'store_name')->value('value') ?: 'FinToko Minimarket & Retail',
                'address' => SystemSetting::where('key', 'store_address')->value('value') ?: '',
                'phone' => SystemSetting::where('key', 'store_phone')->value('value') ?: '',
                'email' => SystemSetting::where('key', 'store_email')->value('value') ?: '',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'FinToko Minimarket & Retail',
                'address' => '',
                'phone' => '',
                'email' => '',
            ];
        }
    }

    /**
     * Inisialisasi workbook Spreadsheet baru dengan metadata.
     */
    protected function createSpreadsheet(string $title, string $sheetTitle = 'Laporan'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('FinToko System')
            ->setLastModifiedBy('FinToko System')
            ->setTitle($title)
            ->setSubject($title)
            ->setDescription('Dokumen resmi ekspor FinToko');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31)); // Batas nama sheet Excel 31 karakter
        $sheet->setShowGridLines(true);

        return $spreadsheet;
    }

    /**
     * Terapkan auto-size pada kolom A sampai kolom tertinggi.
     */
    protected function autoFitColumns($sheet, string $maxCol = 'H'): void
    {
        $maxColIndex = Coordinate::columnIndexFromString($maxCol);
        for ($i = 1; $i <= $maxColIndex; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    /**
     * Stream response download XLSX.
     */
    protected function streamXlsx(Spreadsheet $spreadsheet, string $filename): Response
    {
        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename = preg_replace('/\.xls$/i', '', $filename) . '.xlsx';
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(true);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}


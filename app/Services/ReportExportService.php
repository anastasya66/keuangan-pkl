<?php

namespace App\Services;

use App\Exports\BalanceSheetExport;
use App\Exports\ConsignmentExport;
use App\Exports\IncomeStatementExport;
use App\Exports\InventoryReportExport;
use App\Exports\InventoryValuationExport;
use App\Exports\SalesReportExport;
use App\Exports\StockMovementExport;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReportExportService
{
    /**
     * Dapatkan informasi toko dari SystemSetting untuk header dokumen PDF & Excel.
     */
    public function getStoreSettings(): array
    {
        try {
            return [
                'store_name' => SystemSetting::where('key', 'store_name')->value('value') ?: 'FinToko Minimarket & Retail',
                'store_address' => SystemSetting::where('key', 'store_address')->value('value') ?: '',
                'store_phone' => SystemSetting::where('key', 'store_phone')->value('value') ?: '',
                'store_email' => SystemSetting::where('key', 'store_email')->value('value') ?: '',
            ];
        } catch (\Throwable $e) {
            return [
                'store_name' => 'FinToko Minimarket & Retail',
                'store_address' => '',
                'store_phone' => '',
                'store_email' => '',
            ];
        }
    }

    /**
     * Helper stream download PDF dengan header anti-caching dan Content-Disposition resmi.
     */
    protected function streamPdf($pdf, string $filename): Response
    {
        $response = $pdf->download($filename);
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }

    /**
     * Export Laporan Laba Rugi ke PDF (A4 Portrait).
     */
    public function exportIncomeStatementPdf(array $data, ?string $filename = null): Response
    {
        $startDate = $data['period']['start_date'] ?? date('Y-m-d');
        $endDate = $data['period']['end_date'] ?? date('Y-m-d');
        $filename = $filename ?: "laba-rugi-{$startDate}-sd-{$endDate}.pdf";

        $pdf = Pdf::loadView('reports.pdf.income-statement', [
            'data' => $data,
            'settings' => $this->getStoreSettings(),
        ])->setPaper('a4', 'portrait');

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Laporan Laba Rugi ke Excel (.xlsx).
     */
    public function exportIncomeStatementExcel(array $data, ?string $filename = null): Response
    {
        $startDate = $data['period']['start_date'] ?? date('Y-m-d');
        $endDate = $data['period']['end_date'] ?? date('Y-m-d');
        $filename = $filename ?: "laporan-laba-rugi-{$startDate}-sd-{$endDate}.xlsx";

        $exporter = new IncomeStatementExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Laporan Neraca ke PDF (A4 Portrait).
     */
    public function exportBalanceSheetPdf(array $data, ?string $filename = null): Response
    {
        $asOfDate = $data['as_of_date'] ?? date('Y-m-d');
        $filename = $filename ?: "neraca-per-{$asOfDate}.pdf";

        $pdf = Pdf::loadView('reports.pdf.balance-sheet', [
            'data' => $data,
            'settings' => $this->getStoreSettings(),
        ])->setPaper('a4', 'portrait');

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Laporan Neraca ke Excel (.xlsx).
     */
    public function exportBalanceSheetExcel(array $data, ?string $filename = null): Response
    {
        $asOfDate = $data['as_of_date'] ?? date('Y-m-d');
        $filename = $filename ?: "laporan-neraca-per-{$asOfDate}.xlsx";

        $exporter = new BalanceSheetExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Laporan Stok ke PDF (A4 Landscape agar tabel muat dengan baik).
     */
    public function exportInventoryReportPdf(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-stok-' . date('Ymd') . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.inventory-report', [
            'data' => $data,
            'settings' => $this->getStoreSettings(),
        ])->setPaper('a4', 'landscape');

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Laporan Stok ke Excel (.xlsx).
     */
    public function exportInventoryReportExcel(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-stok-persediaan-' . date('Ymd') . '.xlsx';

        $exporter = new InventoryReportExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Riwayat Mutasi ke PDF (A4 Landscape).
     */
    public function exportStockMovementsPdf(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'riwayat-mutasi-' . date('Ymd') . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.stock-movements', [
            'data' => $data,
            'settings' => $this->getStoreSettings(),
        ])->setPaper('a4', 'landscape');

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Riwayat Mutasi ke Excel (.xlsx).
     */
    public function exportStockMovementsExcel(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'riwayat-mutasi-persediaan-' . date('Ymd') . '.xlsx';

        $exporter = new StockMovementExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Nilai Persediaan ke PDF (A4 Landscape).
     */
    public function exportInventoryValuationPdf(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'nilai-persediaan-' . date('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.inventory-valuation', [
            'data' => $data,
            'settings' => $this->getStoreSettings(),
        ])->setPaper('a4', 'landscape');

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Nilai Persediaan ke Excel (.xlsx).
     */
    public function exportInventoryValuationExcel(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'nilai-persediaan-' . date('Ymd-His') . '.xlsx';

        $exporter = new InventoryValuationExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Laporan Konsinyasi ke PDF (A4 Landscape).
     */
    public function exportConsignmentPdf(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-konsinyasi-' . date('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.consignment-report', [
            'data' => $data,
            'settings' => $this->getStoreSettings(),
        ])->setPaper('a4', 'landscape');

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Laporan Konsinyasi ke Excel (.xlsx).
     */
    public function exportConsignmentExcel(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-konsinyasi-' . date('Ymd-His') . '.xlsx';

        $exporter = new ConsignmentExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Laporan Penjualan (Harian/Bulanan) ke PDF (A4 Portrait).
     */
    public function exportSalesReportPdf(array $data, ?string $filename = null): Response
    {
        $mode = $data['mode'] ?? 'daily';
        $filename = $filename ?: 'laporan-penjualan-' . $mode . '-' . date('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.sales-report', [
            'data' => $data,
            'settings' => $this->getStoreSettings(),
        ])->setPaper('a4', 'portrait');

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Laporan Penjualan (Harian/Bulanan) ke Excel (.xlsx).
     */
    public function exportSalesReportExcel(array $data, ?string $filename = null): Response
    {
        $mode = $data['mode'] ?? 'daily';
        $filename = $filename ?: 'laporan-penjualan-' . $mode . '-' . date('Ymd-His') . '.xlsx';

        $exporter = new SalesReportExport($data);
        return $exporter->download($filename);
    }
}

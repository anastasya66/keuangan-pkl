<?php

namespace App\Services;

use App\Exports\BalanceSheetExport;
use App\Exports\IncomeStatementExport;
use App\Exports\InventoryReportExport;
use App\Exports\StockMovementExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReportExportService
{
    /**
     * Export Laporan Laba Rugi ke PDF.
     */
    public function exportIncomeStatementPdf(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'laba-rugi-' . $data['period']['start_date'] . '-sd-' . $data['period']['end_date'] . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.income-statement', ['data' => $data])
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Export Laporan Laba Rugi ke Excel / CSV.
     */
    public function exportIncomeStatementExcel(array $data, ?string $filename = null): Response
    {
        $exporter = new IncomeStatementExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Laporan Neraca ke PDF.
     */
    public function exportBalanceSheetPdf(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'neraca-per-' . $data['as_of_date'] . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.balance-sheet', ['data' => $data])
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Export Laporan Neraca ke Excel / CSV.
     */
    public function exportBalanceSheetExcel(array $data, ?string $filename = null): Response
    {
        $exporter = new BalanceSheetExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Laporan Stok ke PDF.
     */
    public function exportInventoryReportPdf(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'laporan-stok-' . date('Ymd') . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.inventory-report', ['data' => $data])
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Export Laporan Stok ke Excel / CSV.
     */
    public function exportInventoryReportExcel(array $data, ?string $filename = null): Response
    {
        $exporter = new InventoryReportExport($data);
        return $exporter->download($filename);
    }

    /**
     * Export Riwayat Mutasi ke PDF.
     */
    public function exportStockMovementsPdf(array $data, ?string $filename = null): Response
    {
        $filename = $filename ?: 'riwayat-mutasi-' . date('Ymd') . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.stock-movements', ['data' => $data])
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Export Riwayat Mutasi ke Excel / CSV.
     */
    public function exportStockMovementsExcel(array $data, ?string $filename = null): Response
    {
        $exporter = new StockMovementExport($data);
        return $exporter->download($filename);
    }
}


<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BalanceSheetController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService,
        protected ReportExportService $exportService
    ) {}

    /**
     * Tampilkan Laporan Neraca (Balance Sheet).
     */
    public function index(Request $request): JsonResponse
    {
        $asOf = $request->query('as_of') ?? $request->query('as_of_date') ?? $request->query('date');
        $data = $this->reportService->getBalanceSheet($asOf);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan neraca berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Export Laporan Neraca ke PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $asOf = $request->query('as_of') ?? $request->query('as_of_date') ?? $request->query('date');
        $data = $this->reportService->getBalanceSheet($asOf);

        return $this->exportService->exportBalanceSheetPdf($data);
    }

    /**
     * Export Laporan Neraca ke Excel / CSV.
     */
    public function exportExcel(Request $request): Response
    {
        $asOf = $request->query('as_of') ?? $request->query('as_of_date') ?? $request->query('date');
        $data = $this->reportService->getBalanceSheet($asOf);

        return $this->exportService->exportBalanceSheetExcel($data);
    }
}

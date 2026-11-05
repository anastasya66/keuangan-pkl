<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IncomeStatementController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService,
        protected ReportExportService $exportService
    ) {}

    /**
     * Tampilkan Laporan Laba Rugi.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->reportService->getIncomeStatement(
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan laba rugi berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Export Laporan Laba Rugi ke PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $data = $this->reportService->getIncomeStatement(
            $request->query('start_date'),
            $request->query('end_date')
        );

        return $this->exportService->exportIncomeStatementPdf($data);
    }

    /**
     * Export Laporan Laba Rugi ke Excel / CSV.
     */
    public function exportExcel(Request $request): Response
    {
        $data = $this->reportService->getIncomeStatement(
            $request->query('start_date'),
            $request->query('end_date')
        );

        return $this->exportService->exportIncomeStatementExcel($data);
    }
}


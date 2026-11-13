<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\ReportExportService;
use App\Services\SalesReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SalesReportController extends Controller
{
    public function __construct(
        protected SalesReportService $reportService,
        protected ReportExportService $exportService
    ) {}

    /**
     * Tampilkan Laporan Penjualan (Harian / Bulanan) beserta analisis laba & margin.
     */
    public function index(Request $request): JsonResponse
    {
        $params = [
            'mode' => $request->query('mode', 'daily'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'year' => $request->query('year'),
            'month' => $request->query('month'),
        ];

        $data = $this->reportService->getSalesReport($params);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan penjualan berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Export Laporan Penjualan ke PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $params = [
            'mode' => $request->query('mode', 'daily'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'year' => $request->query('year'),
            'month' => $request->query('month'),
        ];

        $data = $this->reportService->getSalesReport($params);

        return $this->exportService->exportSalesReportPdf($data);
    }

    /**
     * Export Laporan Penjualan ke Excel / OpenXML.
     */
    public function exportExcel(Request $request): Response
    {
        $params = [
            'mode' => $request->query('mode', 'daily'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'year' => $request->query('year'),
            'month' => $request->query('month'),
        ];

        $data = $this->reportService->getSalesReport($params);

        return $this->exportService->exportSalesReportExcel($data);
    }
}


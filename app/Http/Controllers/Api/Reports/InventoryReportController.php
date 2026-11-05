<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\InventoryReportService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryReportController extends Controller
{
    public function __construct(
        protected InventoryReportService $reportService,
        protected ReportExportService $exportService
    ) {}

    /**
     * Laporan Stok Produk Aktual & Valuasi Persediaan.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'category_id' => $request->query('category_id'),
            'stock_status' => $request->query('stock_status'),
            'search' => $request->query('search'),
        ];

        $data = $this->reportService->getInventoryReport($filters);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan stok produk berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Export Laporan Stok ke PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $filters = [
            'category_id' => $request->query('category_id'),
            'stock_status' => $request->query('stock_status'),
            'search' => $request->query('search'),
        ];

        $data = $this->reportService->getInventoryReport($filters);

        return $this->exportService->exportInventoryReportPdf($data);
    }

    /**
     * Export Laporan Stok ke Excel / CSV.
     */
    public function exportExcel(Request $request): Response
    {
        $filters = [
            'category_id' => $request->query('category_id'),
            'stock_status' => $request->query('stock_status'),
            'search' => $request->query('search'),
        ];

        $data = $this->reportService->getInventoryReport($filters);

        return $this->exportService->exportInventoryReportExcel($data);
    }

    /**
     * Laporan Riwayat Mutasi Persediaan.
     */
    public function stockMovements(Request $request): JsonResponse
    {
        $filters = [
            'product_id' => $request->query('product_id'),
            'movement_type' => $request->query('movement_type'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'search' => $request->query('search'),
        ];

        $data = $this->reportService->getStockMovementsReport($filters);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan riwayat mutasi stok berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Export Riwayat Mutasi ke PDF.
     */
    public function stockMovementsExportPdf(Request $request): Response
    {
        $filters = [
            'product_id' => $request->query('product_id'),
            'movement_type' => $request->query('movement_type'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'search' => $request->query('search'),
        ];

        $data = $this->reportService->getStockMovementsReport($filters);

        return $this->exportService->exportStockMovementsPdf($data);
    }

    /**
     * Export Riwayat Mutasi ke Excel / CSV.
     */
    public function stockMovementsExportExcel(Request $request): Response
    {
        $filters = [
            'product_id' => $request->query('product_id'),
            'movement_type' => $request->query('movement_type'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'search' => $request->query('search'),
        ];

        $data = $this->reportService->getStockMovementsReport($filters);

        return $this->exportService->exportStockMovementsExcel($data);
    }
}


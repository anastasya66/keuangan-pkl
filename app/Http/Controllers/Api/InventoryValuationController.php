<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InventoryValuationService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryValuationController extends Controller
{
    public function __construct(
        protected InventoryValuationService $valuationService,
        protected ReportExportService $exportService
    ) {}

    /**
     * Tampilkan data ringkasan dan daftar nilai persediaan Moving Weighted Average.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $data = $this->valuationService->getValuationOverview($request->all(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data nilai persediaan barang berhasil diambil',
            'data' => [
                'summary' => $data['summary'],
                'category_breakdown' => $data['category_breakdown'],
                'products' => $data['products']->items(),
                'generated_at' => $data['generated_at'],
            ],
            'pagination' => [
                'current_page' => $data['products']->currentPage(),
                'last_page' => $data['products']->lastPage(),
                'per_page' => $data['products']->perPage(),
                'total' => $data['products']->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Export data nilai persediaan ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $exportData = $this->valuationService->getFullExportData($request->all());
        return $this->exportService->exportInventoryValuationExcel($exportData);
    }

    /**
     * Export data nilai persediaan ke format PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $exportData = $this->valuationService->getFullExportData($request->all());
        return $this->exportService->exportInventoryValuationPdf($exportData);
    }
}

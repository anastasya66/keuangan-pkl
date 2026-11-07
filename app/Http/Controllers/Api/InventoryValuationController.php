<?php

namespace App\Http\Controllers\Api;

use App\Exports\InventoryValuationExport;
use App\Http\Controllers\Controller;
use App\Services\InventoryValuationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryValuationController extends Controller
{
    public function __construct(
        protected InventoryValuationService $valuationService
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
     * Export data nilai persediaan ke format Excel (.xls).
     */
    public function exportExcel(Request $request): Response
    {
        $exportData = $this->valuationService->getFullExportData($request->all());
        $exporter = new InventoryValuationExport($exportData);

        return $exporter->download('nilai-persediaan-' . date('Ymd-His') . '.xls');
    }

    /**
     * Export data nilai persediaan ke format PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $exportData = $this->valuationService->getFullExportData($request->all());
        $filename = 'nilai-persediaan-' . date('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('reports.pdf.inventory-valuation', ['data' => $exportData])
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}

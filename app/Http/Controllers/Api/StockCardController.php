<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StockCardController extends Controller
{
    public function __construct(
        protected StockCardService $stockCardService
    ) {}

    /**
     * Tampilkan data kartu stok untuk produk tertentu.
     */
    public function show(Request $request, int $productId): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $stockCard = $this->stockCardService->getStockCard($productId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'message' => 'Kartu stok produk berhasil diambil',
            'data' => $stockCard,
        ], Response::HTTP_OK);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\PosSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosController extends Controller
{
    public function __construct(
        protected PosSaleService $posSaleService
    ) {}

    /**
     * Cari produk aktif untuk layar POS kasir (berdasarkan nama, SKU, atau barcode).
     */
    public function products(Request $request): JsonResponse
    {
        $query = $request->input('search', '');
        $limit = (int) $request->input('limit', 40);

        $products = $this->posSaleService->searchProducts($query, $limit);

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk POS berhasil diambil',
            'data' => $products,
        ], Response::HTTP_OK);
    }

    /**
     * Cari produk spesifik langsung dengan scan barcode.
     */
    public function barcode(string $barcode): JsonResponse
    {
        $product = $this->posSaleService->findByBarcode($barcode);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => "Produk dengan barcode '{$barcode}' tidak ditemukan.",
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk barcode berhasil ditemukan',
            'data' => $product,
        ], Response::HTTP_OK);
    }

    /**
     * Selesaikan transaksi kasir (Checkout POS).
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_date' => ['nullable', 'date'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sale = $this->posSaleService->checkout($validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kasir POS berhasil diselesaikan',
            'data' => $sale,
        ], Response::HTTP_CREATED);
    }

    /**
     * Riwayat transaksi penjualan kasir.
     */
    public function sales(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $sales = $this->posSaleService->listSales($request->all(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat penjualan berhasil diambil',
            'data' => $sales->items(),
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Detail satu transaksi penjualan.
     */
    public function showSale(int $id): JsonResponse
    {
        $sale = $this->posSaleService->getSale($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail penjualan berhasil diambil',
            'data' => $sale,
        ], Response::HTTP_OK);
    }

    /**
     * Dapatkan payload data struk belanja untuk pencetakan thermal kasir.
     */
    public function receipt(int $id): JsonResponse
    {
        $receipt = $this->posSaleService->getReceiptData($id);

        return response()->json([
            'success' => true,
            'message' => 'Data struk belanja kasir berhasil diambil',
            'data' => $receipt,
        ], Response::HTTP_OK);
    }

    /**
     * Batalkan / refund transaksi penjualan kasir (Admin only).
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang berwenang membatalkan transaksi penjualan.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $sale = Sale::findOrFail($id);
        $cancelled = $this->posSaleService->cancelSale($sale, $request->user()->id, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi penjualan berhasil dibatalkan dan stok produk telah dikembalikan',
            'data' => $cancelled,
        ], Response::HTTP_OK);
    }
}


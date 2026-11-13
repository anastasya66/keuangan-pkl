<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consignor;
use App\Models\Product;
use App\Services\ConsignmentService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsignmentController extends Controller
{
    public function __construct(
        protected ConsignmentService $consignmentService,
        protected ReportExportService $reportExportService
    ) {}

    /**
     * Dapatkan data 4 kartu ringkasan statistik konsinyasi.
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = [
            'consignor_id' => $request->query('consignor_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $summary = $this->consignmentService->getSummary($filters);

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan data konsinyasi berhasil diambil.',
            'data' => $summary,
        ], Response::HTTP_OK);
    }

    /**
     * Dapatkan riwayat transaksi penjualan konsinyasi (Tab 1).
     */
    public function sales(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'consignor_id' => $request->query('consignor_id'),
            'settlement_status' => $request->query('settlement_status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $sales = $this->consignmentService->getSales($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi penjualan konsinyasi berhasil diambil.',
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
     * Dapatkan daftar stok produk konsinyasi (Tab 2).
     */
    public function products(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'consignor_id' => $request->query('consignor_id'),
            'is_active' => $request->query('is_active'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $products = $this->consignmentService->getProducts($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk konsinyasi berhasil diambil.',
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Dapatkan daftar mitra konsinyor beserta rekap tagihan (Tab 3).
     */
    public function consignors(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'is_active' => $request->query('is_active'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $consignors = $this->consignmentService->getConsignors($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar mitra konsinyor berhasil diambil.',
            'data' => $consignors->items(),
            'pagination' => [
                'current_page' => $consignors->currentPage(),
                'last_page' => $consignors->lastPage(),
                'per_page' => $consignors->perPage(),
                'total' => $consignors->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Dapatkan opsi ringkas mitra konsinyor untuk select/dropdown.
     */
    public function activeConsignors(): JsonResponse
    {
        $consignors = $this->consignmentService->getActiveConsignors();

        return response()->json([
            'success' => true,
            'message' => 'Daftar opsi mitra konsinyor berhasil diambil.',
            'data' => $consignors,
        ], Response::HTTP_OK);
    }

    /**
     * Tambah mitra konsinyor baru.
     */
    public function storeConsignor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:consignors,code'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_holder' => ['nullable', 'string', 'max:150'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $consignor = $this->consignmentService->createConsignor($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mitra konsinyor berhasil ditambahkan.',
            'data' => $consignor,
        ], Response::HTTP_CREATED);
    }

    /**
     * Perbarui data mitra konsinyor.
     */
    public function updateConsignor(Request $request, Consignor $consignor): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:consignors,code,' . $consignor->id],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_holder' => ['nullable', 'string', 'max:150'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updated = $this->consignmentService->updateConsignor($consignor, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Data mitra konsinyor berhasil diperbarui.',
            'data' => $updated,
        ], Response::HTTP_OK);
    }

    /**
     * Hapus mitra konsinyor.
     */
    public function destroyConsignor(Consignor $consignor): JsonResponse
    {
        $this->consignmentService->deleteConsignor($consignor);

        return response()->json([
            'success' => true,
            'message' => 'Mitra konsinyor berhasil dihapus.',
        ], Response::HTTP_OK);
    }

    /**
     * Tambah barang / produk konsinyasi baru.
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consignor_id' => ['required', 'exists:consignors,id'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'description' => ['nullable', 'string'],
            'consignment_cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'gt:0'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = $this->consignmentService->createConsignmentProduct($validated, $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Produk konsinyasi berhasil ditambahkan ke inventaris.',
            'data' => $product,
        ], Response::HTTP_CREATED);
    }

    /**
     * Perbarui data barang / produk konsinyasi.
     */
    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'consignor_id' => ['sometimes', 'required', 'exists:consignors,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku,' . $product->id],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode,' . $product->id],
            'description' => ['nullable', 'string'],
            'consignment_cost_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updated = $this->consignmentService->updateConsignmentProduct($product, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Data produk konsinyasi berhasil diperbarui.',
            'data' => $updated,
        ], Response::HTTP_OK);
    }

    /**
     * Penyesuaian stok konsinyasi (Tambah stok titipan atau Retur sisa barang).
     */
    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:in,return'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->consignmentService->adjustStock($product, $validated, $request->user()?->id);

        $actionText = $validated['type'] === 'return' ? 'Retur sisa barang konsinyasi berhasil diproses.' : 'Penambahan stok titipan konsinyasi berhasil disimpan.';

        return response()->json([
            'success' => true,
            'message' => $actionText,
            'data' => $updated,
        ], Response::HTTP_OK);
    }

    /**
     * Proses Penyelesaian / Penyetoran Tagihan ke Mitra (Settlement).
     */
    public function settle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consignor_id' => ['required', 'exists:consignors,id'],
            'settlement_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'sale_item_ids' => ['nullable', 'array'],
            'sale_item_ids.*' => ['integer', 'exists:sale_items,id'],
        ]);

        $settlement = $this->consignmentService->createSettlement($validated, $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Penyetoran bagi hasil ke mitra konsinyor berhasil diselesaikan.',
            'data' => $settlement,
        ], Response::HTTP_CREATED);
    }

    /**
     * Export Laporan Konsinyasi ke PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $filters = [
            'search' => $request->query('search'),
            'consignor_id' => $request->query('consignor_id'),
            'settlement_status' => $request->query('settlement_status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $summary = $this->consignmentService->getSummary($filters);
        // Ambil semua records tanpa pagination untuk export
        $sales = $this->consignmentService->getSales($filters, 1000);

        $data = [
            'summary' => $summary,
            'sales' => $sales->items(),
        ];

        return $this->reportExportService->exportConsignmentPdf($data);
    }

    /**
     * Export Laporan Konsinyasi ke Excel.
     */
    public function exportExcel(Request $request): Response
    {
        $filters = [
            'search' => $request->query('search'),
            'consignor_id' => $request->query('consignor_id'),
            'settlement_status' => $request->query('settlement_status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $summary = $this->consignmentService->getSummary($filters);
        $sales = $this->consignmentService->getSales($filters, 1000);

        $data = [
            'summary' => $summary,
            'sales' => $sales->items(),
        ];

        return $this->reportExportService->exportConsignmentExcel($data);
    }
}


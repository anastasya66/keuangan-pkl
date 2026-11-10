<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Sale;
use App\Services\Payment\QrisPaymentService;
use App\Services\PosSaleService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosController extends Controller
{
    public function __construct(
        protected PosSaleService $posSaleService,
        protected QrisPaymentService $qrisPaymentService
    ) {}

    /**
     * Cari produk aktif untuk layar POS kasir (berdasarkan nama, SKU, atau barcode).
     */
    public function products(Request $request): JsonResponse
    {
        $query = $request->input('search', '');
        $limit = (int) $request->input('limit', 250);

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
            'payment_method' => ['required', 'string', 'max:50'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'order_id' => ['nullable', 'string', 'max:64'],
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

    /**
     * Inisiasi QRIS Dinamis untuk kasir.
     */
    public function createQris(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'items_count' => ['nullable', 'integer'],
        ]);

        $transaction = $this->qrisPaymentService->initiateQris(
            amount: (float) $validated['amount'],
            userId: $request->user()?->id,
            customerName: $validated['customer_name'] ?? null,
            metadata: [
                'items_count' => $validated['items_count'] ?? 0,
                'source' => 'pos_checkout',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Kode QRIS dinamis berhasil dibuat',
            'data' => [
                'order_id' => $transaction->order_id,
                'gross_amount' => (float) $transaction->gross_amount,
                'status' => $transaction->status,
                'qr_string' => $transaction->qr_string,
                'qr_image_url' => $transaction->qr_image_url,
                'expires_at' => $transaction->expires_at?->toIso8601String(),
                'expires_in_seconds' => $transaction->expires_at ? max(0, now()->diffInSeconds($transaction->expires_at, false)) : 900,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Cek status pembayaran transaksi QRIS secara real-time.
     */
    public function checkPaymentStatus(string $orderId): JsonResponse
    {
        $transaction = $this->qrisPaymentService->checkStatus($orderId);

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran berhasil diperiksa',
            'data' => [
                'order_id' => $transaction->order_id,
                'status' => $transaction->status,
                'is_paid' => $transaction->isPaid(),
                'is_pending' => $transaction->isPending(),
                'is_expired' => $transaction->isExpired(),
                'paid_at' => $transaction->paid_at?->toIso8601String(),
                'gross_amount' => (float) $transaction->gross_amount,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Simulasi bayar sukses QRIS (untuk pengujian / demo).
     */
    public function simulatePayment(string $orderId): JsonResponse
    {
        $transaction = $this->qrisPaymentService->simulatePayment($orderId);

        return response()->json([
            'success' => true,
            'message' => 'Simulasi pembayaran QRIS berhasil! Status lunas.',
            'data' => [
                'order_id' => $transaction->order_id,
                'status' => $transaction->status,
                'is_paid' => $transaction->isPaid(),
                'paid_at' => $transaction->paid_at?->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Batalkan transaksi QRIS sebelum kedaluwarsa.
     */
    public function cancelPayment(string $orderId): JsonResponse
    {
        $transaction = $this->qrisPaymentService->cancelPayment($orderId);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi QRIS berhasil dibatalkan',
            'data' => [
                'order_id' => $transaction->order_id,
                'status' => $transaction->status,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Dapatkan daftar rekening bank toko untuk metode transfer bank kasir.
     */
    public function bankAccounts(): JsonResponse
    {
        // 1. Cek dari Chart of Accounts tipe bank/kas
        $accounts = ChartOfAccount::where('account_type', 'assets')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('account_name', 'like', '%bank%')
                  ->orWhere('account_name', 'like', '%bca%')
                  ->orWhere('account_name', 'like', '%mandiri%')
                  ->orWhere('account_name', 'like', '%bni%')
                  ->orWhere('account_name', 'like', '%bri%');
            })
            ->get();

        $bankList = [];
        if ($accounts->isNotEmpty()) {
            foreach ($accounts as $acc) {
                $bankList[] = [
                    'id' => $acc->id,
                    'bank' => preg_replace('/.*(BCA|Mandiri|BNI|BRI|BSI|Permata).*/i', '$1', $acc->account_name),
                    'account_name' => 'FinToko Official',
                    'account_number' => $acc->account_code === '1120' ? '8830-1928-4100' : '137-00-1928472-1',
                    'display_name' => $acc->account_name,
                ];
            }
        }

        // Fallback default bank accounts if none found
        if (empty($bankList)) {
            $bankList = [
                [
                    'id' => 1,
                    'bank' => 'BCA',
                    'account_name' => 'FinToko Official Store',
                    'account_number' => '8830192841',
                    'display_name' => 'Bank BCA - FinToko Official',
                ],
                [
                    'id' => 2,
                    'bank' => 'Mandiri',
                    'account_name' => 'FinToko Official Store',
                    'account_number' => '1370019284721',
                    'display_name' => 'Bank Mandiri - FinToko Retail',
                ],
                [
                    'id' => 3,
                    'bank' => 'BRI',
                    'account_name' => 'FinToko Official Store',
                    'account_number' => '034101002345501',
                    'display_name' => 'Bank BRI - FinToko Minimarket',
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar rekening bank toko berhasil dimuat',
            'data' => $bankList,
        ], Response::HTTP_OK);
    }

    /**
     * Webhook receiver untuk Midtrans Payment Gateway.
     */
    public function handleMidtransWebhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Signature', $request->input('signature_key'));
        $result = $this->qrisPaymentService->handleWebhook($request->all(), $signature);

        return response()->json($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }
}

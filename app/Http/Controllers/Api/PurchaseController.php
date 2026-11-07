<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    /**
     * Tampilkan daftar transaksi pembelian.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $purchases = $this->purchaseService->listPurchases($request->all(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi pembelian berhasil diambil',
            'data' => $purchases->items(),
            'pagination' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Buat transaksi pembelian baru (Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang berwenang membuat transaksi pembelian.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:50', 'unique:purchases,invoice_number'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,received'],
            'payment_method' => ['required', 'in:cash,transfer,tempo'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
        ]);

        $purchase = $this->purchaseService->createPurchase($validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi pembelian berhasil dibuat' . ($purchase->status === 'received' ? ' dan stok telah diperbarui' : ''),
            'data' => $purchase,
        ], Response::HTTP_CREATED);
    }

    /**
     * Tampilkan detail transaksi pembelian.
     */
    public function show(int $id): JsonResponse
    {
        $purchase = $this->purchaseService->getPurchase($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail transaksi pembelian berhasil diambil',
            'data' => $purchase,
        ], Response::HTTP_OK);
    }

    /**
     * Konfirmasi penerimaan barang untuk pembelian berstatus draft (Admin only).
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang berwenang mengonfirmasi penerimaan barang.',
            ], Response::HTTP_FORBIDDEN);
        }

        $purchase = Purchase::findOrFail($id);
        $received = $this->purchaseService->receivePurchase($purchase, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Barang pembelian berhasil diterima, stok fisik dan HPP rata-rata telah diperbarui',
            'data' => $received,
        ], Response::HTTP_OK);
    }

    /**
     * Batalkan transaksi pembelian (Admin only).
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang berwenang membatalkan transaksi pembelian.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $purchase = Purchase::findOrFail($id);
        $cancelled = $this->purchaseService->cancelPurchase($purchase, $request->user()->id, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi pembelian berhasil dibatalkan dan efek stok telah dibalikkan',
            'data' => $cancelled,
        ], Response::HTTP_OK);
    }

    /**
     * Bayar sisa utang pembelian tempo (Admin only).
     */
    public function payDebt(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang berwenang mencatat pelunasan utang supplier.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:cash,transfer'],
            'notes' => ['nullable', 'string'],
        ]);

        $purchase = Purchase::findOrFail($id);
        $payment = $this->purchaseService->payDebt($purchase, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran utang supplier berhasil dicatat dan jurnal akuntansi telah dibuat',
            'data' => $payment,
        ], Response::HTTP_OK);
    }
}


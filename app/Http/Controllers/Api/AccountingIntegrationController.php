<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JournalEntryResource;
use App\Models\AccountingMapping;
use App\Models\FinancialTransaction;
use App\Models\InventoryMovement;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingIntegrationController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Sinkronkan transaksi keuangan menjadi jurnal akuntansi.
     */
    public function syncTransaction(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        $journal = $this->accountingService->createJournalForFinancialTransaction(
            $transaction,
            $request->user()->id,
            autoPost: true
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal akuntansi transaksi keuangan berhasil digenerate dan diposting.',
            'data' => new JournalEntryResource($journal),
        ], 201);
    }

    /**
     * Sinkronkan mutasi persediaan menjadi jurnal akuntansi.
     */
    public function syncInventory(Request $request, InventoryMovement $movement): JsonResponse
    {
        $this->authorize('view', $movement);

        $journal = $this->accountingService->createJournalForInventoryMovement(
            $movement,
            $request->user()->id,
            autoPost: true
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal akuntansi mutasi persediaan berhasil digenerate dan diposting.',
            'data' => new JournalEntryResource($journal),
        ], 201);
    }

    /**
     * Dapatkan daftar konfigurasi mapping akun otomatis.
     */
    public function mappings(Request $request): JsonResponse
    {
        $mappings = AccountingMapping::with('account')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Konfigurasi pemetaan akun akuntansi berhasil diambil.',
            'data' => $mappings,
        ]);
    }
}


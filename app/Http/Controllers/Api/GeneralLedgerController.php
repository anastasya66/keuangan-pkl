<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\GeneralLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeneralLedgerController extends Controller
{
    public function __construct(
        protected GeneralLedgerService $generalLedgerService
    ) {}

    /**
     * Tampilkan buku besar (dengan memilih akun atau akun default pertama).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $accountId = $request->query('account_id');
        if (!$accountId) {
            // Pilih akun pertama yang aktif jika tidak dispesifikasikan
            $firstAccount = ChartOfAccount::where('is_active', true)->orderBy('account_code')->first();
            $accountId = $firstAccount?->id;
        }

        if (!$accountId) {
            return response()->json([
                'status' => 'success',
                'message' => 'Belum ada akun di Chart of Accounts.',
                'data' => null,
            ]);
        }

        $ledger = $this->generalLedgerService->getLedger(
            (int) $accountId,
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Data buku besar berhasil diambil.',
            'data' => $ledger,
        ]);
    }

    /**
     * Tampilkan buku besar untuk akun spesifik via URL parameter.
     */
    public function show(Request $request, int $accountId): JsonResponse
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $ledger = $this->generalLedgerService->getLedger(
            $accountId,
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Data buku besar berhasil diambil.',
            'data' => $ledger,
        ]);
    }
}


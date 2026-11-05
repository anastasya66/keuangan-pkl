<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\TrialBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrialBalanceController extends Controller
{
    public function __construct(
        protected TrialBalanceService $trialBalanceService
    ) {}

    /**
     * Tampilkan Neraca Saldo (Trial Balance).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $report = $this->trialBalanceService->getTrialBalance(
            $request->query('as_of_date') ?? $request->query('date'),
            $request->query('account_type')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan neraca saldo berhasil diambil.',
            'data' => $report,
        ]);
    }
}


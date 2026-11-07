<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountingPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountingPeriodController extends Controller
{
    public function __construct(
        protected AccountingPeriodService $periodService
    ) {}

    /**
     * Dapatkan daftar periode akuntansi.
     */
    public function index(Request $request): JsonResponse
    {
        $fiscalYear = $request->input('fiscal_year') ? (int) $request->input('fiscal_year') : null;
        $periods = $this->periodService->getPeriods($fiscalYear);

        return response()->json([
            'success' => true,
            'message' => 'Daftar periode akuntansi berhasil diambil',
            'data' => $periods,
        ], Response::HTTP_OK);
    }

    /**
     * Kunci atau buka kunci periode akuntansi.
     */
    public function toggleLock(int $id, Request $request): JsonResponse
    {
        $period = $this->periodService->toggleLock($id, $request->user());

        $statusStr = $period->is_locked ? 'dikunci (Locked)' : 'dibuka kembali (Unlocked)';

        return response()->json([
            'success' => true,
            'message' => "Periode '{$period->period_name}' berhasil {$statusStr}.",
            'data' => $period,
        ], Response::HTTP_OK);
    }

    /**
     * Lakukan penutupan buku periode akuntansi.
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $period = $this->periodService->closePeriod($id, $validated['notes'] ?? null, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Tutup buku periode '{$period->period_name}' berhasil dilakukan.",
            'data' => $period,
        ], Response::HTTP_OK);
    }
}

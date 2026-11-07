<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogController extends Controller
{
    public function __construct(
        protected ActivityLogService $logService
    ) {}

    /**
     * Dapatkan daftar audit log aktivitas sistem.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $logs = $this->logService->getLogs($request->all(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Log aktivitas sistem berhasil diambil',
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ], Response::HTTP_OK);
    }
}

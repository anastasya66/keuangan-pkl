<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Check API health status.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'FinToko API is running',
            'data' => [
                'application' => 'FinToko',
                'version' => '1.0.0',
            ],
        ], 200);
    }
}

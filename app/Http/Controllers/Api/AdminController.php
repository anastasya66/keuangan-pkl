<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    /**
     * Example admin-only endpoint to verify role middleware.
     */
    public function check(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Akses administrator berhasil diverifikasi.',
            'data' => [
                'user' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'role' => $request->user()->role,
                ],
                'authorized' => true,
                'scope' => 'admin_only',
            ],
        ], Response::HTTP_OK);
    }
}

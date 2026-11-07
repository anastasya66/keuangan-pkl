<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentSequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentSequenceController extends Controller
{
    public function __construct(
        protected DocumentSequenceService $sequenceService
    ) {}

    /**
     * Dapatkan daftar seluruh aturan penomoran dokumen.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar penomoran dokumen berhasil diambil',
            'data' => $this->sequenceService->getAll(),
        ], Response::HTTP_OK);
    }

    /**
     * Perbarui aturan penomoran dokumen tertentu.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:20'],
            'format' => ['required', 'string', 'max:100'],
            'padding' => ['required', 'integer', 'min:2', 'max:10'],
            'reset_frequency' => ['required', 'in:daily,monthly,yearly,never'],
        ]);

        $seq = $this->sequenceService->updateSequence($id, $validated);

        return response()->json([
            'success' => true,
            'message' => "Format penomoran '{$seq->name}' berhasil diperbarui.",
            'data' => $seq,
        ], Response::HTTP_OK);
    }
}

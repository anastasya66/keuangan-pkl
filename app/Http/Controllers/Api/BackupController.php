<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * Dapatkan daftar riwayat backup database.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Riwayat backup database berhasil diambil',
            'data' => $this->backupService->getBackups(),
        ], Response::HTTP_OK);
    }

    /**
     * Buat backup database baru secara manual.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $backup = $this->backupService->createBackup(
            $validated['notes'] ?? null,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => "Cadangan database '{$backup->filename}' berhasil dibuat.",
            'data' => $backup,
        ], Response::HTTP_CREATED);
    }

    /**
     * Unduh file cadangan database.
     */
    public function download(int $id): Response
    {
        return $this->backupService->downloadBackup($id);
    }

    /**
     * Pulihkan database dari file backup dengan konfirmasi password admin.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $result = $this->backupService->restoreBackup(
            $id,
            $validated['password'],
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => "Database berhasil dipulihkan dari '{$result['filename']}' ({$result['total_rows']} baris data).",
            'data' => $result,
        ], Response::HTTP_OK);
    }
}

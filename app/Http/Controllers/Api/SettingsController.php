<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}

    /**
     * Dapatkan seluruh pengaturan sistem yang dikelompokkan berdasarkan grup modul.
     */
    public function index(): JsonResponse
    {
        $groups = [
            'store' => $this->settingsService->getByGroup('store'),
            'transaction' => $this->settingsService->getByGroup('transaction'),
            'pos' => $this->settingsService->getByGroup('pos'),
            'inventory' => $this->settingsService->getByGroup('inventory'),
            'financial' => $this->settingsService->getByGroup('financial'),
            'accounting' => $this->settingsService->getByGroup('accounting'),
            'notification' => $this->settingsService->getByGroup('notification'),
            'security' => $this->settingsService->getByGroup('security'),
            'preferences' => $this->settingsService->getByGroup('preferences'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil diambil',
            'data' => [
                'groups' => $groups,
                'flattened' => $this->settingsService->getAll(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Endpoint publik untuk preferensi toko, mata uang, dan tanggal (bisa diakses tanpa login).
     */
    public function publicSettings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Pengaturan publik berhasil diambil',
            'data' => $this->settingsService->getPublicSettings(),
        ], Response::HTTP_OK);
    }

    /**
     * Simpan pembaruan pengaturan sistem.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $this->settingsService->updateBatch($validated['settings'], $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil disimpan dan diperbarui.',
            'data' => $this->settingsService->getAll(),
        ], Response::HTTP_OK);
    }
}

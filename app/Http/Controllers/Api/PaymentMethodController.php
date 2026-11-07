<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentMethodController extends Controller
{
    /**
     * Dapatkan daftar seluruh metode pembayaran.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PaymentMethod::with('account')->orderBy('sort_order')->orderBy('id');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar metode pembayaran berhasil diambil',
            'data' => $query->get(),
        ], Response::HTTP_OK);
    }

    /**
     * Tambah metode pembayaran baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:payment_methods,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:cash,bank,digital,debt'],
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'is_active' => ['boolean'],
            'requires_reference' => ['boolean'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['integer'],
        ]);

        $pm = PaymentMethod::create($validated);

        app(ActivityLogService::class)->log(
            action: 'create',
            module: 'settings',
            description: "Menambahkan metode pembayaran '{$pm->name}' ({$pm->code})",
            subjectType: PaymentMethod::class,
            subjectId: (string) $pm->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Metode pembayaran berhasil ditambahkan',
            'data' => $pm->load('account'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Perbarui metode pembayaran.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $pm = PaymentMethod::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:cash,bank,digital,debt'],
            'account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'is_active' => ['boolean'],
            'requires_reference' => ['boolean'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['integer'],
        ]);

        $pm->update($validated);

        app(ActivityLogService::class)->log(
            action: 'update',
            module: 'settings',
            description: "Memperbarui metode pembayaran '{$pm->name}'",
            subjectType: PaymentMethod::class,
            subjectId: (string) $pm->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Metode pembayaran berhasil diperbarui',
            'data' => $pm->fresh('account'),
        ], Response::HTTP_OK);
    }

    /**
     * Toggle status aktif metode pembayaran.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $pm = PaymentMethod::findOrFail($id);
        $pm->is_active = ! $pm->is_active;
        $pm->save();

        return response()->json([
            'success' => true,
            'message' => "Metode pembayaran '{$pm->name}' berhasil " . ($pm->is_active ? 'diaktifkan' : 'dinonaktifkan'),
            'data' => $pm,
        ], Response::HTTP_OK);
    }
}

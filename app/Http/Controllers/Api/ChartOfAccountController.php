<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreChartOfAccountRequest;
use App\Http\Requests\Accounting\UpdateChartOfAccountRequest;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChartOfAccountController extends Controller
{
    /**
     * Tampilkan daftar Chart of Accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $query = ChartOfAccount::query();

        // Filter tipe akun
        if ($request->filled('account_type')) {
            $query->where('account_type', $request->query('account_type'));
        }

        // Filter status aktif
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Pencarian berdasarkan kode atau nama
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('account_code');

        if ($request->query('all') === 'true' || $request->query('per_page') === 'all') {
            $accounts = $query->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Daftar akun akuntansi berhasil diambil.',
                'data' => ChartOfAccountResource::collection($accounts),
            ]);
        }

        $perPage = (int) $request->query('per_page', 15);
        $accounts = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar akun akuntansi berhasil diambil.',
            'data' => ChartOfAccountResource::collection($accounts),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    /**
     * Simpan akun akuntansi baru.
     */
    public function store(StoreChartOfAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (!isset($validated['normal_balance'])) {
            $validated['normal_balance'] = ChartOfAccount::determineNormalBalance($validated['account_type']);
        }

        $account = ChartOfAccount::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun akuntansi berhasil ditambahkan.',
            'data' => new ChartOfAccountResource($account),
        ], 201);
    }

    /**
     * Tampilkan detail akun akuntansi.
     */
    public function show(ChartOfAccount $chartOfAccount): JsonResponse
    {
        $this->authorize('view', $chartOfAccount);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail akun akuntansi berhasil diambil.',
            'data' => new ChartOfAccountResource($chartOfAccount),
        ]);
    }

    /**
     * Perbarui akun akuntansi.
     */
    public function update(UpdateChartOfAccountRequest $request, ChartOfAccount $chartOfAccount): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['account_type']) && !isset($validated['normal_balance'])) {
            $validated['normal_balance'] = ChartOfAccount::determineNormalBalance($validated['account_type']);
        }

        $chartOfAccount->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun akuntansi berhasil diperbarui.',
            'data' => new ChartOfAccountResource($chartOfAccount),
        ]);
    }

    /**
     * Hapus akun akuntansi jika belum digunakan dalam jurnal.
     */
    public function destroy(ChartOfAccount $chartOfAccount): JsonResponse
    {
        $this->authorize('delete', $chartOfAccount);

        if ($chartOfAccount->hasJournalEntries()) {
            throw ValidationException::withMessages([
                'account' => 'Akun tidak dapat dihapus karena sudah digunakan dalam catatan transaksi jurnal. Anda dapat menonaktifkan akun ini jika tidak ingin digunakan lagi.',
            ]);
        }

        $chartOfAccount->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Akun akuntansi berhasil dihapus.',
        ]);
    }

    /**
     * Ubah status aktif/nonaktif akun akuntansi.
     */
    public function updateStatus(Request $request, ChartOfAccount $chartOfAccount): JsonResponse
    {
        $this->authorize('toggleStatus', $chartOfAccount);

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $chartOfAccount->update([
            'is_active' => (bool) $request->input('is_active'),
        ]);

        $statusText = $chartOfAccount->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'status' => 'success',
            'message' => "Akun akuntansi berhasil {$statusText}.",
            'data' => new ChartOfAccountResource($chartOfAccount),
        ]);
    }
}

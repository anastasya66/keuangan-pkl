<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Requests\Unit\UpdateUnitStatusRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnitController extends Controller
{
    /**
     * Display a listing of units.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $query = Unit::query()->withCount('products');

        // Staff can only view active units
        if ($request->user()->isStaff()) {
            $query->where('is_active', true);
        } elseif ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name or symbol
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Return all without pagination when requested (e.g. for dropdowns)
        if ($request->boolean('all')) {
            $units = $query->orderBy('name')->get();
            return response()->json([
                'success' => true,
                'message' => 'Seluruh data satuan berhasil diambil',
                'data' => UnitResource::collection($units),
            ], Response::HTTP_OK);
        }

        $perPage = (int) $request->input('per_page', 10);
        $units = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar satuan berhasil diambil',
            'data' => UnitResource::collection($units),
            'pagination' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created unit.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

        $validated = $request->validated();
        $unit = Unit::create([
            'name' => $validated['name'],
            'symbol' => $validated['symbol'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Satuan baru berhasil ditambahkan',
            'data' => new UnitResource($unit),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        return response()->json([
            'success' => true,
            'message' => 'Detail satuan berhasil diambil',
            'data' => new UnitResource($unit->loadCount('products')),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified unit.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit);

        $validated = $request->validated();
        $unit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil diperbarui',
            'data' => new UnitResource($unit),
        ], Response::HTTP_OK);
    }

    /**
     * Update the unit active status.
     */
    public function updateStatus(UpdateUnitStatusRequest $request, Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit);

        $validated = $request->validated();
        $unit->update(['is_active' => $validated['is_active']]);

        $statusText = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Satuan berhasil {$statusText}",
            'data' => new UnitResource($unit),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified unit using Soft Delete with integrity check.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        // Prevent deletion if unit is still associated with products
        if ($unit->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Satuan tidak dapat dihapus karena masih digunakan oleh produk. Silakan nonaktifkan satuan ini jika tidak ingin digunakan.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}


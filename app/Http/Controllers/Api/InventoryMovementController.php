<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryMovementController extends Controller
{
    /**
     * Display a listing of all inventory movements.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $query = InventoryMovement::query()->with(['product.unit', 'creator']);

        // Filter by movement type
        if ($type = $request->input('movement_type')) {
            $query->where('movement_type', $type);
        }

        // Filter by product
        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        // Filter by recording user
        if ($userId = $request->input('created_by')) {
            $query->where('created_by', $userId);
        }

        // Filter by date range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('movement_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('movement_date', '<=', $endDate);
        }

        // Search by movement code, product name, or description
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('movement_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($p) use ($search) {
                      $p->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy('movement_date', $sortDirection)->orderBy('id', $sortDirection);

        $perPage = (int) $request->input('per_page', 10);
        $movements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat mutasi stok berhasil diambil',
            'data' => InventoryMovementResource::collection($movements),
            'pagination' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Display a listing of stock-in movements only.
     */
    public function stockInList(Request $request): JsonResponse
    {
        $request->merge(['movement_type' => 'stock_in']);
        return $this->index($request);
    }

    /**
     * Display a listing of stock-out movements only.
     */
    public function stockOutList(Request $request): JsonResponse
    {
        $request->merge(['movement_type' => 'stock_out']);
        return $this->index($request);
    }

    /**
     * Display a listing of stock adjustment movements only.
     */
    public function adjustmentList(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $query = InventoryMovement::query()
            ->with(['product.unit', 'creator'])
            ->whereIn('movement_type', ['adjustment_in', 'adjustment_out']);

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('movement_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('movement_date', '<=', $endDate);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('movement_code', 'like', "%{$search}%")
                  ->orWhere('adjustment_reason', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($p) use ($search) {
                      $p->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $movements = $query->latest('movement_date')->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar penyesuaian stok berhasil diambil',
            'data' => InventoryMovementResource::collection($movements),
            'pagination' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified movement detail.
     */
    public function show(InventoryMovement $movement): JsonResponse
    {
        $this->authorize('view', $movement);

        $movement->load(['product.unit', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Detail mutasi stok berhasil diambil',
            'data' => new InventoryMovementResource($movement),
        ], Response::HTTP_OK);
    }
}

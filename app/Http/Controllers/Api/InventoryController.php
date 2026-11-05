<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockAdjustmentRequest;
use App\Http\Requests\Inventory\StoreStockInRequest;
use App\Http\Requests\Inventory\StoreStockOutRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\InventoryStockResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryController extends Controller
{
    /**
     * Display a listing of product inventory stocks with filters and search.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $query = Product::query()->with(['category', 'unit'])->withCount('movements');

        // Staff can only view active products
        if ($request->user()->isStaff()) {
            $query->where('is_active', true);
        } elseif ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter by unit
        if ($unitId = $request->input('unit_id')) {
            $query->where('unit_id', $unitId);
        }

        // Filter out of stock / low stock
        if ($request->boolean('out_of_stock')) {
            $query->where('stock', '<=', 0);
        } elseif ($request->boolean('low_stock')) {
            $threshold = (float) $request->input('threshold', 5.0);
            $query->where('stock', '<=', $threshold);
        }

        // Search by name, SKU, or barcode
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Calculate summary
        $summaryQuery = clone $query;
        $totalItems = $summaryQuery->count();
        $totalStockUnits = (float) (clone $summaryQuery)->sum('stock');
        $outOfStockCount = (clone $summaryQuery)->where('stock', '<=', 0)->count();

        $perPage = (int) $request->input('per_page', 10);
        $products = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar stok produk berhasil diambil',
            'data' => InventoryStockResource::collection($products),
            'summary' => [
                'total_items' => $totalItems,
                'total_stock_units' => (float) number_format($totalStockUnits, 2, '.', ''),
                'out_of_stock_count' => $outOfStockCount,
            ],
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified product stock detail with recent movements.
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $product->load([
            'category',
            'unit',
            'movements' => function ($q) {
                $q->with('creator')->latest('movement_date')->latest('id')->limit(15);
            },
        ]);
        $product->loadCount('movements');

        return response()->json([
            'success' => true,
            'message' => 'Detail stok produk berhasil diambil',
            'data' => new InventoryStockResource($product),
        ], Response::HTTP_OK);
    }

    /**
     * Record stock in (Stok Masuk).
     */
    public function stockIn(StoreStockInRequest $request, InventoryService $inventoryService): JsonResponse
    {
        $this->authorize('stockIn', InventoryMovement::class);

        $movement = $inventoryService->recordStockIn(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Stok berhasil ditambahkan',
            'data' => new InventoryMovementResource($movement),
        ], Response::HTTP_CREATED);
    }

    /**
     * Record stock out (Stok Keluar).
     */
    public function stockOut(StoreStockOutRequest $request, InventoryService $inventoryService): JsonResponse
    {
        $this->authorize('stockOut', InventoryMovement::class);

        $movement = $inventoryService->recordStockOut(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Stok berhasil dikurangi',
            'data' => new InventoryMovementResource($movement),
        ], Response::HTTP_CREATED);
    }

    /**
     * Record stock adjustment (Penyesuaian Stok Fisik).
     */
    public function adjust(StoreStockAdjustmentRequest $request, InventoryService $inventoryService): JsonResponse
    {
        $this->authorize('adjustStock', InventoryMovement::class);

        $movement = $inventoryService->recordAdjustment(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyesuaian stok berhasil disimpan',
            'data' => new InventoryMovementResource($movement),
        ], Response::HTTP_CREATED);
    }
}

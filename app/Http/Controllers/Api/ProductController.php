<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\UpdateProductStatusRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of products with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()->with(['category', 'unit']);

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

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('selling_price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('selling_price', '<=', (float) $request->input('max_price'));
        }

        // Search by name, SKU, or barcode
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Return all without pagination when requested
        if ($request->boolean('all')) {
            $products = $query->orderBy('name')->get();
            return response()->json([
                'success' => true,
                'message' => 'Seluruh data produk berhasil diambil',
                'data' => ProductResource::collection($products),
            ], Response::HTTP_OK);
        }

        $perPage = (int) $request->input('per_page', 10);
        $products = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk berhasil diambil',
            'data' => ProductResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created product using DB transaction.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            return Product::create([
                'category_id' => $validated['category_id'],
                'unit_id' => $validated['unit_id'],
                'sku' => $validated['sku'],
                'barcode' => $validated['barcode'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'purchase_price' => $validated['purchase_price'],
                'selling_price' => $validated['selling_price'],
                'is_active' => $validated['is_active'] ?? true,
            ]);
        });

        $product->load(['category', 'unit']);

        return response()->json([
            'success' => true,
            'message' => 'Produk baru berhasil ditambahkan',
            'data' => new ProductResource($product),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load(['category', 'unit']);

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil',
            'data' => new ProductResource($product),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified product using DB transaction.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        DB::transaction(function () use ($request, $product) {
            $product->update($request->validated());
        });

        $product->load(['category', 'unit']);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data' => new ProductResource($product),
        ], Response::HTTP_OK);
    }

    /**
     * Update the product active status.
     */
    public function updateStatus(UpdateProductStatusRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validated();
        $product->update(['is_active' => $validated['is_active']]);
        $product->load(['category', 'unit']);

        $statusText = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Produk berhasil {$statusText}",
            'data' => new ProductResource($product),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified product using Soft Delete.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus (soft delete).',
        ], Response::HTTP_OK);
    }
}


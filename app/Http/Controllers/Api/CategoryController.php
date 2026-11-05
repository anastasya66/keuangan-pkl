<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryStatusRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query()->withCount('products');

        // Staff can only view active categories
        if ($request->user()->isStaff()) {
            $query->where('is_active', true);
        } elseif ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name or description
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Return all without pagination when requested (e.g. for dropdowns)
        if ($request->boolean('all')) {
            $categories = $query->orderBy('name')->get();
            return response()->json([
                'success' => true,
                'message' => 'Seluruh data kategori berhasil diambil',
                'data' => CategoryResource::collection($categories),
            ], Response::HTTP_OK);
        }

        $perPage = (int) $request->input('per_page', 10);
        $categories = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori berhasil diambil',
            'data' => CategoryResource::collection($categories),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $validated = $request->validated();
        $category = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori baru berhasil ditambahkan',
            'data' => new CategoryResource($category),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return response()->json([
            'success' => true,
            'message' => 'Detail kategori berhasil diambil',
            'data' => new CategoryResource($category->loadCount('products')),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validated();
        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data' => new CategoryResource($category),
        ], Response::HTTP_OK);
    }

    /**
     * Update the category active status.
     */
    public function updateStatus(UpdateCategoryStatusRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validated();
        $category->update(['is_active' => $validated['is_active']]);

        $statusText = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Kategori berhasil {$statusText}",
            'data' => new CategoryResource($category),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified category using Soft Delete with integrity check.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        // Prevent deletion if category is still associated with products
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh produk. Silakan nonaktifkan kategori ini jika tidak ingin digunakan.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionCategory\StoreTransactionCategoryRequest;
use App\Http\Requests\TransactionCategory\UpdateTransactionCategoryRequest;
use App\Http\Requests\TransactionCategory\UpdateTransactionCategoryStatusRequest;
use App\Http\Resources\TransactionCategoryResource;
use App\Models\TransactionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionCategoryController extends Controller
{
    /**
     * Display a listing of transaction categories with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TransactionCategory::class);

        $query = TransactionCategory::query()->withCount('transactions');

        // Staff can only view active categories
        if ($request->user()->isStaff()) {
            $query->where('is_active', true);
        } elseif ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by type (income or expense)
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Search by name or description
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Return all without pagination when requested (e.g. for transaction dropdowns)
        if ($request->boolean('all')) {
            $categories = $query->orderBy('name')->get();
            return response()->json([
                'success' => true,
                'message' => 'Seluruh data kategori transaksi berhasil diambil',
                'data' => TransactionCategoryResource::collection($categories),
            ], Response::HTTP_OK);
        }

        $perPage = (int) $request->input('per_page', 10);
        $categories = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori transaksi berhasil diambil',
            'data' => TransactionCategoryResource::collection($categories),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created transaction category.
     */
    public function store(StoreTransactionCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', TransactionCategory::class);

        $validated = $request->validated();
        $category = TransactionCategory::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori transaksi berhasil ditambahkan',
            'data' => new TransactionCategoryResource($category),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified transaction category.
     */
    public function show(TransactionCategory $transactionCategory): JsonResponse
    {
        $this->authorize('view', $transactionCategory);

        return response()->json([
            'success' => true,
            'message' => 'Detail kategori transaksi berhasil diambil',
            'data' => new TransactionCategoryResource($transactionCategory->loadCount('transactions')),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified transaction category.
     */
    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transactionCategory): JsonResponse
    {
        $this->authorize('update', $transactionCategory);

        $validated = $request->validated();
        $transactionCategory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori transaksi berhasil diperbarui',
            'data' => new TransactionCategoryResource($transactionCategory),
        ], Response::HTTP_OK);
    }

    /**
     * Update the transaction category active status.
     */
    public function updateStatus(UpdateTransactionCategoryStatusRequest $request, TransactionCategory $transactionCategory): JsonResponse
    {
        $this->authorize('update', $transactionCategory);

        $validated = $request->validated();
        $transactionCategory->update(['is_active' => $validated['is_active']]);

        $statusText = $transactionCategory->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Kategori transaksi berhasil {$statusText}",
            'data' => new TransactionCategoryResource($transactionCategory),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified transaction category using Soft Delete.
     */
    public function destroy(TransactionCategory $transactionCategory): JsonResponse
    {
        $this->authorize('delete', $transactionCategory);

        // Prevent deletion if category is still associated with financial transactions
        if ($transactionCategory->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori transaksi tidak dapat dihapus karena masih digunakan oleh data transaksi. Silakan nonaktifkan kategori ini jika tidak ingin digunakan lagi.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $transactionCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori transaksi berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}


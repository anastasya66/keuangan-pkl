<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    /**
     * Tampilkan daftar supplier dengan filter & paginasi.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $suppliers = $this->supplierService->listSuppliers($request->all(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar supplier berhasil diambil',
            'data' => $suppliers->items(),
            'pagination' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Dapatkan semua supplier aktif untuk pilihan dropdown.
     */
    public function active(): JsonResponse
    {
        $suppliers = $this->supplierService->getActiveSuppliers();

        return response()->json([
            'success' => true,
            'message' => 'Daftar supplier aktif berhasil diambil',
            'data' => $suppliers,
        ], Response::HTTP_OK);
    }

    /**
     * Buat supplier baru (hanya Admin).
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang berhak menambah supplier baru.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supplier = $this->supplierService->createSupplier($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier baru berhasil ditambahkan',
            'data' => $supplier,
        ], Response::HTTP_CREATED);
    }

    /**
     * Tampilkan detail supplier.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->loadCount('purchases');

        return response()->json([
            'success' => true,
            'message' => 'Detail supplier berhasil diambil',
            'data' => $supplier,
        ], Response::HTTP_OK);
    }

    /**
     * Perbarui data supplier (hanya Admin).
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang berhak memperbarui data supplier.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:suppliers,code,' . $supplier->id],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updated = $this->supplierService->updateSupplier($supplier, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Data supplier berhasil diperbarui',
            'data' => $updated,
        ], Response::HTTP_OK);
    }

    /**
     * Hapus data supplier (hanya Admin).
     */
    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang berhak menghapus supplier.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->supplierService->deleteSupplier($supplier);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dihapus',
        ], Response::HTTP_OK);
    }
}

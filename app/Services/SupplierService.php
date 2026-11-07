<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    /**
     * Dapatkan daftar supplier dengan pencarian dan filter paginasi.
     */
    public function listSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::query()->withCount('purchases');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Dapatkan semua supplier aktif untuk dropdown/select.
     */
    public function getActiveSuppliers(): Collection
    {
        return Supplier::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Buat data supplier baru.
     */
    public function createSupplier(array $data): Supplier
    {
        if (empty($data['code'])) {
            $data['code'] = Supplier::generateCode();
        }

        return Supplier::create($data);
    }

    /**
     * Perbarui data supplier.
     */
    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh();
    }

    /**
     * Hapus supplier (soft delete).
     */
    public function deleteSupplier(Supplier $supplier): bool
    {
        if ($supplier->purchases()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => "Supplier '{$supplier->name}' tidak dapat dihapus karena memiliki riwayat transaksi pembelian.",
            ]);
        }

        return $supplier->delete();
    }
}

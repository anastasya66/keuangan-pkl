<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseApiRequest;
use App\Models\Product;
use Closure;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $product = Product::where('id', $value)->whereNull('deleted_at')->first();
                    if ($product && ! $product->is_active) {
                        $fail('Produk yang dipilih tidak aktif.');
                    }
                },
            ],
            'actual_stock' => [
                'required',
                'numeric',
                'gte:0',
            ],
            'adjustment_reason' => [
                'required',
                'string',
                'max:500',
            ],
            'movement_date' => [
                'required',
                'date',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk yang dipilih tidak valid atau tidak ditemukan.',
            'actual_stock.required' => 'Jumlah stok fisik hasil pemeriksaan wajib diisi.',
            'actual_stock.numeric' => 'Jumlah stok fisik harus berupa angka.',
            'actual_stock.gte' => 'Jumlah stok fisik tidak boleh bernilai negatif (minimal 0).',
            'adjustment_reason.required' => 'Alasan penyesuaian stok wajib diisi.',
            'adjustment_reason.max' => 'Alasan penyesuaian stok maksimal 500 karakter.',
            'movement_date.required' => 'Tanggal penyesuaian stok wajib diisi.',
            'movement_date.date' => 'Format tanggal penyesuaian tidak valid.',
        ];
    }
}


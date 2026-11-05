<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseApiRequest;
use App\Models\Product;
use Closure;
use Illuminate\Validation\Rule;

class StoreStockOutRequest extends BaseApiRequest
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
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'movement_date' => [
                'required',
                'date',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk yang dipilih tidak valid atau tidak ditemukan.',
            'quantity.required' => 'Jumlah kuantitas stok keluar wajib diisi.',
            'quantity.numeric' => 'Jumlah kuantitas harus berupa angka.',
            'quantity.gt' => 'Jumlah kuantitas stok keluar harus lebih besar dari 0.',
            'movement_date.required' => 'Tanggal mutasi stok wajib diisi.',
            'movement_date.date' => 'Format tanggal mutasi tidak valid.',
            'reference_number.max' => 'Nomor referensi maksimal 100 karakter.',
        ];
    }
}

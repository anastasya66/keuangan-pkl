<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseApiRequest;
use App\Models\Category;
use App\Models\Unit;
use Closure;
use Illuminate\Validation\Rule;

class StoreProductRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $category = Category::where('id', $value)->whereNull('deleted_at')->first();
                    if ($category && ! $category->is_active) {
                        $fail('Kategori yang dipilih tidak aktif.');
                    }
                },
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $unit = Unit::where('id', $value)->whereNull('deleted_at')->first();
                    if ($unit && ! $unit->is_active) {
                        $fail('Satuan yang dipilih tidak aktif.');
                    }
                },
            ],
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->whereNull('deleted_at'),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori produk wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'unit_id.required' => 'Satuan produk wajib dipilih.',
            'unit_id.exists' => 'Satuan yang dipilih tidak valid.',
            'sku.required' => 'SKU produk wajib diisi.',
            'sku.max' => 'SKU produk maksimal 50 karakter.',
            'sku.unique' => 'SKU produk sudah digunakan.',
            'barcode.max' => 'Barcode maksimal 100 karakter.',
            'barcode.unique' => 'Barcode sudah digunakan.',
            'name.required' => 'Nama produk wajib diisi.',
            'name.max' => 'Nama produk maksimal 150 karakter.',
            'purchase_price.required' => 'Harga beli wajib diisi.',
            'purchase_price.numeric' => 'Harga beli harus berupa angka.',
            'purchase_price.min' => 'Harga beli tidak boleh bernilai negatif.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.numeric' => 'Harga jual harus berupa angka.',
            'selling_price.min' => 'Harga jual tidak boleh bernilai negatif.',
            'is_active.boolean' => 'Status harus berupa nilai boolean.',
        ];
    }
}


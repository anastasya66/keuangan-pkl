<?php

namespace App\Http\Requests\TransactionCategory;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class StoreTransactionCategoryRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('transaction_categories', 'name')
                    ->where('type', $this->input('type'))
                    ->whereNull('deleted_at'),
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['income', 'expense']),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori transaksi wajib diisi.',
            'name.max' => 'Nama kategori transaksi maksimal 100 karakter.',
            'name.unique' => 'Nama kategori transaksi sudah terdaftar untuk jenis transaksi ini.',
            'type.required' => 'Jenis kategori transaksi wajib dipilih (income atau expense).',
            'type.in' => 'Jenis kategori transaksi hanya boleh income atau expense.',
            'is_active.boolean' => 'Status harus berupa nilai boolean.',
        ];
    }
}

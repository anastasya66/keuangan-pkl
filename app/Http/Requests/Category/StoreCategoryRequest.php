<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends BaseApiRequest
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
                Rule::unique('categories', 'name')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'name.unique' => 'Nama kategori sudah digunakan.',
            'is_active.boolean' => 'Status harus berupa nilai boolean.',
        ];
    }
}


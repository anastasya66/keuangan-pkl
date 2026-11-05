<?php

namespace App\Http\Requests\Unit;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $unitId = is_object($unit) ? $unit->id : $unit;

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'name')->ignore($unitId)->whereNull('deleted_at'),
            ],
            'symbol' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'symbol')->ignore($unitId)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama satuan wajib diisi.',
            'name.max' => 'Nama satuan maksimal 50 karakter.',
            'name.unique' => 'Nama satuan sudah digunakan.',
            'symbol.required' => 'Simbol satuan wajib diisi.',
            'symbol.max' => 'Simbol satuan maksimal 20 karakter.',
            'symbol.unique' => 'Simbol satuan sudah digunakan.',
            'is_active.boolean' => 'Status harus berupa nilai boolean.',
        ];
    }
}


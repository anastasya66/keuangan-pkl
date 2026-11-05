<?php

namespace App\Http\Requests\Unit;

use App\Http\Requests\BaseApiRequest;

class UpdateUnitStatusRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required' => 'Status aktif wajib disertakan.',
            'is_active.boolean' => 'Status harus berupa nilai boolean.',
        ];
    }
}


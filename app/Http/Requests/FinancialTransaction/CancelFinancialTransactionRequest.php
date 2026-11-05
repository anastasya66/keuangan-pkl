<?php

namespace App\Http\Requests\FinancialTransaction;

use App\Http\Requests\BaseApiRequest;

class CancelFinancialTransactionRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.max' => 'Alasan pembatalan transaksi maksimal 500 karakter.',
        ];
    }
}

<?php

namespace App\Http\Requests\FinancialTransaction;

use App\Http\Requests\BaseApiRequest;
use App\Models\TransactionCategory;
use Closure;
use Illuminate\Validation\Rule;

class StoreFinancialTransactionRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('*incomes*') || $this->is('api/v1/incomes*') || $this->is('api/incomes*')) {
            $this->merge(['transaction_type' => 'income']);
        } elseif ($this->routeIs('*expenses*') || $this->is('api/v1/expenses*') || $this->is('api/expenses*')) {
            $this->merge(['transaction_type' => 'expense']);
        }
    }

    public function rules(): array
    {
        $transactionType = $this->input('transaction_type');

        return [
            'transaction_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('financial_transactions', 'transaction_code')->whereNull('deleted_at'),
            ],
            'transaction_type' => [
                'required',
                'string',
                Rule::in(['income', 'expense']),
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('transaction_categories', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail) use ($transactionType) {
                    $category = TransactionCategory::where('id', $value)->whereNull('deleted_at')->first();
                    if (! $category) {
                        return;
                    }

                    if (! $category->is_active) {
                        $fail('Kategori transaksi yang dipilih tidak aktif.');
                        return;
                    }

                    if ($transactionType && $category->type !== $transactionType) {
                        $fail("Kategori '{$category->name}' adalah kategori {$category->type}, tidak sesuai dengan jenis transaksi {$transactionType}.");
                    }
                },
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'transaction_date' => [
                'required',
                'date',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['completed', 'cancelled']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_type.required' => 'Jenis transaksi (income atau expense) wajib disertakan.',
            'transaction_type.in' => 'Jenis transaksi hanya boleh income atau expense.',
            'category_id.required' => 'Kategori transaksi wajib dipilih.',
            'category_id.exists' => 'Kategori transaksi yang dipilih tidak valid.',
            'amount.required' => 'Nominal transaksi wajib diisi.',
            'amount.numeric' => 'Nominal transaksi harus berupa angka.',
            'amount.gt' => 'Nominal transaksi harus lebih besar dari 0.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Format tanggal transaksi tidak valid.',
            'transaction_code.unique' => 'Kode transaksi sudah digunakan.',
        ];
    }
}

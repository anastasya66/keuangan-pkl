<?php

namespace App\Http\Requests\Accounting;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ChartOfAccount::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_code' => ['required', 'string', 'max:30', 'unique:chart_of_accounts,account_code'],
            'account_name' => ['required', 'string', 'max:150'],
            'account_type' => ['required', 'string', Rule::in(['assets', 'liabilities', 'equity', 'revenue', 'expenses'])],
            'normal_balance' => ['nullable', 'string', Rule::in(['debit', 'credit'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Prepare inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('account_type') && !$this->has('normal_balance')) {
            $this->merge([
                'normal_balance' => ChartOfAccount::determineNormalBalance($this->input('account_type')),
            ]);
        }
    }
}


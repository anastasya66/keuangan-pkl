<?php

namespace App\Http\Requests\Accounting;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChartOfAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $account = $this->route('chart_of_account') ?? $this->route('account');
        return $this->user()?->can('update', $account) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $accountId = $this->route('chart_of_account')?->id ?? $this->route('account')?->id ?? $this->route('id');

        return [
            'account_code' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('chart_of_accounts', 'account_code')->ignore($accountId)],
            'account_name' => ['sometimes', 'required', 'string', 'max:150'],
            'account_type' => ['sometimes', 'required', 'string', Rule::in(['assets', 'liabilities', 'equity', 'revenue', 'expenses'])],
            'normal_balance' => ['sometimes', 'required', 'string', Rule::in(['debit', 'credit'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $journal = $this->route('journal');
        return $this->user()?->can('update', $journal) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'journal_date' => ['sometimes', 'required', 'date'],
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:2'],
            'items.*.account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.debit' => ['required', 'numeric', 'min:0'],
            'items.*.credit' => ['required', 'numeric', 'min:0'],
        ];
    }
}

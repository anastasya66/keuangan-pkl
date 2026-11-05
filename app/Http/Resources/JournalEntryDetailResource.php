<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'journal_id' => $this->journal_id,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'account_code' => $this->account->account_code,
                    'account_name' => $this->account->account_name,
                    'account_type' => $this->account->account_type,
                    'normal_balance' => $this->account->normal_balance,
                ];
            }),
            'description' => $this->description,
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

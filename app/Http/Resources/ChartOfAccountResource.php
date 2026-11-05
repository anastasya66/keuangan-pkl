<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartOfAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $typeLabels = [
            'assets' => 'Aset',
            'liabilities' => 'Kewajiban',
            'equity' => 'Modal',
            'revenue' => 'Pendapatan',
            'expenses' => 'Beban',
        ];

        return [
            'id' => $this->id,
            'account_code' => $this->account_code,
            'account_name' => $this->account_name,
            'account_type' => $this->account_type,
            'account_type_label' => $typeLabels[$this->account_type] ?? ucfirst($this->account_type),
            'normal_balance' => $this->normal_balance,
            'normal_balance_label' => ucfirst($this->normal_balance),
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'has_journal_entries' => $this->hasJournalEntries(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}


<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusLabels = [
            'draft' => 'Draft',
            'posted' => 'Posted',
            'reversed' => 'Reversed',
        ];

        return [
            'id' => $this->id,
            'journal_number' => $this->journal_number,
            'journal_date' => $this->journal_date?->format('Y-m-d'),
            'description' => $this->description,
            'reference_number' => $this->reference_number,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'status' => $this->status,
            'status_label' => $statusLabels[$this->status] ?? ucfirst($this->status),
            'total_debit' => (float) $this->total_debit,
            'total_credit' => (float) $this->total_credit,
            'is_balanced' => (round((float) $this->total_debit, 2) === round((float) $this->total_credit, 2) && (float) $this->total_debit > 0),
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'role' => $this->creator->role,
                ];
            }),
            'posted_by' => $this->whenLoaded('poster', function () {
                return [
                    'id' => $this->poster->id,
                    'name' => $this->poster->name,
                    'role' => $this->poster->role,
                ];
            }),
            'posted_at' => $this->posted_at?->format('Y-m-d H:i:s'),
            'reversed_by' => $this->whenLoaded('reverser', function () {
                return [
                    'id' => $this->reverser->id,
                    'name' => $this->reverser->name,
                    'role' => $this->reverser->role,
                ];
            }),
            'reversed_at' => $this->reversed_at?->format('Y-m-d H:i:s'),
            'reversal_journal_id' => $this->reversal_journal_id,
            'reversal_journal' => $this->whenLoaded('reversalJournal', function () {
                return [
                    'id' => $this->reversalJournal->id,
                    'journal_number' => $this->reversalJournal->journal_number,
                    'status' => $this->reversalJournal->status,
                ];
            }),
            'details' => JournalEntryDetailResource::collection($this->whenLoaded('details')),
            'details_count' => $this->details()->count(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

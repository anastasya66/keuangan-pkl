<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialTransactionResource extends JsonResource
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
            'transaction_code' => $this->transaction_code,
            'transaction_type' => $this->transaction_type,
            'amount' => (string) number_format((float) $this->amount, 2, '.', ''),
            'amount_numeric' => (float) $this->amount,
            'transaction_date' => $this->transaction_date?->format('Y-m-d') ?? $this->transaction_date,
            'description' => $this->description,
            'status' => $this->status,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'type' => $this->category->type,
                    'is_active' => (bool) $this->category->is_active,
                ];
            }),
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                    'role' => $this->creator->role,
                ];
            }),
            'updated_by' => $this->whenLoaded('updater', function () {
                return $this->updater ? [
                    'id' => $this->updater->id,
                    'name' => $this->updater->name,
                ] : null;
            }),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancelled_by' => $this->whenLoaded('canceller', function () {
                return $this->canceller ? [
                    'id' => $this->canceller->id,
                    'name' => $this->canceller->name,
                ] : null;
            }),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

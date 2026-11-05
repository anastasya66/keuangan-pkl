<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
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
            'movement_code' => $this->movement_code,
            'product_id' => $this->product_id,
            'movement_type' => $this->movement_type,
            'quantity' => (string) number_format((float) $this->quantity, 2, '.', ''),
            'quantity_numeric' => (float) $this->quantity,
            'stock_before' => (string) number_format((float) $this->stock_before, 2, '.', ''),
            'stock_before_numeric' => (float) $this->stock_before,
            'stock_after' => (string) number_format((float) $this->stock_after, 2, '.', ''),
            'stock_after_numeric' => (float) $this->stock_after,
            'adjustment_reason' => $this->adjustment_reason,
            'description' => $this->description,
            'reference_number' => $this->reference_number,
            'movement_date' => $this->movement_date?->format('Y-m-d') ?? $this->movement_date,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'sku' => $this->product->sku,
                    'name' => $this->product->name,
                    'current_stock' => (float) $this->product->stock,
                    'unit' => $this->product->unit ? [
                        'id' => $this->product->unit->id,
                        'name' => $this->product->unit->name,
                        'symbol' => $this->product->unit->symbol,
                    ] : null,
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
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}


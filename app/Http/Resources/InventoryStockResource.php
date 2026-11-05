<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockResource extends JsonResource
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
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'stock' => (string) number_format((float) $this->stock, 2, '.', ''),
            'stock_numeric' => (float) $this->stock,
            'is_out_of_stock' => (float) $this->stock <= 0,
            'purchase_price' => (string) number_format((float) $this->purchase_price, 2, '.', ''),
            'selling_price' => (string) number_format((float) $this->selling_price, 2, '.', ''),
            'is_active' => (bool) $this->is_active,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'id' => $this->unit->id,
                    'name' => $this->unit->name,
                    'symbol' => $this->unit->symbol,
                ];
            }),
            'movements_count' => $this->whenCounted('movements'),
            'recent_movements' => InventoryMovementResource::collection($this->whenLoaded('movements')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

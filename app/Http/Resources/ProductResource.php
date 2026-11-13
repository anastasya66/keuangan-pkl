<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'image' => $this->image,
            'image_url' => $this->image_url,
            'purchase_price' => (string) number_format((float) $this->purchase_price, 2, '.', ''),
            'selling_price' => (string) number_format((float) $this->selling_price, 2, '.', ''),
            'purchase_price_numeric' => (float) $this->purchase_price,
            'selling_price_numeric' => (float) $this->selling_price,
            'stock' => (float) $this->stock,
            'average_cost' => (float) $this->average_cost,
            'min_stock' => (float) $this->min_stock,
            'is_active' => (bool) $this->is_active,
            'is_consignment' => (bool) $this->is_consignment,
            'consignor_id' => $this->consignor_id,
            'consignor' => $this->whenLoaded('consignor', function () {
                return [
                    'id' => $this->consignor->id,
                    'code' => $this->consignor->code,
                    'name' => $this->consignor->name,
                    'phone' => $this->consignor->phone,
                ];
            }),
            'consignment_cost_price' => (float) $this->consignment_cost_price,
            'commission_rate' => (float) $this->commission_rate,
            'commission_amount' => (float) $this->commission_amount,
            'initial_consignment_stock' => (float) $this->initial_consignment_stock,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'is_active' => (bool) $this->category->is_active,
                ];
            }),
            'category_id' => $this->category_id,
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'id' => $this->unit->id,
                    'name' => $this->unit->name,
                    'symbol' => $this->unit->symbol,
                    'is_active' => (bool) $this->unit->is_active,
                ];
            }),
            'unit_id' => $this->unit_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}


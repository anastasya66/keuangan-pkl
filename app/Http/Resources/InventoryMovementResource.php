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
        $code = $this->movement_code ?: ($this->reference_number ?: ('MUT-' . str_pad($this->id, 5, '0', STR_PAD_LEFT)));
        $desc = $this->description ?: $this->adjustment_reason;
        if (empty($desc)) {
            if (in_array($this->movement_type, ['sale', 'stock_out'])) {
                $desc = 'Penjualan Kasir POS' . ($this->reference_number ? " ({$this->reference_number})" : '');
            } elseif (in_array($this->movement_type, ['purchase', 'stock_in'])) {
                $desc = 'Pembelian Stok' . ($this->reference_number ? " ({$this->reference_number})" : '');
            } else {
                $desc = 'Penyesuaian Stok Toko';
            }
        }

        $typeLabels = [
            'purchase' => 'Masuk (Pembelian)',
            'stock_in' => 'Stok Masuk',
            'sale' => 'Keluar (Penjualan)',
            'stock_out' => 'Stok Keluar',
            'adjustment_in' => 'Opname Bertambah',
            'adjustment_out' => 'Opname Berkurang',
            'initial_stock' => 'Stok Awal',
            'sale_return' => 'Retur Penjualan',
            'purchase_return' => 'Retur Pembelian',
            'purchase_reversal' => 'Batal Pembelian',
        ];
        $typeLabel = $typeLabels[$this->movement_type] ?? ucfirst($this->movement_type);

        $dateFormatted = $this->created_at ? $this->created_at->format('d M Y, H:i') : ($this->movement_date?->format('d M Y') ?? '-');

        return [
            'id' => $this->id,
            'movement_code' => $code,
            'code' => $code,
            'product_id' => $this->product_id,
            'movement_type' => $this->movement_type,
            'type' => $this->movement_type,
            'movement_type_label' => $typeLabel,
            'type_label' => $typeLabel,
            'quantity' => (string) number_format((float) $this->quantity, 2, '.', ''),
            'quantity_numeric' => (float) $this->quantity,
            'stock_before' => (string) number_format((float) $this->stock_before, 2, '.', ''),
            'stock_before_numeric' => (float) $this->stock_before,
            'stock_after' => (string) number_format((float) $this->stock_after, 2, '.', ''),
            'stock_after_numeric' => (float) $this->stock_after,
            'adjustment_reason' => $this->adjustment_reason,
            'description' => $desc,
            'notes' => $desc,
            'reference_number' => $this->reference_number,
            'movement_date' => $this->movement_date?->format('Y-m-d') ?? $this->movement_date,
            'date' => $dateFormatted,
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
            'creator_name' => $this->creator?->name ?? 'Administrator',
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                    'role' => $this->creator->role,
                ];
            }, [
                'name' => $this->creator?->name ?? 'Administrator',
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}


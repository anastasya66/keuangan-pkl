<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'is_consignment',
        'consignor_id',
        'quantity',
        'unit_price',
        'cost_price',
        'consignment_cost',
        'discount_amount',
        'commission_amount',
        'subtotal',
        'cogs_total',
        'settlement_status',
        'settled_at',
        'settlement_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_consignment' => 'boolean',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'consignment_cost' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'cogs_total' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    /**
     * Penjualan induk.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Produk yang dijual.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Mitra konsinyor pemilik produk titipan ini.
     */
    public function consignor(): BelongsTo
    {
        return $this->belongsTo(Consignor::class);
    }

    /**
     * Data penyelesaian/settlement setoran.
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(ConsignmentSettlement::class, 'settlement_id');
    }
}


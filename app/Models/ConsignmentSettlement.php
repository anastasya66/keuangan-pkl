<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsignmentSettlement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'settlement_number',
        'consignor_id',
        'settlement_date',
        'total_sold_qty',
        'total_sales_amount',
        'total_consignor_amount',
        'total_store_commission',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'total_sold_qty' => 'decimal:2',
            'total_sales_amount' => 'decimal:2',
            'total_consignor_amount' => 'decimal:2',
            'total_store_commission' => 'decimal:2',
        ];
    }

    /**
     * Mitra konsinyor penerima setoran.
     */
    public function consignor(): BelongsTo
    {
        return $this->belongsTo(Consignor::class);
    }

    /**
     * Staf yang memproses settlement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Item penjualan yang diselesaikan pada transaksi settlement ini.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'settlement_id');
    }

    /**
     * Generate nomor settlement otomatis (SET-YYYYMMDD-0001).
     */
    public static function generateNumber(?string $date = null): string
    {
        $prefix = 'SET-' . date('Ymd', strtotime($date ?: 'today')) . '-';

        $last = self::where('settlement_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($last && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $last->settlement_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextNumber);
    }
}


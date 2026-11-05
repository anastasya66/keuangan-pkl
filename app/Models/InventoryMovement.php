<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'movement_code',
        'product_id',
        'movement_type',
        'quantity',
        'stock_before',
        'stock_after',
        'adjustment_reason',
        'description',
        'reference_number',
        'movement_date',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
            'movement_date' => 'date',
        ];
    }

    /**
     * Produk yang mengalami mutasi stok.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Pengguna yang mencatat mutasi stok.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope untuk menyaring mutasi stok masuk (stock_in).
     */
    public function scopeStockIn(Builder $query): Builder
    {
        return $query->where('movement_type', 'stock_in');
    }

    /**
     * Scope untuk menyaring mutasi stok keluar (stock_out).
     */
    public function scopeStockOut(Builder $query): Builder
    {
        return $query->where('movement_type', 'stock_out');
    }

    /**
     * Scope untuk menyaring mutasi penyesuaian stok (adjustment_in & adjustment_out).
     */
    public function scopeAdjustments(Builder $query): Builder
    {
        return $query->whereIn('movement_type', ['adjustment_in', 'adjustment_out']);
    }

    /**
     * Generate kode mutasi unik otomatis (Format: MOV-YYYYMMDD-XXXX).
     */
    public static function generateMovementCode(?string $date = null): string
    {
        $dateStr = Carbon::parse($date ?? now())->format('Ymd');
        $prefix = "MOV-{$dateStr}";

        $lastMovement = static::where('movement_code', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastMovement && preg_match('/-(\d{4})$/', $lastMovement->movement_code, $matches)) {
            $nextSequence = (int) $matches[1] + 1;
        } else {
            $nextSequence = 1;
        }

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }
}

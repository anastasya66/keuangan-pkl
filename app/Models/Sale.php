<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'transaction_number',
        'cashier_id',
        'customer_name',
        'sale_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'cogs_total',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'cancelled_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'cogs_total' => 'decimal:2',
        ];
    }

    /**
     * Kasir yang melayani penjualan.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Staf yang membatalkan transaksi bila terjadi retur/batal.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Daftar item barang yang terjual.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Jurnal akuntansi yang mencatat transaksi penjualan ini.
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'source_id')->where('source_type', 'sale');
    }

    /**
     * Transaksi gateway pembayaran (QRIS, transfer, dll).
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Laba kotor dari transaksi penjualan (Grand Total - HPP).
     */
    public function getGrossProfitAttribute(): float
    {
        return round((float) $this->grand_total - (float) $this->cogs_total, 2);
    }

    /**
     * Generate nomor transaksi kasir otomatis (POS-YYYYMMDD-0001).
     */
    public static function generateTransactionNumber(?string $date = null): string
    {
        $dateObj = $date ? Carbon::parse($date) : Carbon::today();
        $dateStr = $dateObj->format('Ymd');
        $prefix = "POS-{$dateStr}-";

        $last = self::withTrashed()
            ->where('transaction_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($last && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $last->transaction_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextNumber);
    }
}


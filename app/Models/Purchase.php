<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'purchase_date',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'due_date',
        'notes',
        'received_at',
        'received_by',
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
            'purchase_date' => 'date',
            'due_date' => 'date',
            'received_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * Supplier tujuan pembelian.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Staf yang membuat purchase order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Staf yang menerima barang masuk.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Item barang yang dibeli.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Riwayat pembayaran cicilan utang tempo pembelian.
     */
    public function debtPayments(): HasMany
    {
        return $this->hasMany(SupplierDebtPayment::class);
    }

    /**
     * Jurnal akuntansi yang mencatat transaksi pembelian ini.
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'source_id')->where('source_type', 'purchase');
    }

    /**
     * Sisa utang yang belum dibayarkan.
     */
    public function getRemainingDebtAttribute(): float
    {
        return max(0.00, round((float) $this->grand_total - (float) $this->paid_amount, 2));
    }

    /**
     * Generate nomor invoice pembelian otomatis (PO-YYYYMMDD-0001).
     */
    public static function generateInvoiceNumber(?string $date = null): string
    {
        $dateObj = $date ? Carbon::parse($date) : Carbon::today();
        $dateStr = $dateObj->format('Ymd');
        $prefix = "PO-{$dateStr}-";

        $last = self::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($last && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $last->invoice_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextNumber);
    }
}

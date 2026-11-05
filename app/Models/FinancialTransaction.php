<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'transaction_code',
        'transaction_type',
        'category_id',
        'amount',
        'transaction_date',
        'description',
        'created_by',
        'updated_by',
        'status',
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
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Kategori transaksi keuangan.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    /**
     * Pengguna yang mencatat transaksi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pengguna yang memperbarui transaksi terakhir kali.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Pengguna yang membatalkan transaksi.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Scope transaksi pemasukan (income).
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('transaction_type', 'income');
    }

    /**
     * Scope transaksi pengeluaran (expense).
     */
    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('transaction_type', 'expense');
    }

    /**
     * Scope transaksi berstatus sukses / aktif (completed).
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope transaksi yang dibatalkan (cancelled).
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Cek apakah status transaksi completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Cek apakah status transaksi cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Relasi ke jurnal akuntansi yang terkait transaksi ini.
     */
    public function journalEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JournalEntry::class, 'source_id')->where('source_type', 'financial_transaction');
    }

    /**
     * Jurnal aktif yang belum dibatalkan/reversal.
     */
    public function activeJournal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(JournalEntry::class, 'source_id')
            ->where('source_type', 'financial_transaction')
            ->where('status', '!=', 'reversed');
    }

    /**
     * Generate kode transaksi unik otomatis (Format: TRX-YYYYMMDD-XXXX).
     */
    public static function generateTransactionCode(?string $date = null): string
    {
        $dateStr = Carbon::parse($date ?? now())->format('Ymd');
        $prefix = "TRX-{$dateStr}";

        $lastTransaction = static::withTrashed()
            ->where('transaction_code', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastTransaction && preg_match('/-(\d{4})$/', $lastTransaction->transaction_code, $matches)) {
            $nextSequence = (int) $matches[1] + 1;
        } else {
            $nextSequence = 1;
        }

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }
}


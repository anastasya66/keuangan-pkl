<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JournalEntry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'journal_number',
        'journal_date',
        'description',
        'reference_number',
        'source_type',
        'source_id',
        'status',
        'total_debit',
        'total_credit',
        'created_by',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'reversal_journal_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
        ];
    }

    /**
     * Detail baris jurnal (double entry).
     */
    public function details(): HasMany
    {
        return $this->hasMany(JournalEntryDetail::class, 'journal_id');
    }

    /**
     * Pengguna pembuat jurnal.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pengguna yang memposting jurnal.
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Pengguna yang melakukan reversal jurnal.
     */
    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * Jurnal pembalik yang membatalkan jurnal ini.
     */
    public function reversalJournal(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_id');
    }

    /**
     * Jurnal sumber yang dibalik oleh jurnal ini.
     */
    public function reversedOriginal(): HasOne
    {
        return $this->hasOne(JournalEntry::class, 'reversal_journal_id');
    }

    /**
     * Scope status draft.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope status posted.
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope status reversed.
     */
    public function scopeReversed(Builder $query): Builder
    {
        return $query->where('status', 'reversed');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    /**
     * Generate nomor jurnal otomatis unik (Format: JRN-YYYYMMDD-XXXX).
     */
    public static function generateJournalNumber(?string $date = null): string
    {
        $dateStr = Carbon::parse($date ?? now())->format('Ymd');
        $prefix = "JRN-{$dateStr}";

        $last = static::where('journal_number', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->first();

        if ($last && preg_match('/-(\d{4})$/', $last->journal_number, $matches)) {
            $nextSequence = (int) $matches[1] + 1;
        } else {
            $nextSequence = 1;
        }

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }
}

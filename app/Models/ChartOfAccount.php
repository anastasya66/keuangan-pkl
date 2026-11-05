<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chart_of_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'normal_balance',
        'description',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Detail baris jurnal yang menggunakan akun ini.
     */
    public function journalDetails(): HasMany
    {
        return $this->hasMany(JournalEntryDetail::class, 'account_id');
    }

    /**
     * Konfigurasi mapping akun otomatis.
     */
    public function mappings(): HasMany
    {
        return $this->hasMany(AccountingMapping::class, 'account_id');
    }

    /**
     * Cek apakah akun sudah digunakan dalam catatan detail jurnal.
     */
    public function hasJournalEntries(): bool
    {
        return $this->journalDetails()->exists();
    }

    /**
     * Tentukan normal balance berdasarkan kategori akun.
     */
    public static function determineNormalBalance(string $accountType): string
    {
        return match (strtolower($accountType)) {
            'assets', 'asset', 'expenses', 'expense' => 'debit',
            'liabilities', 'liability', 'equity', 'revenue' => 'credit',
            default => 'debit',
        };
    }

    /**
     * Scope akun berstatus aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope berdasarkan jenis/kategori akun.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('account_type', $type);
    }
}


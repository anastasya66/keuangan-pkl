<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'accounting_mappings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mapping_code',
        'name',
        'account_id',
        'description',
    ];

    /**
     * Akun yang dipetakan.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Ambil account_id berdasarkan kode mapping.
     */
    public static function getAccountId(string $code): ?int
    {
        return static::where('mapping_code', $code)->value('account_id');
    }
}


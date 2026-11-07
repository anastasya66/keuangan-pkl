<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'contact_person',
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
     * Transaksi pembelian dari supplier ini.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Generate kode supplier otomatis (SUP-0001).
     */
    public static function generateCode(): string
    {
        $last = self::withTrashed()
            ->where('code', 'like', 'SUP-%')
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($last && preg_match('/SUP-(\d+)/', $last->code, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('SUP-%04d', $nextNumber);
    }
}

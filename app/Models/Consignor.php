<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consignor extends Model
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
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'default_commission_rate',
        'notes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Produk konsinyasi milik mitra ini.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Item penjualan konsinyasi milik mitra ini.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Riwayat settlement/pembayaran ke mitra ini.
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(ConsignmentSettlement::class);
    }

    /**
     * Generate kode konsinyor otomatis format KSY-0001.
     */
    public static function generateCode(): string
    {
        $last = self::withTrashed()
            ->where('code', 'like', 'KSY-%')
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($last && preg_match('/KSY-(\d+)/', $last->code, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('KSY-%04d', $nextNumber);
    }
}


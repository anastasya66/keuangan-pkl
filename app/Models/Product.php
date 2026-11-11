<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'unit_id',
        'sku',
        'barcode',
        'name',
        'description',
        'image',
        'purchase_price',
        'selling_price',
        'average_cost',
        'stock',
        'min_stock',
        'is_active',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'image_url',
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
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'average_cost' => 'decimal:2',
            'stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
        ];
    }

    /**
     * Kategori tempat produk ini bernaung.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Satuan yang digunakan oleh produk ini.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Seluruh riwayat mutasi stok untuk produk ini.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Item pembelian produk ini.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Item penjualan produk ini.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Harga pokok persediaan efektif (Moving Average Cost, fallback ke purchase_price).
     */
    public function getEffectiveCostAttribute(): float
    {
        $avg = (float) $this->average_cost;
        if ($avg > 0) {
            return $avg;
        }
        return (float) $this->purchase_price;
    }

    /**
     * Total nilai persediaan untuk produk ini (Stok * Effective Cost).
     */
    public function getInventoryValueAttribute(): float
    {
        return round(max(0.0, (float) $this->stock) * $this->effective_cost, 2);
    }

    /**
     * Status stok apakah menipis (dibawah atau sama dengan stok minimum).
     */
    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->stock <= (float) $this->min_stock;
    }

    /**
     * URL Foto Produk yang dapat diakses publik.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }
        return asset('storage/' . ltrim($this->image, '/'));
    }

    /**
     * Generate format SKU produk otomatis jika dikosongkan.
     */
    public static function generateSku(?int $categoryId = null): string
    {
        $prefix = 'PRD-';
        if ($categoryId) {
            $cat = Category::find($categoryId);
            if ($cat && !empty($cat->name)) {
                $cleanName = strtoupper(preg_replace('/[^A-Za-z]/', '', $cat->name));
                $prefix .= (strlen($cleanName) >= 3 ? substr($cleanName, 0, 3) : str_pad($cleanName, 3, 'X')) . '-';
            }
        }

        $last = self::withTrashed()
            ->where('sku', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($last && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $last->sku, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextNumber);
    }

    /**
     * Cek kecukupan stok produk terhadap kuantitas yang diminta.
     */
    public function hasSufficientStock(float $quantity): bool
    {
        return (float) $this->stock >= $quantity;
    }
}

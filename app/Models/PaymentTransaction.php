<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'sale_id',
        'user_id',
        'payment_method',
        'provider',
        'gross_amount',
        'status',
        'qr_string',
        'qr_image_url',
        'bank_name',
        'va_number',
        'reference_number',
        'customer_name',
        'expires_at',
        'paid_at',
        'payload',
        'metadata',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'payload' => 'array',
        'metadata' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['settlement', 'paid', 'capture']);
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expire') {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }
}

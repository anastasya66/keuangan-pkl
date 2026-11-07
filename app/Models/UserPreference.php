<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'theme',
        'locale',
        'table_per_page',
        'notifications_enabled',
        'sound_enabled',
        'preferences',
    ];

    protected function casts(): array
    {
        return [
            'table_per_page' => 'integer',
            'notifications_enabled' => 'boolean',
            'sound_enabled' => 'boolean',
            'preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

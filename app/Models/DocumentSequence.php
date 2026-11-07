<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'document_type',
        'name',
        'prefix',
        'format',
        'padding',
        'current_sequence',
        'reset_frequency',
        'last_reset_date',
    ];

    protected function casts(): array
    {
        return [
            'padding' => 'integer',
            'current_sequence' => 'integer',
            'last_reset_date' => 'date',
        ];
    }
}

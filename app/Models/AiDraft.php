<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDraft extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'suggestions' => 'array',
            'applied_at' => 'datetime',
        ];
    }
}

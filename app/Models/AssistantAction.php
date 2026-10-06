<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantAction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'files' => 'array',
            'expires_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }
}

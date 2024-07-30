<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected array $dates = ['created_at'];
    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d.m.Y');
    }
}

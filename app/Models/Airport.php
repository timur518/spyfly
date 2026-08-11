<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = [
        'city',
        'name',
        'iata_code',
        'additional_names',
        'is_popular_destination',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_popular_destination' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

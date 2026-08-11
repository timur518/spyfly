<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfitabilitySetting extends Model
{
    protected $fillable = [
        'signal_threshold_percent',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'signal_threshold_percent' => 'decimal:2',
            'rules' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'signal_threshold_percent' => 40,
            'rules' => [],
        ]);
    }
}

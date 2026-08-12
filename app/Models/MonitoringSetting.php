<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringSetting extends Model
{
    protected $fillable = [
        'subscription_scan_interval_minutes',
    ];

    protected function casts(): array
    {
        return [
            'subscription_scan_interval_minutes' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'subscription_scan_interval_minutes' => 60,
        ]);
    }
}

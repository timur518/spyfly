<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'travelpayouts_api_base',
        'travelpayouts_api_token',
        'travelpayouts_partner_id',
        'travelpayouts_tp_trs',
        'travelpayouts_tp_p',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'travelpayouts_api_base' => config('services.travelpayouts.api_base'),
            'travelpayouts_api_token' => config('services.travelpayouts.api_token'),
            'travelpayouts_partner_id' => config('services.travelpayouts.partner_id'),
            'travelpayouts_tp_trs' => config('services.travelpayouts.tp_trs'),
            'travelpayouts_tp_p' => config('services.travelpayouts.tp_p'),
        ]);
    }
}

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
        'yandex_client_id',
        'yandex_client_secret',
        'vkontakte_client_id',
        'vkontakte_client_secret',
        'odnoklassniki_client_id',
        'odnoklassniki_client_secret',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'travelpayouts_api_base' => config('services.travelpayouts.api_base'),
            'travelpayouts_api_token' => config('services.travelpayouts.api_token'),
            'travelpayouts_partner_id' => config('services.travelpayouts.partner_id'),
            'travelpayouts_tp_trs' => config('services.travelpayouts.tp_trs'),
            'travelpayouts_tp_p' => config('services.travelpayouts.tp_p'),
            'yandex_client_id' => config('services.yandex.client_id'),
            'yandex_client_secret' => config('services.yandex.client_secret'),
            'vkontakte_client_id' => config('services.vkontakte.client_id'),
            'vkontakte_client_secret' => config('services.vkontakte.client_secret'),
            'odnoklassniki_client_id' => config('services.odnoklassniki.client_id'),
            'odnoklassniki_client_secret' => config('services.odnoklassniki.client_secret'),
        ]);
    }
}

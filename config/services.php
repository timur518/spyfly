<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'travelpayouts' => [
        'api_base' => env('TRAVELPAYOUTS_API_BASE', 'https://api.travelpayouts.com'),
        'api_token' => env('TRAVELPAYOUTS_API_TOKEN'),
        'partner_id' => env('TRAVELPAYOUTS_PARTNER_ID'),
        'tp_trs' => env('TRAVELPAYOUTS_TP_TRS'),
        'tp_p' => env('TRAVELPAYOUTS_TP_P'),
    ],

    'yandex' => [
        'client_id' => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
        'redirect' => env('YANDEX_REDIRECT_URI', env('APP_URL').'/auth/yandex/callback'),
    ],

    'vkontakte' => [
        'client_id' => env('VKONTAKTE_CLIENT_ID'),
        'client_secret' => env('VKONTAKTE_CLIENT_SECRET'),
        'redirect' => env('VKONTAKTE_REDIRECT_URI', env('APP_URL').'/auth/vk/callback'),
    ],

    'odnoklassniki' => [
        'client_id' => env('ODNOKLASSNIKI_CLIENT_ID'),
        'client_secret' => env('ODNOKLASSNIKI_CLIENT_SECRET'),
        'redirect' => env('ODNOKLASSNIKI_REDIRECT_URI', env('APP_URL').'/auth/ok/callback'),
    ],

];

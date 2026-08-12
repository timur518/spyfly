<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\Description;
use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();
    $view = request('view') === 'cabinet' ? 'cabinet' : 'search';
    $cabinet = null;

    if ($user) {
        $subscriptions = Description::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();
        $integration = IntegrationSetting::current();
        $buyPrefix = blank($integration->travelpayouts_partner_id)
            || blank($integration->travelpayouts_tp_trs)
            || blank($integration->travelpayouts_tp_p)
            ? null
            : 'https://tp.media/r?campaign_id=100&marker=' . rawurlencode((string) $integration->travelpayouts_partner_id)
                . '&p=' . rawurlencode((string) $integration->travelpayouts_tp_p)
                . '&trs=' . rawurlencode((string) $integration->travelpayouts_tp_trs)
                . '&u=';

        $serializeSubscription = static function (Description $subscription): array {
            return [
                'id' => $subscription->id,
                'origin_iata' => $subscription->origin_iata,
                'destination_iata' => $subscription->destination_iata,
                'trip_type' => $subscription->trip_type,
                'max_desired_price' => $subscription->max_desired_price,
                'min_stay_days' => $subscription->min_stay_days,
                'max_stay_days' => $subscription->max_stay_days,
                'channel' => $subscription->channel,
                'is_active' => $subscription->is_active,
                'matched_flights' => $subscription->matched_flights ?? [],
                'created_at' => $subscription->created_at?->toISOString(),
                'updated_at' => $subscription->updated_at?->toISOString(),
                'route_summary' => $subscription->route_summary,
                'price_summary' => $subscription->price_summary,
                'stay_summary' => $subscription->stay_summary,
                'date_range_summary' => $subscription->date_range_summary,
                'channel_summary' => $subscription->channel_summary,
            ];
        };

        $cabinet = [
            'view' => $view,
            'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'provider' => $user->provider,
            ],
            'active_count' => $subscriptions->where('is_active', true)->count(),
            'buy_prefix' => $buyPrefix,
            'subscriptions' => $subscriptions->map($serializeSubscription)->values(),
            'active_subscriptions' => $subscriptions->where('is_active', true)->map($serializeSubscription)->values(),
            'has_subscriptions' => $subscriptions->isNotEmpty(),
        ];
    }

    return view('home', [
        'cabinet' => $cabinet,
    ]);
})->name('home');

Route::get('/sitemap.xml', function () {
    $urls = [
        [
            'loc' => url('/'),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ],
    ];

    $xml = view('sitemap', ['urls' => $urls])->render();

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

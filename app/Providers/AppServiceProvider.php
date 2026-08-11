<?php

namespace App\Providers;

use App\Events\FlightSearchCompleted;
use App\Listeners\RecordSearchHistory;
use App\Listeners\StoreCheapFlightSignals;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Odnoklassniki\Provider as OdnoklassnikiProvider;
use SocialiteProviders\VKontakte\Provider as VkontakteProvider;
use SocialiteProviders\Yandex\Provider as YandexProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(FlightSearchCompleted::class, RecordSearchHistory::class);
        Event::listen(FlightSearchCompleted::class, StoreCheapFlightSignals::class);
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('yandex', YandexProvider::class);
            $event->extendSocialite('vkontakte', VkontakteProvider::class);
            $event->extendSocialite('odnoklassniki', OdnoklassnikiProvider::class);
        });

        FilamentShield::enforcePolicies(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin');
    }
}

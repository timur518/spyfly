<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class SocialAuthController extends Controller
{
    private const PROVIDERS = [
        'yandex' => 'yandex',
        'vk' => 'vkontakte',
        'vkontakte' => 'vkontakte',
        'ok' => 'odnoklassniki',
        'odnoklassniki' => 'odnoklassniki',
    ];

    public function redirect(string $provider): RedirectResponse
    {
        $provider = $this->normalizeProvider($provider);
        $this->configureProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $provider = $this->normalizeProvider($provider);
        $this->configureProvider($provider);

        $socialUser = Socialite::driver($provider)->user();
        $providerId = (string) $socialUser->getId();
        $email = $socialUser->getEmail() ?: sprintf('%s-%s@spyfly.local', $provider, $providerId);
        $name = $socialUser->getName() ?: $socialUser->getNickname() ?: $email;

        $user = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (! $user) {
            $user = $socialUser->getEmail()
                ? User::query()->where('email', $socialUser->getEmail())->first()
                : null;
        }

        if ($user) {
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'provider' => $provider,
                'provider_id' => $providerId,
                'avatar_url' => $socialUser->getAvatar(),
            ])->save();
        } else {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'provider' => $provider,
                'provider_id' => $providerId,
                'avatar_url' => $socialUser->getAvatar(),
                'password' => Hash::make(str()->random(32)),
            ]);
        }

        $userRole = Role::query()->firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $user->syncRoles([$userRole]);

        Auth::login($user, true);

        return redirect()->route('home');
    }

    private function normalizeProvider(string $provider): string
    {
        abort_unless(array_key_exists($provider, self::PROVIDERS), Response::HTTP_NOT_FOUND);

        return self::PROVIDERS[$provider];
    }

    private function configureProvider(string $provider): void
    {
        $settings = IntegrationSetting::current();

        config([
            "services.{$provider}.client_id" => $settings->{"{$provider}_client_id"} ?? config("services.{$provider}.client_id"),
            "services.{$provider}.client_secret" => $settings->{"{$provider}_client_secret"} ?? config("services.{$provider}.client_secret"),
            "services.{$provider}.redirect" => config("services.{$provider}.redirect"),
        ]);
    }
}

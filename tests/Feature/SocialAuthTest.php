<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_in_a_social_user_and_assigns_the_user_role(): void
    {
        Role::query()->create([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        IntegrationSetting::current()->update([
            'yandex_client_id' => 'client-id',
            'yandex_client_secret' => 'client-secret',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn(new class implements SocialiteUser
        {
            public function getId()
            {
                return '12345';
            }

            public function getNickname()
            {
                return 'skytraveler';
            }

            public function getName()
            {
                return 'Sky Traveler';
            }

            public function getEmail()
            {
                return 'sky@example.com';
            }

            public function getAvatar()
            {
                return 'https://example.com/avatar.jpg';
            }
        });

        $response = $this->get('/auth/yandex/callback?code=demo');

        $response->assertRedirect(route('home'));

        $user = User::query()->where('email', 'sky@example.com')->first();

        self::assertNotNull($user);
        self::assertSame('yandex', $user->provider);
        self::assertSame('12345', $user->provider_id);
        self::assertTrue($user->hasRole('user'));
        $this->assertAuthenticatedAs($user);
    }
}

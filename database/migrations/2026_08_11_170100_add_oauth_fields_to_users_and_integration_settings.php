<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('password');
            $table->string('provider_id')->nullable()->after('provider');
            $table->string('avatar_url')->nullable()->after('provider_id');
            $table->index(['provider', 'provider_id']);
        });

        Schema::table('integration_settings', function (Blueprint $table) {
            $table->string('yandex_client_id')->nullable()->after('travelpayouts_tp_p');
            $table->string('yandex_client_secret')->nullable()->after('yandex_client_id');
            $table->string('vkontakte_client_id')->nullable()->after('yandex_client_secret');
            $table->string('vkontakte_client_secret')->nullable()->after('vkontakte_client_id');
            $table->string('odnoklassniki_client_id')->nullable()->after('vkontakte_client_secret');
            $table->string('odnoklassniki_client_secret')->nullable()->after('odnoklassniki_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id', 'avatar_url']);
        });

        Schema::table('integration_settings', function (Blueprint $table) {
            $table->dropColumn([
                'yandex_client_id',
                'yandex_client_secret',
                'vkontakte_client_id',
                'vkontakte_client_secret',
                'odnoklassniki_client_id',
                'odnoklassniki_client_secret',
            ]);
        });
    }
};

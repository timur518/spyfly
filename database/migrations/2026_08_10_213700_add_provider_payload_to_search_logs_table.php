<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('search_logs', 'provider_payload')) {
                $table->json('provider_payload')->nullable()->after('request_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('search_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('search_logs', 'provider_payload')) {
                $table->dropColumn('provider_payload');
            }
        });
    }
};

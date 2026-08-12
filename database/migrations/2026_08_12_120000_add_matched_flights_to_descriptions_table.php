<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('descriptions', 'matched_flights')) {
            Schema::table('descriptions', function (Blueprint $table): void {
                $table->json('matched_flights')->nullable()->after('last_notified_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('descriptions', 'matched_flights')) {
            Schema::table('descriptions', function (Blueprint $table): void {
                $table->dropColumn('matched_flights');
            });
        }
    }
};

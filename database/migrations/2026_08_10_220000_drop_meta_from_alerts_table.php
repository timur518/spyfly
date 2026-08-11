<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table): void {
            if (Schema::hasColumn('alerts', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table): void {
            if (! Schema::hasColumn('alerts', 'meta')) {
                $table->json('meta')->nullable()->after('score');
            }
        });
    }
};

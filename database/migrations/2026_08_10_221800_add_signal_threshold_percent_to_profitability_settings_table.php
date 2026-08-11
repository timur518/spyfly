<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('profitability_settings', 'signal_threshold_percent')) {
            Schema::table('profitability_settings', function (Blueprint $table): void {
                $table->decimal('signal_threshold_percent', 5, 2)->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('profitability_settings', 'signal_threshold_percent')) {
            Schema::table('profitability_settings', function (Blueprint $table): void {
                $table->dropColumn('signal_threshold_percent');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('search_logs', 'max_price')) {
                $table->decimal('max_price', 12, 2)->nullable()->after('date_to');
            }

            if (Schema::hasColumn('search_logs', 'passengers')) {
                $table->dropColumn('passengers');
            }

            if (Schema::hasColumn('search_logs', 'classification')) {
                $table->dropColumn('classification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('search_logs', function (Blueprint $table): void {
            $table->dropColumn('max_price');
            if (! Schema::hasColumn('search_logs', 'passengers')) {
                $table->unsignedSmallInteger('passengers')->default(1)->after('date_to');
            }
            if (! Schema::hasColumn('search_logs', 'classification')) {
                $table->string('classification')->nullable()->after('coverage_percent');
            }
        });
    }
};

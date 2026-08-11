<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('descriptions', 'max_desired_price')) {
            Schema::table('descriptions', function (Blueprint $table): void {
                $table->decimal('max_desired_price', 10, 2)->nullable()->after('max_stay_days');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('descriptions', 'max_desired_price')) {
            Schema::table('descriptions', function (Blueprint $table): void {
                $table->dropColumn('max_desired_price');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('descriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('descriptions', 'date_from')) {
                $table->date('date_from')->nullable()->after('destination_iata');
            }
            if (! Schema::hasColumn('descriptions', 'date_to')) {
                $table->date('date_to')->nullable()->after('date_from');
            }
        });
    }

    public function down(): void
    {
        Schema::table('descriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('descriptions', 'date_to')) {
                $table->dropColumn('date_to');
            }
            if (Schema::hasColumn('descriptions', 'date_from')) {
                $table->dropColumn('date_from');
            }
        });
    }
};

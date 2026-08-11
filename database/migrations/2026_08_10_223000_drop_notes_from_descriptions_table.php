<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('descriptions', 'notes')) {
            Schema::table('descriptions', function (Blueprint $table): void {
                $table->dropColumn('notes');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('descriptions', 'notes')) {
            Schema::table('descriptions', function (Blueprint $table): void {
                $table->text('notes')->nullable()->after('channel');
            });
        }
    }
};

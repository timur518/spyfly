<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table): void {
            if (Schema::hasColumn('alerts', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('alerts', 'sent_at')) {
                $table->dropColumn('sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table): void {
            if (! Schema::hasColumn('alerts', 'status')) {
                $table->string('status')->default('new')->after('score');
            }

            if (! Schema::hasColumn('alerts', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin_iata', 3);
            $table->string('destination_iata', 3);
            $table->string('search_type')->default('round_trip');
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('max_price', 12, 2)->nullable();
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('median_price', 12, 2)->nullable();
            $table->decimal('coverage_percent', 5, 2)->nullable();
            $table->unsignedInteger('results_count')->default(0);
            $table->json('request_payload')->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('response_summary')->nullable();
            $table->string('status')->default('completed');
            $table->text('error_message')->nullable();
            $table->timestamp('searched_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};

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
        Schema::create('descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('origin_iata', 3);
            $table->string('destination_iata', 3)->nullable();
            $table->string('trip_type')->default('round_trip');
            $table->unsignedSmallInteger('min_stay_days')->nullable();
            $table->unsignedSmallInteger('max_stay_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('channel')->default('email');
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descriptions');
    }
};

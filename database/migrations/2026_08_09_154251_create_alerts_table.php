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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_log_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin_iata', 3);
            $table->string('destination_iata', 3);
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('baseline_price', 12, 2)->nullable();
            $table->decimal('deviation_percent', 5, 2)->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};

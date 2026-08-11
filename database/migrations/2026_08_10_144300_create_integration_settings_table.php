<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('travelpayouts_api_base')->nullable();
            $table->string('travelpayouts_api_token')->nullable();
            $table->string('travelpayouts_partner_id')->nullable();
            $table->string('travelpayouts_tp_trs')->nullable();
            $table->string('travelpayouts_tp_p')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
    }
};

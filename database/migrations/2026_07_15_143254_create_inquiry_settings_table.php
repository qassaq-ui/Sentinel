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
        Schema::create('inquiry_settings', function (Blueprint $table) {
            $table->id();
            $table->string('number_prefix', 12)->default('KAZM');
            $table->unsignedTinyInteger('sequence_padding')->default(4);
            $table->boolean('ai_screening_enabled')->default(false);
            $table->text('ai_screening_instructions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_settings');
    }
};

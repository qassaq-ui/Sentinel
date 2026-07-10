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
        Schema::create('inquiry_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name_key')->unique();
            $table->string('description_key')->unique();
            $table->string('fallback_name');
            $table->text('fallback_description')->nullable();
            $table->unsignedSmallInteger('review_days')->default(15);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_categories');
    }
};

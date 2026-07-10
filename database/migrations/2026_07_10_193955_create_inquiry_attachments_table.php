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
        Schema::create('inquiry_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type');
            $table->string('extension', 20)->nullable();
            $table->string('file_type', 40)->index();
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['inquiry_id', 'file_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_attachments');
    }
};

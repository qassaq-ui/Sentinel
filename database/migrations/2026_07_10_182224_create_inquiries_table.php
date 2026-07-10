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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('number')->unique();
            $table->string('number_prefix');
            $table->string('number_period', 4);
            $table->unsignedInteger('number_sequence');
            $table->string('number_format')->default('{prefix}-{month}{year}-{sequence}');
            $table->string('type')->default('portal')->index();
            $table->string('status')->default('new')->index();
            $table->foreignId('inquiry_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('submitted_at')->index();
            $table->unsignedSmallInteger('review_days');
            $table->date('review_due_date')->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->index(['number_prefix', 'number_period', 'number_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};

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
        Schema::create('inquiry_responses', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('inquiry_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_outcome_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('authored_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft')->index();
            $table->text('review_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_responses');
    }
};

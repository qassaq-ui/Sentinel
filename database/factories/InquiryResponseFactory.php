<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryOutcome;
use App\Models\InquiryResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryResponse>
 */
class InquiryResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inquiry_id' => Inquiry::factory(),
            'inquiry_outcome_id' => InquiryOutcome::factory(),
            'authored_by_id' => User::factory(),
            'reviewer_id' => null,
            'body' => fake()->paragraphs(3, true),
            'status' => InquiryResponse::STATUS_DRAFT,
        ];
    }

    public function pendingApproval(?User $reviewer = null): static
    {
        return $this->state(fn (): array => [
            'reviewer_id' => $reviewer?->id ?? User::factory(),
            'status' => InquiryResponse::STATUS_PENDING_APPROVAL,
            'submitted_at' => now(),
        ]);
    }
}

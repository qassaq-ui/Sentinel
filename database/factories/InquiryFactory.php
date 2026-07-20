<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submittedAt = fake()->dateTimeBetween('-7 days');
        $reviewDays = 15;

        return [
            'number' => fake()->unique()->numerify('TEST-####'),
            'number_prefix' => 'TEST',
            'number_period' => $submittedAt->format('my'),
            'number_sequence' => fake()->numberBetween(1, 9999),
            'number_format' => '{prefix}-{month}{year}-{sequence}',
            'type' => Inquiry::TYPE_IDENTIFIED,
            'status' => fake()->randomElement([
                Inquiry::STATUS_NEW,
                Inquiry::STATUS_IN_PROGRESS,
                Inquiry::STATUS_SUSPENDED,
                Inquiry::STATUS_COMPLETED,
                Inquiry::STATUS_REJECTED,
                Inquiry::STATUS_WITHDRAWN,
            ]),
            'inquiry_category_id' => InquiryCategory::factory(),
            'created_by_id' => User::factory(),
            'assigned_to_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'submitted_at' => $submittedAt,
            'review_days' => $reviewDays,
            'review_due_date' => (clone $submittedAt)->modify("+{$reviewDays} days")->format('Y-m-d'),
            'archived_at' => null,
        ];
    }

    public function anonymous(): static
    {
        return $this->state(fn (): array => [
            'type' => Inquiry::TYPE_ANONYMOUS,
            'created_by_id' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryApplicant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryApplicant>
 */
class InquiryApplicantFactory extends Factory
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
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'tracking_token_hash' => hash('sha256', fake()->unique()->uuid()),
        ];
    }
}

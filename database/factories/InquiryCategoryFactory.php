<?php

namespace Database\Factories;

use App\Models\InquiryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryCategory>
 */
class InquiryCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fallback_name' => $this->faker->words(3, true),
            'fallback_description' => $this->faker->sentence(),
            'review_days' => 15,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}

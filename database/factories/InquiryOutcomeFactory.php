<?php

namespace Database\Factories;

use App\Models\InquiryOutcome;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryOutcome>
 */
class InquiryOutcomeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = $this->faker->unique()->slug(2);

        return [
            'code' => $code,
            'fallback_name' => $this->faker->words(2, true),
            'fallback_description' => $this->faker->sentence(),
            'ai_instruction' => $this->faker->paragraph(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}

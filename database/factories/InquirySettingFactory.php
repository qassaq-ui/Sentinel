<?php

namespace Database\Factories;

use App\Models\InquirySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquirySetting>
 */
class InquirySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number_prefix' => 'KAZM',
            'sequence_padding' => 4,
            'ai_screening_enabled' => false,
            'ai_screening_instructions' => InquirySetting::DEFAULT_SCREENING_INSTRUCTIONS,
        ];
    }
}

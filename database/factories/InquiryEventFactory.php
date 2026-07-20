<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryEvent>
 */
class InquiryEventFactory extends Factory
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
            'actor_id' => User::factory(),
            'actor_name' => fake()->name(),
            'actor_role' => 'Compliance Officer',
            'type' => 'inquiry_created',
            'metadata' => null,
            'created_at' => now(),
        ];
    }
}

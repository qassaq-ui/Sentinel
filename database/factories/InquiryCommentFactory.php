<?php

namespace Database\Factories;

use App\Models\InquiryComment;
use App\Models\InquiryResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryComment>
 */
class InquiryCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inquiry_response_id' => InquiryResponse::factory(),
            'inquiry_id' => fn (array $attributes): int => InquiryResponse::query()->findOrFail($attributes['inquiry_response_id'])->inquiry_id,
            'user_id' => User::factory(),
            'parent_id' => null,
            'author_name' => fake()->name(),
            'author_role' => null,
            'body' => fake()->paragraph(),
            'source' => 'manual',
        ];
    }
}

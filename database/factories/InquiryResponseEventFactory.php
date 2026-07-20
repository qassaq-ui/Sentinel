<?php

namespace Database\Factories;

use App\Models\InquiryResponse;
use App\Models\InquiryResponseEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryResponseEvent>
 */
class InquiryResponseEventFactory extends Factory
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
            'user_id' => User::factory(),
            'type' => 'saved',
            'status_from' => InquiryResponse::STATUS_DRAFT,
            'status_to' => InquiryResponse::STATUS_DRAFT,
            'comment' => null,
            'payload' => null,
        ];
    }
}

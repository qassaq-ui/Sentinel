<?php

namespace Database\Factories;

use App\Models\InquiryResponse;
use App\Models\InquiryResponseAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InquiryResponseAttachment>
 */
class InquiryResponseAttachmentFactory extends Factory
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
            'uploaded_by_id' => User::factory(),
            'disk' => 'local',
            'path' => fn (): string => 'inquiry-responses/attachments/'.Str::uuid().'.pdf',
            'original_name' => 'response-document.pdf',
            'stored_name' => fn (array $attributes): string => basename($attributes['path']),
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => fake()->numberBetween(10_000, 5_000_000),
            'checksum' => fake()->sha256(),
        ];
    }
}

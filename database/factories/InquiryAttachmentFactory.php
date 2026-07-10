<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InquiryAttachment>
 */
class InquiryAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['jpg', 'pdf', 'docx', 'xlsx', 'txt', 'mp3']);
        $mimeType = match ($extension) {
            'jpg' => 'image/jpeg',
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'mp3' => 'audio/mpeg',
        };
        $fileType = match ($extension) {
            'jpg' => InquiryAttachment::TYPE_PHOTO,
            'pdf' => InquiryAttachment::TYPE_PDF,
            'docx' => InquiryAttachment::TYPE_DOCUMENT,
            'xlsx' => InquiryAttachment::TYPE_SPREADSHEET,
            'txt' => InquiryAttachment::TYPE_TEXT,
            'mp3' => InquiryAttachment::TYPE_AUDIO,
        };
        $storedName = Str::uuid()->toString().'.'.$extension;

        return [
            'inquiry_id' => Inquiry::factory(),
            'uploaded_by_id' => User::factory(),
            'disk' => 'local',
            'path' => "inquiries/attachments/{$storedName}",
            'original_name' => fake()->words(3, true).'.'.$extension,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'file_type' => $fileType,
            'size_bytes' => fake()->numberBetween(10_000, 5_000_000),
            'checksum' => fake()->sha256(),
            'metadata' => [
                'source' => 'factory',
            ],
        ];
    }

    public function anonymousUpload(): static
    {
        return $this->state(fn (): array => [
            'uploaded_by_id' => null,
        ]);
    }
}

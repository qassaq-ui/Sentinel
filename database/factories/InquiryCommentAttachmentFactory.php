<?php

namespace Database\Factories;

use App\Models\InquiryComment;
use App\Models\InquiryCommentAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InquiryCommentAttachment>
 */
class InquiryCommentAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inquiry_comment_id' => InquiryComment::factory(),
            'disk' => 'local',
            'path' => fn (): string => 'inquiry-comments/attachments/'.Str::uuid().'.pdf',
            'original_name' => 'comment-document.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => fake()->numberBetween(1000, 100000),
            'checksum' => fake()->sha256(),
        ];
    }
}

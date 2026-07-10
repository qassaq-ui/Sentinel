<?php

namespace App\Models;

use Database\Factories\InquiryAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $inquiry_id
 * @property int|null $uploaded_by_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $stored_name
 * @property string $mime_type
 * @property string|null $extension
 * @property string $file_type
 * @property int $size_bytes
 * @property string|null $checksum
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'inquiry_id',
    'uploaded_by_id',
    'disk',
    'path',
    'original_name',
    'stored_name',
    'mime_type',
    'extension',
    'file_type',
    'size_bytes',
    'checksum',
    'metadata',
])]
class InquiryAttachment extends Model
{
    /** @use HasFactory<InquiryAttachmentFactory> */
    use HasFactory;

    public const TYPE_PHOTO = 'photo';

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_SPREADSHEET = 'spreadsheet';

    public const TYPE_TEXT = 'text';

    public const TYPE_PDF = 'pdf';

    public const TYPE_AUDIO = 'audio';

    public const TYPE_OTHER = 'other';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    protected static function booted(): void
    {
        static::creating(function (InquiryAttachment $attachment): void {
            $attachment->uuid ??= (string) Str::uuid();
        });
    }
}

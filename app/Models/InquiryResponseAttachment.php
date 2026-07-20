<?php

namespace App\Models;

use Database\Factories\InquiryResponseAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $inquiry_response_id
 * @property int|null $uploaded_by_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $stored_name
 * @property string $mime_type
 * @property string|null $extension
 * @property int $size_bytes
 * @property string|null $checksum
 * @property Carbon|null $created_at
 * @property-read InquiryResponse $response
 * @property-read User|null $uploadedBy
 */
#[Fillable([
    'inquiry_response_id',
    'uploaded_by_id',
    'disk',
    'path',
    'original_name',
    'stored_name',
    'mime_type',
    'extension',
    'size_bytes',
    'checksum',
])]
class InquiryResponseAttachment extends Model
{
    /** @use HasFactory<InquiryResponseAttachmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (InquiryResponseAttachment $attachment): void {
            $attachment->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<InquiryResponse, $this> */
    public function response(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'inquiry_response_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}

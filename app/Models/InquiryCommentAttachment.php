<?php

namespace App\Models;

use Database\Factories\InquiryCommentAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['inquiry_comment_id', 'disk', 'path', 'original_name', 'mime_type', 'extension', 'size_bytes', 'checksum'])]
class InquiryCommentAttachment extends Model
{
    /** @use HasFactory<InquiryCommentAttachmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (InquiryCommentAttachment $attachment) => $attachment->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(InquiryComment::class, 'inquiry_comment_id');
    }
}

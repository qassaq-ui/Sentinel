<?php

namespace App\Models;

use Database\Factories\InquiryCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['inquiry_id', 'inquiry_response_id', 'user_id', 'parent_id', 'author_name', 'author_role', 'body', 'source'])]
class InquiryComment extends Model
{
    /** @use HasFactory<InquiryCommentFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(fn (InquiryComment $comment) => $comment->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'inquiry_response_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InquiryCommentAttachment::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest('id');
    }
}

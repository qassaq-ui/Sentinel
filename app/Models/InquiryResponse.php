<?php

namespace App\Models;

use Database\Factories\InquiryResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $inquiry_id
 * @property int|null $inquiry_outcome_id
 * @property int|null $authored_by_id
 * @property int|null $reviewer_id
 * @property int|null $reviewed_by_id
 * @property int|null $sent_by_id
 * @property string|null $body
 * @property string $status
 * @property string|null $review_comment
 * @property Carbon|null $submitted_at
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $sent_at
 * @property-read Inquiry $inquiry
 * @property-read InquiryOutcome|null $outcome
 * @property-read User|null $author
 * @property-read User|null $reviewer
 * @property-read User|null $reviewedBy
 * @property-read User|null $sentBy
 * @property-read Collection<int, InquiryResponseAttachment> $attachments
 */
#[Fillable([
    'inquiry_id',
    'inquiry_outcome_id',
    'authored_by_id',
    'reviewer_id',
    'reviewed_by_id',
    'sent_by_id',
    'body',
    'status',
    'review_comment',
    'submitted_at',
    'reviewed_at',
    'sent_at',
])]
class InquiryResponse extends Model
{
    /** @use HasFactory<InquiryResponseFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SENT = 'sent';

    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected static function booted(): void
    {
        static::creating(function (InquiryResponse $response): void {
            $response->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Inquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /** @return BelongsTo<InquiryOutcome, $this> */
    public function outcome(): BelongsTo
    {
        return $this->belongsTo(InquiryOutcome::class, 'inquiry_outcome_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authored_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_id');
    }

    /** @return HasMany<InquiryResponseEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(InquiryResponseEvent::class);
    }

    /** @return HasMany<InquiryResponseAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(InquiryResponseAttachment::class);
    }
}

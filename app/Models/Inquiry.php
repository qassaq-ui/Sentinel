<?php

namespace App\Models;

use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $number
 * @property string $number_prefix
 * @property string $number_period
 * @property int $number_sequence
 * @property string $number_format
 * @property string $type
 * @property string $status
 * @property int $inquiry_category_id
 * @property int|null $created_by_id
 * @property int|null $assigned_to_id
 * @property string $title
 * @property string|null $description
 * @property Carbon $submitted_at
 * @property int $review_days
 * @property Carbon $review_due_date
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $events_count
 * @property Collection<int, InquiryEvent> $events
 * @property-read InquiryCategory $category
 * @property-read User|null $creator
 * @property-read User|null $assignee
 * @property-read Collection<int, InquiryAttachment> $attachments
 * @property-read InquiryApplicant|null $applicant
 * @property-read Collection<int, InquiryReport> $reports
 * @property-read Collection<int, InquiryComment> $comments
 * @property-read InquiryResponse|null $response
 */
#[Fillable([
    'number',
    'number_prefix',
    'number_period',
    'number_sequence',
    'number_format',
    'type',
    'status',
    'inquiry_category_id',
    'created_by_id',
    'assigned_to_id',
    'title',
    'description',
    'submitted_at',
    'review_days',
    'review_due_date',
    'archived_at',
])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    public const TYPE_IDENTIFIED = 'identified';

    public const TYPE_ANONYMOUS = 'anonymous';

    public const STATUS_NEW = 'new';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number_sequence' => 'integer',
            'submitted_at' => 'datetime',
            'review_days' => 'integer',
            'review_due_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InquiryCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InquiryCategory::class, 'inquiry_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /** @return HasMany<InquiryAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(InquiryAttachment::class);
    }

    /** @return HasOne<InquiryApplicant, $this> */
    public function applicant(): HasOne
    {
        return $this->hasOne(InquiryApplicant::class);
    }

    /** @return HasMany<InquiryReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(InquiryReport::class);
    }

    /** @return HasMany<InquiryComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(InquiryComment::class);
    }

    /** @return HasOne<InquiryResponse, $this> */
    public function response(): HasOne
    {
        return $this->hasOne(InquiryResponse::class);
    }

    /** @return HasMany<InquiryEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(InquiryEvent::class);
    }

    /**
     * Limit inquiries to records visible to the given employee.
     *
     * @param  Builder<Inquiry>  $query
     * @return Builder<Inquiry>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->can('inquiries.view')) {
            return $query->whereKey(-1);
        }

        if ($user->can('inquiries.view_all')) {
            return $query;
        }

        $canViewAssigned = $user->can('inquiries.view_assigned');
        $canApprove = $user->can('inquiries.approve');

        if (! $canViewAssigned && ! $canApprove) {
            return $query->whereKey(-1);
        }

        return $query->where(function (Builder $visibilityQuery) use ($user, $canViewAssigned, $canApprove): void {
            if ($canViewAssigned) {
                $visibilityQuery->where('assigned_to_id', $user->id);
            }

            if ($canApprove) {
                $reviewerConstraint = fn (Builder $responseQuery): Builder => $responseQuery
                    ->where('reviewer_id', $user->id)
                    ->where('status', '!=', InquiryResponse::STATUS_SENT);

                if ($canViewAssigned) {
                    $visibilityQuery->orWhereHas('response', $reviewerConstraint);
                } else {
                    $visibilityQuery->whereHas('response', $reviewerConstraint);
                }
            }
        });
    }

    /**
     * @param  Builder<Inquiry>  $query
     * @return Builder<Inquiry>
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->where('status', '!=', self::STATUS_COMPLETED);
    }

    /**
     * @param  Builder<Inquiry>  $query
     * @return Builder<Inquiry>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where(function (Builder $archiveQuery): void {
            $archiveQuery
                ->whereNotNull('archived_at')
                ->orWhere('status', self::STATUS_COMPLETED);
        });
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null || $this->status === self::STATUS_COMPLETED;
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    protected static function booted(): void
    {
        static::creating(function (Inquiry $inquiry): void {
            $inquiry->uuid ??= (string) Str::uuid();
        });
    }
}

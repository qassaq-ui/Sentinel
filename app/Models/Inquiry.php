<?php

namespace App\Models;

use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public const TYPE_PORTAL = 'portal';

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(InquiryCategory::class, 'inquiry_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InquiryAttachment::class);
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

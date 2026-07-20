<?php

namespace App\Models;

use Database\Factories\InquiryEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $inquiry_id
 * @property int|null $actor_id
 * @property int|null $inquiry_response_id
 * @property string|null $actor_name
 * @property string|null $actor_role
 * @property string $type
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 */
#[Fillable([
    'inquiry_id',
    'actor_id',
    'inquiry_response_id',
    'actor_name',
    'actor_role',
    'type',
    'metadata',
    'created_at',
])]
class InquiryEvent extends Model
{
    /** @use HasFactory<InquiryEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Inquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<InquiryResponse, $this> */
    public function response(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'inquiry_response_id');
    }
}

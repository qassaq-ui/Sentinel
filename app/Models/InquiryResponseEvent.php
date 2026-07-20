<?php

namespace App\Models;

use Database\Factories\InquiryResponseEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inquiry_response_id
 * @property int|null $user_id
 * @property string $type
 * @property string|null $status_from
 * @property string $status_to
 * @property string|null $comment
 * @property array<string, mixed>|null $payload
 */
#[Fillable([
    'inquiry_response_id',
    'user_id',
    'type',
    'status_from',
    'status_to',
    'comment',
    'payload',
])]
class InquiryResponseEvent extends Model
{
    /** @use HasFactory<InquiryResponseEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'inquiry_response_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

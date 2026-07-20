<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryEvent;
use App\Models\InquiryResponse;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RecordInquiryEvent
{
    /** @param array<string, mixed> $metadata */
    public function handle(
        Inquiry $inquiry,
        string $type,
        ?User $actor = null,
        array $metadata = [],
        ?InquiryResponse $response = null,
        ?CarbonInterface $occurredAt = null,
    ): InquiryEvent {
        $roleName = $actor?->getRoleNames()->first();
        $role = $roleName === null ? null : Role::query()->where('name', $roleName)->first();
        $fallbackRoleLabel = $role?->getAttribute('fallback_label');

        $event = new InquiryEvent([
            'inquiry_id' => $inquiry->id,
            'actor_id' => $actor?->id,
            'inquiry_response_id' => $response?->id,
            'actor_name' => $actor?->name,
            'actor_role' => $role === null
                ? null
                : (is_string($fallbackRoleLabel) && $fallbackRoleLabel !== ''
                    ? $fallbackRoleLabel
                    : Str::headline($role->name)),
            'type' => $type,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => $occurredAt ?? now(),
        ]);
        $event->save();

        return $event;
    }
}

<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UpdateInquiryAssignee
{
    public function __construct(private RecordInquiryEvent $recordEvent) {}

    public function handle(Inquiry $inquiry, User $actor, ?int $assigneeId): Inquiry
    {
        return DB::transaction(function () use ($inquiry, $actor, $assigneeId): Inquiry {
            $locked = Inquiry::query()->lockForUpdate()->findOrFail($inquiry->id);

            abort_if($locked->isArchived(), 403);

            if ($locked->assigned_to_id === $assigneeId) {
                return $locked;
            }

            $previousAssignee = $locked->assigned_to_id === null
                ? null
                : User::query()->with('roles:id,name,fallback_label')->find($locked->assigned_to_id);
            $assignee = $assigneeId === null
                ? null
                : User::query()->with('roles:id,name,fallback_label')->findOrFail($assigneeId);
            $previousStatus = $locked->status;
            $nextStatus = $assignee !== null && $previousStatus === Inquiry::STATUS_NEW
                ? Inquiry::STATUS_IN_PROGRESS
                : $previousStatus;

            $locked->forceFill([
                'assigned_to_id' => $assignee?->id,
                'status' => $nextStatus,
            ])->save();

            $type = match (true) {
                $previousAssignee === null => 'assignee_assigned',
                $assignee === null => 'assignee_unassigned',
                default => 'assignee_reassigned',
            };

            $metadata = [
                'from' => $this->userSnapshot($previousAssignee),
                'to' => $this->userSnapshot($assignee),
            ];

            if ($previousStatus !== $nextStatus) {
                $metadata['inquiry_status_from'] = $previousStatus;
                $metadata['inquiry_status_to'] = $nextStatus;
            }

            $this->recordEvent->handle($locked, $type, $actor, $metadata);

            return $locked->refresh();
        }, attempts: 3);
    }

    /** @return array{id: int, name: string, role: string|null}|null */
    private function userSnapshot(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $roleName = $user->getRoleNames()->first();
        $role = $roleName === null ? null : Role::query()->where('name', $roleName)->first();
        $fallbackRoleLabel = $role?->getAttribute('fallback_label');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $role === null
                ? null
                : (is_string($fallbackRoleLabel) && $fallbackRoleLabel !== ''
                    ? $fallbackRoleLabel
                    : Str::headline($role->name)),
        ];
    }
}

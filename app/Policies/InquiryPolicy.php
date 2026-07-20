<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\User;

class InquiryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inquiries.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inquiry $inquiry): bool
    {
        return Inquiry::query()
            ->visibleTo($user)
            ->whereKey($inquiry->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.update') && $this->view($user, $inquiry);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.delete') && $this->view($user, $inquiry);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Inquiry $inquiry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Inquiry $inquiry): bool
    {
        return false;
    }

    public function assign(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.assign')
            && ! $inquiry->isArchived()
            && $this->view($user, $inquiry);
    }

    public function respond(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.respond')
            && $this->view($user, $inquiry)
            && $inquiry->assigned_to_id === $user->id;
    }

    public function comment(User $user, Inquiry $inquiry): bool
    {
        if (! $this->view($user, $inquiry)) {
            return false;
        }

        $response = $inquiry->response;

        return $response !== null
            && $response->status !== InquiryResponse::STATUS_SENT
            && (
                ($inquiry->assigned_to_id === $user->id && $user->can('inquiries.respond'))
                || ($response->reviewer_id === $user->id && $user->can('inquiries.approve'))
            );
    }
}

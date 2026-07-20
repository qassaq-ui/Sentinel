<?php

namespace App\Policies;

use App\Models\InquiryComment;
use App\Models\InquiryResponse;
use App\Models\User;

class InquiryCommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InquiryComment $inquiryComment): bool
    {
        return false;
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
    public function update(User $user, InquiryComment $inquiryComment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InquiryComment $inquiryComment): bool
    {
        return ! $inquiryComment->trashed()
            && $inquiryComment->source === 'manual'
            && $inquiryComment->user_id === $user->id
            && $user->can('view', $inquiryComment->inquiry)
            && $inquiryComment->inquiry->response?->status !== InquiryResponse::STATUS_SENT;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InquiryComment $inquiryComment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InquiryComment $inquiryComment): bool
    {
        return false;
    }
}

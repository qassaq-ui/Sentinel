<?php

namespace App\Policies;

use App\Models\InquiryResponse;
use App\Models\User;

class InquiryResponsePolicy
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
    public function view(User $user, InquiryResponse $inquiryResponse): bool
    {
        return $user->can('view', $inquiryResponse->inquiry);
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
    public function update(User $user, InquiryResponse $response): bool
    {
        return $user->can('inquiries.respond')
            && $this->view($user, $response)
            && $response->inquiry->assigned_to_id === $user->id
            && in_array($response->status, [
                InquiryResponse::STATUS_DRAFT,
                InquiryResponse::STATUS_CHANGES_REQUESTED,
            ], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InquiryResponse $inquiryResponse): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InquiryResponse $inquiryResponse): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InquiryResponse $inquiryResponse): bool
    {
        return false;
    }

    public function review(User $user, InquiryResponse $response): bool
    {
        return $user->can('inquiries.approve')
            && $this->view($user, $response)
            && $response->reviewer_id === $user->id
            && $response->authored_by_id !== $user->id
            && $response->status === InquiryResponse::STATUS_PENDING_APPROVAL;
    }

    public function send(User $user, InquiryResponse $response): bool
    {
        return $user->can('inquiries.send')
            && $this->view($user, $response)
            && $response->status === InquiryResponse::STATUS_APPROVED;
    }
}

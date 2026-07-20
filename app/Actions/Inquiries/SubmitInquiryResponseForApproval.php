<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitInquiryResponseForApproval
{
    public function __construct(private RecordInquiryEvent $recordEvent) {}

    public function handle(InquiryResponse $response, User $author, User $reviewer): InquiryResponse
    {
        return DB::transaction(function () use ($response, $author, $reviewer): InquiryResponse {
            $inquiry = Inquiry::query()->lockForUpdate()->findOrFail($response->inquiry_id);
            $locked = InquiryResponse::query()->lockForUpdate()->findOrFail($response->id);

            abort_unless(
                $author->can('view', $inquiry)
                    && $author->can('inquiries.respond')
                    && $inquiry->assigned_to_id === $author->id
                    && $reviewer->status === 'active'
                    && $reviewer->can('inquiries.approve')
                    && $reviewer->can('inquiries.view')
                    && ! $reviewer->is($author),
                403,
            );

            if (! in_array($locked->status, [
                InquiryResponse::STATUS_DRAFT,
                InquiryResponse::STATUS_CHANGES_REQUESTED,
            ], true)) {
                throw ValidationException::withMessages([
                    'response' => __('Only a draft can be submitted for approval.'),
                ]);
            }

            if ($locked->inquiry_outcome_id === null || blank($locked->body)) {
                throw ValidationException::withMessages([
                    'response' => __('Select an outcome and prepare the response before approval.'),
                ]);
            }

            $previousStatus = $locked->status;
            $locked->forceFill([
                'authored_by_id' => $author->id,
                'reviewer_id' => $reviewer->id,
                'status' => InquiryResponse::STATUS_PENDING_APPROVAL,
                'review_comment' => null,
                'submitted_at' => now(),
                'reviewed_by_id' => null,
                'reviewed_at' => null,
            ])->save();

            $locked->events()->create([
                'user_id' => $author->id,
                'type' => 'submitted',
                'status_from' => $previousStatus,
                'status_to' => InquiryResponse::STATUS_PENDING_APPROVAL,
                'payload' => ['reviewer_id' => $reviewer->id],
            ]);

            $this->recordEvent->handle($inquiry, 'response_submitted', $author, [
                'reviewer' => [
                    'id' => $reviewer->id,
                    'name' => $reviewer->name,
                ],
                'status_from' => $previousStatus,
                'status_to' => InquiryResponse::STATUS_PENDING_APPROVAL,
            ], $locked);

            return $locked->refresh();
        }, attempts: 3);
    }
}

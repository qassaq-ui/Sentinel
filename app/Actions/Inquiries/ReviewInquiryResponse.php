<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewInquiryResponse
{
    public function __construct(
        private RecordInquiryEvent $recordEvent,
        private StoreInquiryComment $storeComment,
    ) {}

    public function handle(InquiryResponse $response, User $reviewer, string $decision, ?string $comment): InquiryResponse
    {
        return DB::transaction(function () use ($response, $reviewer, $decision, $comment): InquiryResponse {
            $inquiry = Inquiry::query()->lockForUpdate()->findOrFail($response->inquiry_id);
            $locked = InquiryResponse::query()->lockForUpdate()->findOrFail($response->id);

            abort_unless(
                $reviewer->can('view', $inquiry)
                    && $reviewer->can('inquiries.approve')
                    && $locked->reviewer_id === $reviewer->id
                    && $locked->authored_by_id !== $reviewer->id,
                403,
            );

            if ($locked->status !== InquiryResponse::STATUS_PENDING_APPROVAL) {
                throw ValidationException::withMessages([
                    'response' => __('This response is not awaiting approval.'),
                ]);
            }

            $status = $decision === 'approve'
                ? InquiryResponse::STATUS_APPROVED
                : InquiryResponse::STATUS_CHANGES_REQUESTED;

            $locked->forceFill([
                'status' => $status,
                'review_comment' => $comment,
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $locked->events()->create([
                'user_id' => $reviewer->id,
                'type' => $decision === 'approve' ? 'approved' : 'returned',
                'status_from' => InquiryResponse::STATUS_PENDING_APPROVAL,
                'status_to' => $status,
                'comment' => $comment,
            ]);

            $this->recordEvent->handle(
                $inquiry,
                $decision === 'approve' ? 'response_approved' : 'response_returned',
                $reviewer,
                [
                    'status_from' => InquiryResponse::STATUS_PENDING_APPROVAL,
                    'status_to' => $status,
                    'comment' => $comment,
                ],
                $locked,
            );

            if (filled($comment)) {
                $this->storeComment->handle($inquiry, $reviewer, (string) $comment, source: 'review');
            }

            return $locked->refresh();
        }, attempts: 3);
    }
}

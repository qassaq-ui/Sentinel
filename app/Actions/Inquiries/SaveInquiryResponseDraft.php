<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryOutcome;
use App\Models\InquiryResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveInquiryResponseDraft
{
    public function __construct(private RecordInquiryEvent $recordEvent) {}

    public function handle(Inquiry $inquiry, User $author, ?int $outcomeId, ?string $body): InquiryResponse
    {
        return DB::transaction(function () use ($inquiry, $author, $outcomeId, $body): InquiryResponse {
            $lockedInquiry = Inquiry::query()->lockForUpdate()->findOrFail($inquiry->id);
            abort_unless(
                $author->can('view', $lockedInquiry)
                    && $author->can('inquiries.respond')
                    && $lockedInquiry->assigned_to_id === $author->id,
                403,
            );
            $response = InquiryResponse::query()
                ->whereBelongsTo($lockedInquiry)
                ->lockForUpdate()
                ->first();

            if ($response !== null && ! in_array($response->status, [
                InquiryResponse::STATUS_DRAFT,
                InquiryResponse::STATUS_CHANGES_REQUESTED,
            ], true)) {
                throw ValidationException::withMessages([
                    'response' => __('This response can no longer be edited.'),
                ]);
            }

            $previousStatus = $response?->status;
            $response ??= new InquiryResponse(['inquiry_id' => $lockedInquiry->id]);
            $response->fill([
                'inquiry_outcome_id' => $outcomeId,
                'authored_by_id' => $author->id,
                'body' => $body,
                'status' => InquiryResponse::STATUS_DRAFT,
                'reviewed_by_id' => null,
                'reviewed_at' => null,
            ])->save();

            $response->events()->create([
                'user_id' => $author->id,
                'type' => $previousStatus === null ? 'created' : 'saved',
                'status_from' => $previousStatus,
                'status_to' => InquiryResponse::STATUS_DRAFT,
                'payload' => ['outcome_id' => $outcomeId],
            ]);

            $outcome = $outcomeId === null ? null : InquiryOutcome::query()->find($outcomeId);
            $this->recordEvent->handle(
                $lockedInquiry,
                $previousStatus === null ? 'response_created' : 'response_saved',
                $author,
                [
                    'outcome_id' => $outcome?->id,
                    'outcome_name' => $outcome?->fallback_name,
                    'status_from' => $previousStatus,
                    'status_to' => InquiryResponse::STATUS_DRAFT,
                ],
                $response,
            );

            return $response->refresh();
        }, attempts: 3);
    }
}

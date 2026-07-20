<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SendInquiryResponse
{
    public function __construct(private RecordInquiryEvent $recordEvent) {}

    public function handle(InquiryResponse $response, User $sender): InquiryResponse
    {
        return DB::transaction(function () use ($response, $sender): InquiryResponse {
            $inquiry = Inquiry::query()->lockForUpdate()->findOrFail($response->inquiry_id);
            $locked = InquiryResponse::query()->lockForUpdate()->findOrFail($response->id);

            abort_unless(
                $sender->can('view', $inquiry) && $sender->can('inquiries.send'),
                403,
            );

            if ($locked->status !== InquiryResponse::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'response' => __('Only an approved response can be sent.'),
                ]);
            }

            $locked->forceFill([
                'status' => InquiryResponse::STATUS_SENT,
                'sent_by_id' => $sender->id,
                'sent_at' => now(),
            ])->save();

            $previousInquiryStatus = $inquiry->status;
            $inquiry->forceFill([
                'status' => Inquiry::STATUS_COMPLETED,
                'archived_at' => now(),
            ])->save();

            $locked->events()->create([
                'user_id' => $sender->id,
                'type' => 'sent',
                'status_from' => InquiryResponse::STATUS_APPROVED,
                'status_to' => InquiryResponse::STATUS_SENT,
            ]);

            $this->recordEvent->handle($inquiry, 'response_sent', $sender, [
                'status_from' => InquiryResponse::STATUS_APPROVED,
                'status_to' => InquiryResponse::STATUS_SENT,
                'inquiry_status_from' => $previousInquiryStatus,
                'inquiry_status_to' => Inquiry::STATUS_COMPLETED,
            ], $locked);

            return $locked->refresh();
        }, attempts: 3);
    }
}

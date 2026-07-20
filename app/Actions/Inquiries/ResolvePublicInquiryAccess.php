<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Support\InquiryAccessCode;
use Illuminate\Validation\ValidationException;

class ResolvePublicInquiryAccess
{
    public function handle(string $accessCode): Inquiry
    {
        $inquiry = Inquiry::query()
            ->select(['id', 'number', 'status', 'submitted_at', 'updated_at'])
            ->whereRelation(
                'applicant',
                'tracking_token_hash',
                InquiryAccessCode::hash($accessCode),
            )
            ->first();

        if ($inquiry === null) {
            throw ValidationException::withMessages([
                'access_code' => __('The access code is incorrect.'),
            ]);
        }

        return $inquiry;
    }
}

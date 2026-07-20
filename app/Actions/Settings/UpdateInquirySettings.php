<?php

namespace App\Actions\Settings;

use App\Models\InquirySetting;
use Illuminate\Support\Facades\DB;

class UpdateInquirySettings
{
    /**
     * @param  array{
     *     number_prefix: string,
     *     sequence_padding: int,
     *     ai_screening_enabled: bool,
     *     ai_screening_instructions: string|null
     * }  $data
     */
    public function handle(array $data): InquirySetting
    {
        return DB::transaction(function () use ($data): InquirySetting {
            $settings = InquirySetting::query()
                ->whereKey(1)
                ->lockForUpdate()
                ->first();

            if ($settings === null) {
                $settings = new InquirySetting;
                $settings->id = 1;
            }

            $settings->fill($data)->save();

            return $settings;
        }, attempts: 3);
    }
}

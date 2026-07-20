<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateInquirySettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\InquirySettingUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InquirySettingsController extends Controller
{
    public function update(
        InquirySettingUpdateRequest $request,
        UpdateInquirySettings $updateInquirySettings,
    ): RedirectResponse {
        $updateInquirySettings->handle($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Inquiry settings updated.'),
        ]);

        return back();
    }
}

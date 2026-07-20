<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\InquirySetting;
use App\Support\Localization\LocalizationManager;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    public function edit(LocalizationManager $localization): Response
    {
        $inquirySettings = InquirySetting::current();

        return Inertia::render('settings/Index', [
            'localizationSettings' => [
                'fallback' => config('app.fallback_locale'),
                'locales' => $localization->configuredLocales(),
            ],
            'inquirySettings' => [
                'numberPrefix' => $inquirySettings->number_prefix,
                'sequencePadding' => $inquirySettings->sequence_padding,
                'aiScreeningEnabled' => $inquirySettings->ai_screening_enabled,
                'aiScreeningInstructions' => $inquirySettings->ai_screening_instructions
                    ?: InquirySetting::DEFAULT_SCREENING_INSTRUCTIONS,
            ],
        ]);
    }
}

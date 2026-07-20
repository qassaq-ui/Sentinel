<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LocalizationStatusRequest;
use App\Http\Requests\Settings\LocalizationUploadRequest;
use App\Support\Localization\LocalizationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LocalizationController extends Controller
{
    public function store(LocalizationUploadRequest $request, LocalizationManager $localization): RedirectResponse
    {
        $validated = $request->validated();
        $path = $request->file('messages')->getRealPath();
        $messages = json_decode(file_get_contents($path) ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $localization->storeUploadedLocale(
            $validated['locale'],
            $validated['label'],
            $messages,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Localization uploaded.')]);

        return back();
    }

    public function update(
        LocalizationStatusRequest $request,
        string $locale,
        LocalizationManager $localization,
    ): RedirectResponse {
        $enabled = $request->boolean('enabled');

        $configuredLocale = collect($localization->configuredLocales())
            ->firstWhere('code', $locale);

        abort_if(! $configuredLocale, 404);

        if (! $enabled && $locale === config('app.fallback_locale')) {
            throw ValidationException::withMessages([
                'enabled' => __('The fallback language cannot be disabled.'),
            ]);
        }

        if ($enabled) {
            $localization->enableLocale($locale);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Language enabled.')]);
        } else {
            $localization->disableLocale($locale);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Language disabled.')]);
        }

        return back();
    }
}

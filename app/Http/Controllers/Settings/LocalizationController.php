<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LocalizationUploadRequest;
use App\Support\Localization\LocalizationManager;
use Illuminate\Http\RedirectResponse;
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
}

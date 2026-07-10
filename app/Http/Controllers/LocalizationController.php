<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocaleUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class LocalizationController extends Controller
{
    public function update(LocaleUpdateRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');

        session(['locale' => $locale]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language updated.')]);

        return back()->withCookie(cookie()->forever('locale', $locale));
    }
}

<?php

namespace App\Http\Requests;

use App\Support\Localization\LocalizationManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocaleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = collect(app(LocalizationManager::class)->availableLocales())
            ->pluck('code')
            ->all();

        return [
            'locale' => ['required', 'string', Rule::in($locales)],
        ];
    }
}

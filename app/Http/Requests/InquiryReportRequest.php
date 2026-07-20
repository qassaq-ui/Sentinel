<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use App\Services\AIAssistant\InquiryTranslationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InquiryReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $inquiry = $this->route('inquiry');

        return $inquiry instanceof Inquiry
            && $this->user()?->can('view', $inquiry) === true;
    }

    /**
     * Get the validation rules that apply to this request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'language' => [
                'nullable',
                'string',
                'max:12',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }
                    if (! is_string($value) || ! app(InquiryTranslationService::class)->isSupportedLanguage($value)) {
                        $fail(__('The selected language is not supported.'));
                    }
                },
            ],
        ];
    }
}

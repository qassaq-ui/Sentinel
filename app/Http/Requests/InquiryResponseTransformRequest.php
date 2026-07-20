<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use App\Services\AIAssistant\InquiryTranslationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InquiryResponseTransformRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $inquiry = $this->route('inquiry');

        return $inquiry instanceof Inquiry
            && $this->user()?->can('respond', $inquiry) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['translate', 'polish'])],
            'body' => ['required', 'string', 'max:20000'],
            'locale' => [
                'nullable',
                'required_if:action,translate',
                'string',
                'max:12',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null
                        && (! is_string($value)
                            || ! app(InquiryTranslationService::class)->isSupportedLanguage($value))) {
                        $fail(__('The selected language is not supported.'));
                    }
                },
            ],
        ];
    }
}

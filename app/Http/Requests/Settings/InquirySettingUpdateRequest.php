<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InquirySettingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('settings.access') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number_prefix' => ['required', 'string', 'max:12', 'regex:/^[A-Z0-9]+$/'],
            'sequence_padding' => ['required', 'integer', 'min:3', 'max:8'],
            'ai_screening_enabled' => ['required', 'boolean'],
            'ai_screening_instructions' => [
                Rule::requiredIf($this->boolean('ai_screening_enabled')),
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'number_prefix' => mb_strtoupper(trim((string) $this->input('number_prefix'))),
        ]);
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AIAssistantChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job' => [
                'required',
                'string',
                Rule::in([
                    'translate_text',
                    'analyze_inquiry',
                    'recommend_assignee',
                    'recommend_response',
                ]),
            ],
            'message' => ['nullable', 'string', 'max:4000'],
            'history' => ['nullable', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'string', Rule::in(['user', 'assistant'])],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'locale' => ['required', 'string', 'max:12'],
            'inquiry_number' => ['nullable', 'string', 'max:50', 'exists:inquiries,number'],
        ];
    }
}

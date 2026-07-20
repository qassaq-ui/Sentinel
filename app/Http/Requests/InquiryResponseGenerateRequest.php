<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InquiryResponseGenerateRequest extends FormRequest
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
            'inquiry_outcome_id' => [
                'required',
                'integer',
                Rule::exists('inquiry_outcomes', 'id')->where('is_active', true),
            ],
            'current_body' => ['nullable', 'string', 'max:20000'],
            'locale' => ['required', 'string', 'max:12'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Support\InquiryAccessCode;
use Illuminate\Foundation\Http\FormRequest;

class PublicInquiryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'access_code' => ['required', 'string', 'regex:/^[A-HJ-NP-Z2-9]{12}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'access_code.required' => __('Enter the access code.'),
            'access_code.regex' => __('Enter a valid access code.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $accessCode = $this->input('access_code');

        if (is_string($accessCode)) {
            $this->merge(['access_code' => InquiryAccessCode::normalize($accessCode)]);
        }
    }
}

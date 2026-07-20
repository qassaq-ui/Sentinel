<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class PublicInquiryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'submission_mode' => ['required', Rule::in(['anonymous', 'identified'])],
            'inquiry_category_id' => [
                'required',
                'integer',
                Rule::exists('inquiry_categories', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                File::types([
                    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt',
                    'jpg', 'jpeg', 'png', 'heic', 'heif', 'webp', 'gif', 'avif',
                    'mp3', 'm4a', 'wav', 'ogg', 'webm',
                ])->max('20mb'),
            ],
            'applicant_name' => [
                'exclude_if:submission_mode,anonymous',
                'required',
                'string',
                'max:255',
            ],
            'applicant_email' => [
                'exclude_if:submission_mode,anonymous',
                'nullable',
                'required_without:applicant_phone',
                'email',
                'max:255',
            ],
            'applicant_phone' => [
                'exclude_if:submission_mode,anonymous',
                'nullable',
                'required_without:applicant_email',
                'string',
                'regex:/^[0-9+()\\-\\s]{7,30}$/',
            ],
        ];
    }
}

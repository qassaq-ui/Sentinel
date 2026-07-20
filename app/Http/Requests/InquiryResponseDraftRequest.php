<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class InquiryResponseDraftRequest extends FormRequest
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
                'nullable',
                'integer',
                Rule::exists('inquiry_outcomes', 'id')->where('is_active', true),
            ],
            'body' => ['nullable', 'string', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'required',
                File::types([
                    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                    'txt', 'rtf', 'odt', 'ods', 'jpg', 'jpeg', 'png',
                ])->max('10mb'),
                'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,odt,ods,jpg,jpeg,png',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $inquiry = $this->route('inquiry');
            $files = $this->file('attachments', []);

            if (! $inquiry instanceof Inquiry || ! is_array($files)) {
                return;
            }

            $storedAttachmentsCount = $inquiry->response?->attachments()->count() ?? 0;

            if ($storedAttachmentsCount + count($files) > 10) {
                $validator->errors()->add('attachments', __('You can attach up to 10 files.'));
            }
        }];
    }
}

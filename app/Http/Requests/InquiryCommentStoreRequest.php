<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class InquiryCommentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $inquiry = $this->route('inquiry');

        return $inquiry instanceof Inquiry && $this->user()?->can('comment', $inquiry) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4000'],
            'parent_id' => [
                'nullable',
                'string',
                Rule::exists('inquiry_comments', 'uuid')->where('inquiry_id', $this->route('inquiry')?->id),
            ],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'required',
                File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png'])->max('10mb'),
                'extensions:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png',
            ],
        ];
    }
}

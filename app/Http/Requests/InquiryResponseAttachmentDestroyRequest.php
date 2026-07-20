<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use App\Models\InquiryResponseAttachment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InquiryResponseAttachmentDestroyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $inquiry = $this->route('inquiry');
        $attachment = $this->route('attachment');

        if (! $inquiry instanceof Inquiry || ! $attachment instanceof InquiryResponseAttachment) {
            return false;
        }

        $response = $attachment->response;

        return $response->inquiry_id === $inquiry->id
            && $this->user()?->can('update', $response) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}

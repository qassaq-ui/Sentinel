<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InquiryResponseSubmitRequest extends InquiryResponseDraftRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'reviewer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('status', 'active'),
            ],
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'reviewer_id.required' => __('Select an approver'),
        ]);
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [...parent::after(), function (Validator $validator): void {
            $reviewer = User::query()->find($this->integer('reviewer_id'));

            if ($reviewer !== null && (
                ! $reviewer->can('inquiries.approve')
                || ! $reviewer->can('inquiries.view')
            )) {
                $validator->errors()->add('reviewer_id', __('Select a user who can approve responses.'));
            }

            if ($reviewer?->is($this->user())) {
                $validator->errors()->add('reviewer_id', __('The response author cannot approve their own response.'));
            }
        }];
    }
}

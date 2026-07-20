<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InquiryAssigneeUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $inquiry = $this->route('inquiry');

        return $inquiry instanceof Inquiry
            && $this->user()?->can('assign', $inquiry) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_to_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('status', 'active')
                ),
            ],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('assigned_to_id') === null) {
                return;
            }

            $assignee = User::query()->find($this->integer('assigned_to_id'));

            if ($assignee !== null && (
                ! $assignee->can('inquiries.respond')
                || ! $assignee->can('inquiries.view')
                || (
                    ! $assignee->can('inquiries.view_assigned')
                    && ! $assignee->can('inquiries.view_all')
                )
            )) {
                $validator->errors()->add(
                    'assigned_to_id',
                    __('Select an employee who can prepare inquiry responses.'),
                );
            }
        }];
    }
}

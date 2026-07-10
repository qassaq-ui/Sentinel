<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
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
            'fallback_label' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'fallback_label')
                    ->where('guard_name', 'web')
                    ->ignore($this->route('role')),
            ],
            'ai_description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

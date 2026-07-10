<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RolePermissionUpdateRequest extends FormRequest
{
    public const SYSTEM_PERMISSIONS = [
        'settings.access',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'roles.permissions.update',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
    ];

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
            'permission' => ['required', 'string', Rule::in(self::SYSTEM_PERMISSIONS)],
            'enabled' => ['required', 'boolean'],
        ];
    }
}

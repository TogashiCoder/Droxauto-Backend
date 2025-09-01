<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(function ($query) {
                    return $query->where('guard_name', $this->guard_name ?? 'api');
                }),
                function ($attribute, $value, $fail) {
                    // Prevent creating roles with system role names
                    if (\App\Services\RoleConfigService::isSystemRole($value)) {
                        $fail("Cannot create a role with the name '{$value}' as it conflicts with a system role name.");
                    }
                }
            ],
            'guard_name' => 'string|max:255|in:api,web',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'name.max' => 'Role name cannot exceed 255 characters.',
            'guard_name.max' => 'Guard name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'role name',
            'guard_name' => 'guard name',
            'description' => 'description',
            'permissions' => 'permissions'
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        $roleId = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(function ($query) {
                    return $query->where('guard_name', $this->guard_name ?? 'api');
                })->ignore($roleId),
                function ($attribute, $value, $fail) {
                    // Get current role being updated
                    $roleId = $this->route('role');
                    $currentRole = \Spatie\Permission\Models\Role::find($roleId);
                    
                    if ($currentRole && $value !== $currentRole->name) {
                        // Check role protection
                        $validationError = \App\Services\RoleConfigService::validateRoleOperation('rename', $currentRole->name, $value);
                        if ($validationError) {
                            $fail($validationError);
                        }
                    }
                }
            ],
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
            'description' => 'description',
            'permissions' => 'permissions'
        ];
    }
}

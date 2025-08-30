<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePermissionRequest extends FormRequest
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
                Rule::unique('permissions', 'name')->where(function ($query) {
                    return $query->where('guard_name', $this->guard_name ?? 'api');
                })
            ],
            'guard_name' => 'string|max:255|in:api,web',
            'description' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Permission name is required.',
            'name.unique' => 'A permission with this name already exists.',
            'name.max' => 'Permission name cannot exceed 255 characters.',
            'guard_name.in' => 'Guard name must be either "api" or "web".',
            'guard_name.max' => 'Guard name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'permission name',
            'guard_name' => 'guard name',
            'description' => 'description'
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('approve user registrations') || 
               $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'role.exists' => 'The selected role does not exist',
            'admin_notes.max' => 'Admin notes cannot exceed 1000 characters',
        ];
    }
}

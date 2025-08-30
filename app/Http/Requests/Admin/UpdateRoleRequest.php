<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateRoleRequest",
 *     title="Update Role Request",
 *     description="Request body for updating an existing role",
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Unique name for the role",
 *         example="senior_manager",
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Description of the role",
 *         example="Senior manager role with extended administrative access",
 *         nullable=true,
 *         maxLength=500
 *     ),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         description="Array of permission IDs to assign to the role",
 *         @OA\Items(type="integer"),
 *         example={1, 2, 3, 4},
 *         nullable=true
 *     )
 * )
 */
class UpdateRoleRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($this->route('role'))
            ],
            'description' => 'sometimes|nullable|string|max:500',
            'permissions' => 'sometimes|nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CreateRoleRequest",
 *     title="Create Role Request",
 *     description="Request body for creating a new role",
 *     required={"name", "guard_name"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Unique name for the role",
 *         example="manager",
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="guard_name",
 *         type="string",
 *         description="Guard name for the role",
 *         example="api",
 *         enum={"api", "web"}
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Description of the role",
 *         example="Manager role with limited administrative access",
 *         nullable=true,
 *         maxLength=500
 *     ),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         description="Array of permission IDs to assign to the role",
 *         @OA\Items(type="integer"),
 *         example={1, 2, 3},
 *         nullable=true
 *     )
 * )
 */
class CreateRoleRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'required|string|in:api,web',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ];
    }
}

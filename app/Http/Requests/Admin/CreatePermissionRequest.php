<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CreatePermissionRequest",
 *     title="Create Permission Request",
 *     description="Request body for creating a new permission",
 *     required={"name", "guard_name"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Unique name for the permission",
 *         example="manage_inventory",
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="guard_name",
 *         type="string",
 *         description="Guard name for the permission",
 *         example="api",
 *         enum={"api", "web"}
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Description of the permission",
 *         example="Allows users to manage inventory items",
 *         nullable=true,
 *         maxLength=500
 *     )
 * )
 */
class CreatePermissionRequest extends FormRequest
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
                'required',
                'string',
                'max:255',
                Rule::unique('permissions')->where(function ($query) {
                    return $query->where('guard_name', $this->guard_name);
                })
            ],
            'guard_name' => 'required|string|in:api,web',
            'description' => 'nullable|string|max:500'
        ];
    }
}

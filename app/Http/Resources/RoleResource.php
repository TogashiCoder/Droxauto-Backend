<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PermissionResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RoleResource",
 *     title="Role Resource",
 *     description="Role data structure returned by the API",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="admin"),
 *     @OA\Property(property="guard_name", type="string", example="api"),
 *     @OA\Property(property="description", type="string", example="Administrator role with full access", nullable=true),
 *     @OA\Property(property="permissions_count", type="integer", example=15),
 *     @OA\Property(property="is_system_role", type="boolean", example=true),
 *     @OA\Property(property="can_be_deleted", type="boolean", example=false),
 *     @OA\Property(property="can_be_modified", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         description="Array of permissions assigned to this role",
 *         @OA\Items(ref="#/components/schemas/PermissionResource")
 *     ),
 *     @OA\Property(
 *         property="links",
 *         type="object",
 *         @OA\Property(property="self", type="string", example="/api/v1/admin/roles/1"),
 *         @OA\Property(property="edit", type="string", example="/api/v1/admin/roles/1"),
 *         @OA\Property(property="delete", type="string", example="/api/v1/admin/roles/1"),
 *         @OA\Property(property="permissions", type="string", example="/api/v1/admin/roles/1")
 *     )
 * )
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'description' => $this->description,
            'permissions_count' => $this->permissions_count ?? $this->permissions()->count(),
            'is_system_role' => $this->isSystemRole(),
            'can_be_deleted' => $this->canBeDeleted(),
            'can_be_modified' => $this->canBeModified(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'links' => [
                'self' => "/api/v1/admin/roles/{$this->id}",
                'edit' => "/api/v1/admin/roles/{$this->id}",
                'delete' => "/api/v1/admin/roles/{$this->id}",
                'permissions' => "/api/v1/admin/roles/{$this->id}"
            ]
        ];
    }

    /**
     * Check if this is a system role
     */
    private function isSystemRole(): bool
    {
        $systemRoles = ['admin', 'basic', 'manager'];
        return in_array($this->name, $systemRoles);
    }

    /**
     * Check if this role can be deleted
     */
    private function canBeDeleted(): bool
    {
        if ($this->isSystemRole()) {
            return false;
        }

        // Check if role has assigned users
        return !$this->hasAssignedUsers();
    }

    /**
     * Check if this role can be modified
     */
    private function canBeModified(): bool
    {
        return true; // All roles can be modified, but some operations may be restricted
    }

    /**
     * Check if role has assigned users
     */
    private function hasAssignedUsers(): bool
    {
        return $this->users()->count() > 0;
    }
}

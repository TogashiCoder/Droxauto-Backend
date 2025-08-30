<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="PermissionResource",
 *     title="Permission Resource",
 *     description="Permission data structure returned by the API",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="manage_users"),
 *     @OA\Property(property="guard_name", type="string", example="api"),
 *     @OA\Property(property="description", type="string", example="Allows users to manage other users", nullable=true),
 *     @OA\Property(property="roles_count", type="integer", example=3),
 *     @OA\Property(property="is_system_permission", type="boolean", example=true),
 *     @OA\Property(property="can_be_deleted", type="boolean", example=false),
 *     @OA\Property(property="can_be_modified", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(
 *         property="links",
 *         type="object",
 *         @OA\Property(property="self", type="string", example="/api/v1/admin/permissions/1"),
 *         @OA\Property(property="edit", type="string", example="/api/v1/admin/permissions/1"),
 *         @OA\Property(property="delete", type="string", example="/api/v1/admin/permissions/1")
 *     )
 * )
 */
class PermissionResource extends JsonResource
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
            'roles_count' => $this->roles_count ?? $this->roles()->count(),
            'is_system_permission' => $this->isSystemPermission(),
            'can_be_deleted' => $this->canBeDeleted(),
            'can_be_modified' => $this->canBeModified(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'links' => [
                'self' => "/api/v1/admin/permissions/{$this->id}",
                'edit' => "/api/v1/admin/permissions/{$this->id}",
                'delete' => "/api/v1/admin/permissions/{$this->id}"
            ]
        ];
    }

    /**
     * Check if this is a system permission
     */
    private function isSystemPermission(): bool
    {
        $systemPermissions = [
            'manage_users',
            'view_users',
            'manage_roles',
            'view_roles',
            'manage_permissions',
            'view_permissions'
        ];
        return in_array($this->name, $systemPermissions);
    }

    /**
     * Check if this permission can be deleted
     */
    private function canBeDeleted(): bool
    {
        if ($this->isSystemPermission()) {
            return false;
        }

        // Check if permission has assigned roles
        return !$this->hasAssignedRoles();
    }

    /**
     * Check if this permission can be modified
     */
    private function canBeModified(): bool
    {
        return true; // All permissions can be modified, but some operations may be restricted
    }

    /**
     * Check if permission has assigned roles
     */
    private function hasAssignedRoles(): bool
    {
        return $this->roles()->count() > 0;
    }
}

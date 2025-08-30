<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'roles_count' => $this->when(isset($this->roles_count), $this->roles_count),
            'created_at' => $this->when(isset($this->created_at), $this->created_at?->toISOString()),
            'updated_at' => $this->when(isset($this->updated_at), $this->updated_at?->toISOString()),

            // Additional computed fields
            'is_system_permission' => $this->when(isset($this->name), $this->isSystemPermission()),
            'can_be_deleted' => $this->when(isset($this->roles_count), $this->roles_count === 0),
            'can_be_modified' => $this->when(isset($this->name), $this->canBeModified()),

            // Links for API navigation
            'links' => [
                'self' => "/api/v1/admin/permissions/{$this->id}",
                'edit' => "/api/v1/admin/permissions/{$this->id}",
                'delete' => "/api/v1/admin/permissions/{$this->id}",
            ],
        ];
    }

    /**
     * Check if this is a system permission
     */
    private function isSystemPermission(): bool
    {
        $systemPermissions = [
            'view users', 'create users', 'edit users', 'delete users',
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view permissions', 'create permissions', 'edit permissions', 'delete permissions',
            'view dapartos', 'create dapartos', 'edit dapartos', 'delete dapartos',
            'upload csv', 'view csv status', 'access admin panel', 'view system stats'
        ];

        return in_array($this->name, $systemPermissions);
    }

    /**
     * Check if this permission can be modified
     */
    private function canBeModified(): bool
    {
        return !$this->isSystemPermission();
    }
}

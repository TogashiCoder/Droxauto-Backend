<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PermissionResource;

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
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at' => $this->when(isset($this->created_at), $this->created_at?->toISOString()),
            'updated_at' => $this->when(isset($this->updated_at), $this->updated_at?->toISOString()),

            // Additional computed fields
            'is_system_role' => $this->when(isset($this->name), in_array($this->name, ['admin', 'basic_user', 'manager'])),
            'can_be_deleted' => $this->when(isset($this->name), !in_array($this->name, ['admin', 'basic_user', 'manager'])),
            'can_be_modified' => $this->when(isset($this->name), !in_array($this->name, ['admin', 'basic_user', 'manager'])),

            // Links for API navigation
            'links' => [
                'self' => "/api/v1/admin/roles/{$this->id}",
                'edit' => "/api/v1/admin/roles/{$this->id}",
                'delete' => "/api/v1/admin/roles/{$this->id}",
                'permissions' => "/api/v1/admin/roles/{$this->id}/permissions",
            ],
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleService
{
    /**
     * Get paginated roles with search and sorting
     */
    public function getPaginatedRoles(
        int $perPage = 15,
        ?string $search = null,
        string $sortBy = 'name',
        string $sortDirection = 'asc'
    ): LengthAwarePaginator {
        $query = Role::with(['permissions'])
            ->where('guard_name', 'api')
            ->withCount(['permissions']);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $allowedSortFields = ['name', 'guard_name', 'created_at', 'permissions_count', 'users_count'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'name';
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'asc';

        return $query->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * Create a new role
     */
    public function createRole(array $data): Role
    {
        DB::beginTransaction();

        try {
            // Set default guard if not provided
            if (!isset($data['guard_name'])) {
                $data['guard_name'] = 'api';
            }

            // Create the role
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'],
                'description' => $data['description'] ?? null,
            ]);

            // Assign permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $permissions = Permission::whereIn('name', $data['permissions'])
                    ->where('guard_name', $data['guard_name'])
                    ->get();

                $role->syncPermissions($permissions);
            }

            DB::commit();

            // Load relationships for response
            $role->load(['permissions']);

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create role', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing role
     */
    public function updateRole(Role $role, array $data): Role
    {
        DB::beginTransaction();

        try {
            // Update basic role information
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (!empty($updateData)) {
                $role->update($updateData);
            }

            // Update permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $permissions = Permission::whereIn('name', $data['permissions'])
                    ->where('guard_name', $role->guard_name)
                    ->get();

                $role->syncPermissions($permissions);
            }

            DB::commit();

            // Load relationships for response
            $role->load(['permissions']);

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update role', [
                'role_id' => $role->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole(Role $role): bool
    {
        DB::beginTransaction();

        try {
            // Remove all permissions from the role
            $role->syncPermissions([]);

            // Remove the role from all users
            $role->users()->detach();

            // Delete the role
            $deleted = $role->delete();

            DB::commit();

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Check if a role is a system role
     */
    public function isSystemRole(Role $role): bool
    {
        $systemRoles = ['admin', 'basic_user', 'manager'];
        return in_array($role->name, $systemRoles);
    }

    /**
     * Check if a role has assigned users
     */
    public function hasAssignedUsers(Role $role): bool
    {
        return $role->users()->count() > 0;
    }

    /**
     * Get role statistics
     */
    public function getRoleStatistics(): array
    {
        $totalRoles = Role::where('guard_name', 'api')->count();
        $totalPermissions = Permission::where('guard_name', 'api')->count();

        $rolesWithUsers = Role::where('guard_name', 'api')
            ->whereHas('users')
            ->count();

        $rolesWithPermissions = Role::where('guard_name', 'api')
            ->whereHas('permissions')
            ->count();

        return [
            'total_roles' => $totalRoles,
            'total_permissions' => $totalPermissions,
            'roles_with_users' => $rolesWithUsers,
            'roles_with_permissions' => $rolesWithPermissions,
            'unused_roles' => $totalRoles - $rolesWithUsers,
            'roles_without_permissions' => $totalRoles - $rolesWithPermissions,
        ];
    }

    /**
     * Bulk assign permissions to roles
     */
    public function bulkAssignPermissions(array $rolePermissionMap): array
    {
        $results = [];

        foreach ($rolePermissionMap as $roleName => $permissionNames) {
            try {
                $role = Role::where('name', $roleName)
                    ->where('guard_name', 'api')
                    ->first();

                if (!$role) {
                    $results[$roleName] = [
                        'success' => false,
                        'message' => 'Role not found'
                    ];
                    continue;
                }

                $permissions = Permission::whereIn('name', $permissionNames)
                    ->where('guard_name', 'api')
                    ->get();

                $role->syncPermissions($permissions);

                $results[$roleName] = [
                    'success' => true,
                    'message' => 'Permissions assigned successfully',
                    'permissions_count' => $permissions->count()
                ];
            } catch (\Exception $e) {
                Log::error('Failed to assign permissions to role', [
                    'role_name' => $roleName,
                    'permissions' => $permissionNames,
                    'error' => $e->getMessage()
                ]);

                $results[$roleName] = [
                    'success' => false,
                    'message' => 'Failed to assign permissions: ' . $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Clone a role with new name
     */
    public function cloneRole(Role $sourceRole, string $newName, ?string $description = null): Role
    {
        DB::beginTransaction();

        try {
            // Create new role
            $newRole = Role::create([
                'name' => $newName,
                'guard_name' => $sourceRole->guard_name,
                'description' => $description ?? "Cloned from {$sourceRole->name}"
            ]);

            // Copy permissions
            $permissions = $sourceRole->permissions;
            $newRole->syncPermissions($permissions);

            DB::commit();

            // Load relationships for response
            $newRole->load(['permissions']);

            return $newRole;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to clone role', [
                'source_role_id' => $sourceRole->id,
                'new_name' => $newName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get roles by permission
     */
    public function getRolesByPermission(string $permissionName): \Illuminate\Database\Eloquent\Collection
    {
        return Role::where('guard_name', 'api')
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->with(['permissions'])
            ->get();
    }

    /**
     * Validate role hierarchy
     */
    public function validateRoleHierarchy(Role $parentRole, Role $childRole): bool
    {
        // Prevent circular dependencies
        if ($parentRole->id === $childRole->id) {
            return false;
        }

        // Check if child role already has parent role as a child (circular)
        $hasCircular = $this->hasCircularDependency($parentRole, $childRole);

        return !$hasCircular;
    }

    /**
     * Check for circular dependencies in role hierarchy
     */
    private function hasCircularDependency(Role $parentRole, Role $childRole): bool
    {
        // This is a simplified check - in a real implementation,
        // you might have a role_hierarchy table or similar structure
        // For now, we'll just prevent basic circular references

        return false;
    }
}

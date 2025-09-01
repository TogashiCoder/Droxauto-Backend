<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Get paginated permissions with roles count
     */
    public function getPaginatedPermissions(
        int $perPage = 15,
        string $search = null,
        string $sortBy = 'name',
        string $sortDirection = 'asc'
    ): LengthAwarePaginator {
        $query = Permission::withCount('roles')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy($sortBy, $sortDirection);

        return Cache::tags(['rbac', 'permissions'])->remember(
            "permissions_page_{$perPage}_search_{$search}_sort_{$sortBy}_{$sortDirection}",
            now()->addMinutes(30),
            fn() => $query->paginate($perPage)
        );
    }

    /**
     * Create a new permission
     */
    public function createPermission(array $data): Permission
    {
        return DB::transaction(function () use ($data) {
            $permission = Permission::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'api',
                'description' => $data['description'] ?? null,
            ]);

            Log::info('Permission created', [
                'permission_id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name
            ]);

            return $permission;
        });
    }

    /**
     * Update an existing permission
     */
    public function updatePermission(Permission $permission, array $data): Permission
    {
        return DB::transaction(function () use ($permission, $data) {
            $oldName = $permission->name;

            $permission->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? $permission->description,
            ]);

            Log::info('Permission updated', [
                'permission_id' => $permission->id,
                'old_name' => $oldName,
                'new_name' => $permission->name
            ]);

            return $permission->fresh();
        });
    }

    /**
     * Delete a permission
     */
    public function deletePermission(Permission $permission): bool
    {
        return DB::transaction(function () use ($permission) {
            // Check if permission has assigned roles
            if ($permission->roles()->count() > 0) {
                throw new \Exception('Cannot delete permission with assigned roles');
            }

            $deleted = $permission->delete();

            if ($deleted) {
                Log::info('Permission deleted', [
                    'permission_id' => $permission->id,
                    'name' => $permission->name
                ]);
            }

            return $deleted;
        });
    }

    /**
     * Check if permission is a system permission
     */
    public function isSystemPermission(string $permissionName): bool
    {
        $systemPermissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'view dapartos',
            'create dapartos',
            'edit dapartos',
            'delete dapartos',
            'upload csv',
            'view csv status',
            'access admin panel',
            'view system stats'
        ];

        return in_array($permissionName, $systemPermissions);
    }

    /**
     * Check if permission has assigned roles
     */
    public function hasAssignedRoles(Permission $permission): bool
    {
        return $permission->roles()->count() > 0;
    }

    /**
     * Get permission statistics
     */
    public function getPermissionStatistics(): array
    {
        return Cache::tags(['rbac', 'permissions'])->remember(
            'permission_statistics',
            now()->addMinutes(15),
            function () {
                return [
                    'total_permissions' => Permission::count(),
                    'system_permissions_count' => Permission::whereIn('name', [
                        'view users',
                        'create users',
                        'edit users',
                        'delete users',
                        'view roles',
                        'create roles',
                        'edit roles',
                        'delete roles',
                        'view permissions',
                        'create permissions',
                        'edit permissions',
                        'delete permissions',
                        'view dapartos',
                        'create dapartos',
                        'edit dapartos',
                        'delete dapartos',
                        'upload csv',
                        'view csv status',
                        'access admin panel',
                        'view system stats'
                    ])->count(),
                    'custom_permissions_count' => Permission::whereNotIn('name', [
                        'view users',
                        'create users',
                        'edit users',
                        'delete users',
                        'view roles',
                        'create roles',
                        'edit roles',
                        'delete roles',
                        'view permissions',
                        'create permissions',
                        'edit permissions',
                        'delete permissions',
                        'view dapartos',
                        'create dapartos',
                        'edit dapartos',
                        'delete dapartos',
                        'upload csv',
                        'view csv status',
                        'access admin panel',
                        'view system stats'
                    ])->count(),
                    'permissions_by_guard' => Permission::selectRaw('guard_name, count(*) as count')
                        ->groupBy('guard_name')
                        ->pluck('count', 'guard_name')
                        ->toArray(),
                    'most_used_permissions' => Permission::withCount('roles')
                        ->orderBy('roles_count', 'desc')
                        ->limit(5)
                        ->pluck('name', 'id')
                        ->toArray(),
                    'unused_permissions_count' => Permission::doesntHave('roles')->count(),
                ];
            }
        );
    }

    /**
     * Bulk assign permissions to roles
     */
    public function bulkAssignToRoles(array $permissionIds, array $roleIds): array
    {
        return DB::transaction(function () use ($permissionIds, $roleIds) {
            $permissions = Permission::whereIn('id', $permissionIds)->get();
            $roles = Role::whereIn('id', $roleIds)->get();

            $assigned = [];

            foreach ($roles as $role) {
                foreach ($permissions as $permission) {
                    if (!$role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                        $assigned[] = [
                            'role' => $role->name,
                            'permission' => $permission->name
                        ];
                    }
                }
            }

            Log::info('Bulk permissions assigned to roles', [
                'permission_ids' => $permissionIds,
                'role_ids' => $roleIds,
                'assignments_count' => count($assigned)
            ]);

            return $assigned;
        });
    }

    /**
     * Clone a permission
     */
    public function clonePermission(Permission $permission, string $newName, string $description = null): Permission
    {
        return DB::transaction(function () use ($permission, $newName, $description) {
            $clonedPermission = Permission::create([
                'name' => $newName,
                'guard_name' => $permission->guard_name,
                'description' => $description ?? "Cloned from {$permission->name}",
            ]);

            Log::info('Permission cloned', [
                'original_permission_id' => $permission->id,
                'cloned_permission_id' => $clonedPermission->id,
                'original_name' => $permission->name,
                'new_name' => $newName
            ]);

            return $clonedPermission;
        });
    }

    /**
     * Get roles that have a specific permission
     */
    public function getRolesByPermission(string $permissionName): Collection
    {
        return Cache::tags(['rbac', 'permissions'])->remember(
            "roles_with_permission_{$permissionName}",
            now()->addMinutes(15),
            fn() => Role::whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })->get()
        );
    }

    /**
     * Validate permission hierarchy (placeholder for future implementation)
     */
    public function validatePermissionHierarchy(array $permissions): bool
    {
        // Permission hierarchy validation - Future enhancement
        // This could include checking for conflicting permissions
        // or ensuring proper permission dependencies
        return true;
    }

    /**
     * Check for circular dependencies (placeholder for future implementation)
     */
    public function hasCircularDependency(array $permissions): bool
    {
        // Circular dependency detection - Future enhancement
        // This could be useful for complex permission systems
        return false;
    }
}

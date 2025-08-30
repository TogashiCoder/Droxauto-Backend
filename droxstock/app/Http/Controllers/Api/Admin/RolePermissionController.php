<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    /**
     * Assign a permission to a role
     */
    public function assignPermission(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_id' => 'required|integer',
                'permission_id' => 'required|integer'
            ]);

            $role = Role::find($validated['role_id']);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $permission = Permission::find($validated['permission_id']);
            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], 404);
            }

            // Check guard names
            if ($role->guard_name !== $permission->guard_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role and permission must have the same guard'
                ], 422);
            }

            // Check if role already has this permission - handle gracefully
            if ($role->hasPermissionTo($permission)) {
                // Role already has this permission, return success
                return response()->json([
                    'success' => true,
                    'message' => 'Permission assigned to role successfully',
                    'data' => [
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                        'permission_name' => $permission->name,
                        'note' => 'Role already had this permission'
                    ]
                ]);
            }

            DB::transaction(function () use ($role, $permission) {
                $role->givePermissionTo($permission);
            });

            // Flush cache
            Cache::tags(['rbac', 'roles', 'permissions'])->flush();

            Log::info('Permission assigned to role successfully', [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to role successfully',
                'data' => [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Permission assignment validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to assign permission to role', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permission',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Assign multiple permissions to a role
     */
    public function assignMultiplePermissions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_id' => 'required|integer',
                'permission_ids' => 'required|array|min:1',
                'permission_ids.*' => 'integer'
            ]);

            $role = Role::find($validated['role_id']);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $permissions = Permission::whereIn('id', $validated['permission_ids'])->get();
            if ($permissions->count() !== count($validated['permission_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more permissions not found'
                ], 404);
            }

            // Check guard names
            $invalidPermissions = $permissions->where('guard_name', '!=', $role->guard_name);
            if ($invalidPermissions->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some permissions have invalid guard names'
                ], 422);
            }

            $assignedPermissions = [];
            $alreadyAssigned = [];

            DB::transaction(function () use ($role, $permissions, &$assignedPermissions, &$alreadyAssigned) {
                foreach ($permissions as $permission) {
                    if ($role->hasPermissionTo($permission)) {
                        $alreadyAssigned[] = $permission->name;
                    } else {
                        $role->givePermissionTo($permission);
                        $assignedPermissions[] = $permission->name;
                    }
                }
            });

            // Flush cache
            Cache::tags(['rbac', 'roles', 'permissions'])->flush();

            Log::info('Multiple permissions assigned to role successfully', [
                'role_id' => $role->id,
                'assigned_permissions' => $assignedPermissions,
                'already_assigned' => $alreadyAssigned
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Multiple permissions assigned to role successfully',
                'data' => [
                    'role_id' => $role->id,
                    'assigned_permissions' => $assignedPermissions,
                    'already_assigned' => $alreadyAssigned
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Multiple permission assignment validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to assign multiple permissions to role', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove a permission from a role
     */
    public function removePermission(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_id' => 'required|integer',
                'permission_id' => 'required|integer'
            ]);

            $role = Role::find($validated['role_id']);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $permission = Permission::find($validated['permission_id']);
            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], 404);
            }

            // Check if role has this permission
            if (!$role->hasPermissionTo($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role does not have this permission'
                ], 422);
            }

            // Check if this is a critical permission for admin role
            if ($this->isCriticalPermissionForAdminRole($permission, $role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove critical permissions from admin role'
                ], 422);
            }

            DB::transaction(function () use ($role, $permission) {
                $role->revokePermissionTo($permission);
            });

            // Flush cache
            Cache::tags(['rbac', 'roles', 'permissions'])->flush();

            Log::info('Permission removed from role successfully', [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission removed from role successfully',
                'data' => [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Permission removal validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to remove permission from role', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove permission',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove all permissions from a role
     */
    public function removeAllPermissions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_id' => 'required|integer'
            ]);

            $role = Role::find($validated['role_id']);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            // Get current permissions
            $currentPermissions = $role->getAllPermissions();

            // Prevent removing all permissions from system roles
            if ($this->isSystemRole($role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove all permissions from system roles'
                ], 422);
            }

            DB::transaction(function () use ($role) {
                $role->syncPermissions([]);
            });

            // Flush cache
            Cache::tags(['rbac', 'roles', 'permissions'])->flush();

            Log::info('All permissions removed from role successfully', [
                'role_id' => $role->id,
                'removed_permissions_count' => $currentPermissions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All permissions removed from role successfully',
                'data' => [
                    'role_id' => $role->id,
                    'removed_permissions_count' => $currentPermissions->count()
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Remove all permissions validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to remove all permissions from role', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove all permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if this is a critical permission for admin role
     */
    private function isCriticalPermissionForAdminRole(Permission $permission, Role $role): bool
    {
        // Check if role is admin
        if ($role->name !== 'admin') {
            return false;
        }

        // Define critical permissions that admin role should always have
        $criticalPermissions = [
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_users',
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'manage_roles',
            'view_permissions',
            'create_permissions',
            'edit_permissions',
            'delete_permissions',
            'manage_permissions',
            'access_admin_panel'
        ];

        return in_array($permission->name, $criticalPermissions);
    }

    /**
     * Check if role is a system role
     */
    private function isSystemRole(Role $role): bool
    {
        $systemRoles = ['admin', 'basic_user', 'manager'];
        return in_array($role->name, $systemRoles);
    }
}

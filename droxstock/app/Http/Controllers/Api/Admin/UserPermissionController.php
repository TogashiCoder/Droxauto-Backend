<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    /**
     * Assign a permission directly to a user
     */
    public function assignPermission(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'permission_id' => 'required|integer'
            ]);

            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $permission = Permission::find($validated['permission_id']);
            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], 404);
            }

            // Check guard name
            if ($permission->guard_name !== 'api') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid permission guard'
                ], 422);
            }

            // Check if user already has this permission directly - handle gracefully
            if ($user->hasDirectPermission($permission)) {
                // User already has this permission, return success
                return response()->json([
                    'success' => true,
                    'message' => 'Permission assigned to user successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'permission_id' => $permission->id,
                        'permission_name' => $permission->name,
                        'note' => 'User already had this permission directly'
                    ]
                ]);
            }

            DB::transaction(function () use ($user, $permission) {
                $user->givePermissionTo($permission);
            });

            // Flush cache
            Cache::tags(['rbac', 'users', 'permissions'])->flush();

            Log::info('Permission assigned directly to user successfully', [
                'user_id' => $user->id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to user successfully',
                'data' => [
                    'user_id' => $user->id,
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
            Log::error('Failed to assign permission to user', [
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
     * Remove a permission directly from a user
     */
    public function removePermission(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'permission_id' => 'required|integer'
            ]);

            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $permission = Permission::find($validated['permission_id']);
            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], 404);
            }

            // Check guard name
            if ($permission->guard_name !== 'api') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid permission guard'
                ], 422);
            }

            // Check if user has this permission directly
            if (!$user->hasDirectPermission($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have this permission directly'
                ], 422);
            }

            // Check if this is a critical permission for admin users
            $isCritical = $this->isCriticalPermissionForAdmin($permission, $user);
            Log::info('Critical permission check', [
                'permission_name' => $permission->name,
                'user_id' => $user->id,
                'is_critical' => $isCritical,
                'user_has_admin_role' => $user->hasRole('admin')
            ]);

            if ($isCritical) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove critical permissions from admin users'
                ], 422);
            }

            DB::transaction(function () use ($user, $permission) {
                $user->revokePermissionTo($permission);
            });

            // Flush cache
            Cache::tags(['rbac', 'users', 'permissions'])->flush();

            Log::info('Permission removed directly from user successfully', [
                'user_id' => $user->id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission removed from user successfully',
                'data' => [
                    'user_id' => $user->id,
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
            Log::error('Failed to remove permission from user', [
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
     * Assign multiple permissions directly to a user
     */
    public function assignMultiplePermissions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'permission_ids' => 'required|array|min:1',
                'permission_ids.*' => 'integer'
            ]);

            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
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
            $invalidPermissions = $permissions->where('guard_name', '!=', 'api');
            if ($invalidPermissions->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some permissions have invalid guard names'
                ], 422);
            }

            $assignedPermissions = [];
            $alreadyAssigned = [];

            DB::transaction(function () use ($user, $permissions, &$assignedPermissions, &$alreadyAssigned) {
                foreach ($permissions as $permission) {
                    if ($user->hasDirectPermission($permission)) {
                        $alreadyAssigned[] = $permission->name;
                    } else {
                        $user->givePermissionTo($permission);
                        $assignedPermissions[] = $permission->name;
                    }
                }
            });

            // Flush cache
            Cache::tags(['rbac', 'users', 'permissions'])->flush();

            Log::info('Multiple permissions assigned directly to user', [
                'user_id' => $user->id,
                'assigned_permissions' => $assignedPermissions,
                'already_assigned' => $alreadyAssigned
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Multiple permissions assigned to user successfully',
                'data' => [
                    'user_id' => $user->id,
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
            Log::error('Failed to assign multiple permissions to user', [
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
     * Remove all permissions directly from a user
     */
    public function removeAllPermissions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer'
            ]);

            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get current direct permissions
            $currentPermissions = $user->getDirectPermissions();

            // Prevent removing all permissions from admin users
            if ($user->hasRole('admin') && $this->hasCriticalPermissions($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove all permissions from admin users with critical permissions'
                ], 422);
            }

            DB::transaction(function () use ($user) {
                $user->revokePermissionTo($user->getDirectPermissions());
            });

            // Flush cache
            Cache::tags(['rbac', 'users', 'permissions'])->flush();

            Log::info('All permissions removed directly from user successfully', [
                'user_id' => $user->id,
                'removed_permissions_count' => $currentPermissions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All permissions removed from user successfully',
                'data' => [
                    'user_id' => $user->id,
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
            Log::error('Failed to remove all permissions from user', [
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
     * Check if user has critical permissions
     */
    private function hasCriticalPermissions(User $user): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }

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

        return $user->hasAnyPermission($criticalPermissions);
    }

    /**
     * Check if this is a critical permission for admin users
     */
    private function isCriticalPermissionForAdmin(Permission $permission, User $user): bool
    {
        // Check if user has admin role
        if (!$user->hasRole('admin')) {
            Log::info('User does not have admin role', ['user_id' => $user->id]);
            return false;
        }

        // Define critical permissions that admin users should always have
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

        $isCritical = in_array($permission->name, $criticalPermissions);

        return $isCritical;
    }
}

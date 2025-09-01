<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    /**
     * Assign a role to a user
     */
    public function assignRole(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|uuid',
                'role_id' => 'required|integer',
                'guard_name' => 'string|max:255'
            ]);

            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $role = Role::find($validated['role_id']);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            // Check guard name
            if ($role->guard_name !== 'api') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role guard'
                ], 422);
            }

            // Check if user already has this role - handle gracefully
            if ($user->hasRole($role)) {
                // User already has this role, return success
                return response()->json([
                    'success' => true,
                    'message' => 'Role assigned to user successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'note' => 'User already had this role'
                    ]
                ]);
            }

            DB::transaction(function () use ($user, $role) {
                $user->assignRole($role);
            });

            // Flush cache
            Cache::tags(['rbac', 'users', 'roles'])->flush();

            Log::info('Role assigned to user successfully', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned to user successfully',
                'data' => [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'role_name' => $role->name
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Role assignment validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to assign role to user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Assign multiple roles to a user
     */
    public function assignMultipleRoles(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|uuid',
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'integer'
            ]);

            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }



            $roles = Role::whereIn('id', $validated['role_ids'])->get();
            if ($roles->count() !== count($validated['role_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more roles not found'
                ], 404);
            }

            // Check guard names
            $invalidRoles = $roles->where('guard_name', '!=', 'api');
            if ($invalidRoles->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some roles have invalid guard names'
                ], 422);
            }

            $assignedRoles = [];
            $alreadyAssigned = [];

            DB::transaction(function () use ($user, $roles, &$assignedRoles, &$alreadyAssigned) {
                foreach ($roles as $role) {
                    if ($user->hasRole($role)) {
                        $alreadyAssigned[] = $role->name;
                    } else {
                        $user->assignRole($role);
                        $assignedRoles[] = $role->name;
                    }
                }
            });

            // Flush cache
            Cache::tags(['rbac', 'users', 'roles'])->flush();

            Log::info('Multiple roles assigned to user', [
                'user_id' => $user->id,
                'assigned_roles' => $assignedRoles,
                'already_assigned' => $alreadyAssigned
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Multiple roles assigned to user successfully',
                'data' => [
                    'user_id' => $user->id,
                    'assigned_roles' => $assignedRoles,
                    'already_assigned' => $alreadyAssigned
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Multiple role assignment validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to assign multiple roles to user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove a role from a user
     */
    public function removeRole(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|uuid',
                'role_id' => 'required|integer'
            ]);

            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $role = Role::find($validated['role_id']);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            // Check guard name
            if ($role->guard_name !== 'api') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role guard'
                ], 422);
            }

            // Check if user has this role
            if (!$user->hasRole($role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have this role'
                ], 422);
            }

            // Prevent removing admin role from the last admin user
            if ($role->name === 'admin' && $this->isLastAdminUser($user, $role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove admin role from last admin user'
                ], 422);
            }

            DB::transaction(function () use ($user, $role) {
                $user->removeRole($role);
            });

            // Flush cache
            Cache::tags(['rbac', 'users', 'roles'])->flush();

            Log::info('Role removed from user successfully', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role removed from user successfully',
                'data' => [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'role_name' => $role->name
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Role removal validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to remove role from user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove role',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove all roles from a user
     */
    public function removeAllRoles(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|uuid'
            ]);

            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get current roles
            $currentRoles = $user->roles;

            // Prevent removing all roles from admin users
            if ($currentRoles->where('name', 'admin')->count() > 0 && $this->isLastAdminUser($user, null)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove all roles from the last admin user'
                ], 422);
            }

            DB::transaction(function () use ($user) {
                $user->syncRoles([]);
            });

            // Flush cache
            Cache::tags(['rbac', 'users', 'roles'])->flush();

            Log::info('All roles removed from user successfully', [
                'user_id' => $user->id,
                'removed_roles_count' => $currentRoles->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All roles removed from user successfully',
                'data' => [
                    'user_id' => $user->id,
                    'removed_roles_count' => $currentRoles->count()
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Remove all roles validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to remove all roles from user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove all roles',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user permissions (including those from roles)
     */
    public function getUserPermissions(string $id): JsonResponse
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $permissions = $user->getAllPermissions();
            $roles = $user->roles;

            Log::info('User permissions retrieved successfully', [
                'user_id' => $user->id,
                'permissions_count' => $permissions->count(),
                'roles_count' => $roles->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User permissions retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'roles' => $roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'guard_name' => $role->guard_name,
                            'permissions' => $role->permissions->map(function ($permission) {
                                return [
                                    'id' => $permission->id,
                                    'name' => $permission->name,
                                    'guard_name' => $permission->guard_name
                                ];
                            })
                        ];
                    }),
                    'direct_permissions' => $permissions->filter(function ($permission) use ($roles) {
                        // Filter out permissions that come from roles
                        $rolePermissionIds = $roles->flatMap->permissions->pluck('id');
                        return !$rolePermissionIds->contains($permission->id);
                    })->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name
                        ];
                    }),
                    'all_permissions' => $permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user permissions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if this is the last admin user
     */
    private function isLastAdminUser(User $user, ?Role $role): bool
    {
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();

        if (!$adminRole) {
            return false;
        }

        // Count users with admin role
        $adminUsersCount = User::role('admin')->count();

        if ($role && $role->name === 'admin') {
            // If removing admin role, check if this is the last admin
            return $adminUsersCount === 1 && $user->hasRole($adminRole);
        } else {
            // If removing all roles, check if this user is admin and if they're the last one
            return $user->hasRole($adminRole) && $adminUsersCount === 1;
        }
    }
}

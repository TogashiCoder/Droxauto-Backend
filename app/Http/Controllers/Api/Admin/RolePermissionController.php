<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="RBAC - Role Permission Management",
 *     description="API Endpoints for managing role-permission relationships"
 * )
 */
class RolePermissionController extends Controller
{
    /**
     * Assign a single permission to a role
     *
     * @OA\Post(
     *     path="/api/v1/admin/roles/assign-permission",
     *     summary="Assign permission to role",
     *     description="Assigns a specific permission to a role, handling duplicate assignments gracefully",
     *     tags={"RBAC - Role Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="role_id", type="integer", example=2, description="Role ID"),
     *             @OA\Property(property="permission_id", type="integer", example=3, description="Permission ID to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission assigned to role successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="permission_id", type="integer", example=3),
     *                 @OA\Property(property="role_name", type="string", example="manager"),
     *                 @OA\Property(property="permission_name", type="string", example="custom_permission"),
     *                 @OA\Property(property="note", type="string", example="Permission was already assigned", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role or permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Role not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function assignPermission(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|integer',
            'permission_id' => 'required|integer'
        ]);

        $role = Role::find($request->role_id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $permission = Permission::find($request->permission_id);
        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        if ($role->hasPermissionTo($permission)) {
            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to role successfully',
                'data' => [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'role_name' => $role->name,
                    'permission_name' => $permission->name,
                    'note' => 'Permission was already assigned'
                ]
            ]);
        }

        $role->givePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned to role successfully',
            'data' => [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'role_name' => $role->name,
                'permission_name' => $permission->name
            ]
        ]);
    }

    /**
     * Assign multiple permissions to a role
     *
     * @OA\Post(
     *     path="/api/v1/admin/roles/assign-multiple-permissions",
     *     summary="Assign multiple permissions to role",
     *     description="Assigns multiple permissions to a role in a single operation",
     *     tags={"RBAC - Role Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="role_id", type="integer", example=2, description="Role ID"),
     *             @OA\Property(property="permission_ids", type="array", @OA\Items(type="integer"), example={3, 4}, description="Array of permission IDs to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Multiple permissions assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Multiple permissions assigned to role successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="role_name", type="string", example="manager"),
     *                 @OA\Property(property="assigned_permissions", type="array", @OA\Items(type="string"), example={"custom_permission", "another_permission"}),
     *                 @OA\Property(property="total_assigned", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role or permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Role not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function assignMultiplePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|integer',
            'permission_ids' => 'required|array|min:1',
            'permission_ids.*' => 'integer|exists:permissions,id'
        ]);

        $role = Role::find($request->role_id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $permissions = Permission::whereIn('id', $request->permission_ids)->get();
        $assignedPermissions = [];

        foreach ($permissions as $permission) {
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
            $assignedPermissions[] = $permission->name;
        }

        return response()->json([
            'success' => true,
            'message' => 'Multiple permissions assigned to role successfully',
            'data' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'assigned_permissions' => $assignedPermissions,
                'total_assigned' => count($assignedPermissions)
            ]
        ]);
    }

    /**
     * Remove a specific permission from a role
     *
     * @OA\Post(
     *     path="/api/v1/admin/roles/remove-permission",
     *     summary="Remove permission from role",
     *     description="Removes a specific permission from a role, with protection for critical permissions on admin roles",
     *     tags={"RBAC - Role Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="role_id", type="integer", example=2, description="Role ID"),
     *             @OA\Property(property="permission_id", type="integer", example=3, description="Permission ID to remove")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission removed from role successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="permission_id", type="integer", example=3),
     *                 @OA\Property(property="role_name", type="string", example="manager"),
     *                 @OA\Property(property="permission_name", type="string", example="custom_permission")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role or permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Role not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot remove permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot remove critical permissions from admin role")
     *         )
     *     )
     * )
     */
    public function removePermission(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|integer',
            'permission_id' => 'required|integer'
        ]);

        $role = Role::find($request->role_id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $permission = Permission::find($request->permission_id);
        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        if ($role->name === 'admin' && $this->isCriticalPermissionForAdminRole($permission, $role)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove critical permissions from admin role'
            ], 422);
        }

        if ($this->isSystemRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify permissions on system role'
            ], 422);
        }

        $role->revokePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission removed from role successfully',
            'data' => [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'role_name' => $role->name,
                'permission_name' => $permission->name
            ]
        ]);
    }

    /**
     * Remove all permissions from a role
     *
     * @OA\Post(
     *     path="/api/v1/admin/roles/remove-all-permissions",
     *     summary="Remove all permissions from role",
     *     description="Removes all permissions from a role, with protection for system roles and admin roles",
     *     tags={"RBAC - Role Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="role_id", type="integer", example=2, description="Role ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All permissions removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All permissions removed from role successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="role_name", type="string", example="manager"),
     *                 @OA\Property(property="removed_permissions", type="array", @OA\Items(type="string"), example={"custom_permission", "another_permission"}),
     *                 @OA\Property(property="total_removed", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Role not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot remove all permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot remove all permissions from system role")
     *         )
     *     )
     * )
     */
    public function removeAllPermissions(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|integer'
        ]);

        $role = Role::find($request->role_id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        if ($this->isSystemRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove all permissions from system role'
            ], 422);
        }

        if ($role->name === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove all permissions from admin role'
            ], 422);
        }

        $removedPermissions = $role->getAllPermissions()->pluck('name')->toArray();
        $role->syncPermissions([]);

        return response()->json([
            'success' => true,
            'message' => 'All permissions removed from role successfully',
            'data' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'removed_permissions' => $removedPermissions,
                'total_removed' => count($removedPermissions)
            ]
        ]);
    }

    /**
     * Check if permission is critical for admin role
     */
    private function isCriticalPermissionForAdminRole(Permission $permission, Role $role): bool
    {
        if ($role->name !== 'admin') {
            return false;
        }

        $criticalPermissions = [
            'manage_users',
            'view_roles',
            'manage_roles',
            'manage_permissions',
            'view_permissions'
        ];

        return in_array($permission->name, $criticalPermissions);
    }

    /**
     * Check if role is a system role
     */
    private function isSystemRole(Role $role): bool
    {
        $systemRoles = ['admin', 'basic', 'manager'];
        return in_array($role->name, $systemRoles);
    }
}

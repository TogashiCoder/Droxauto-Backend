<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="RBAC - User Permission Management",
 *     description="API Endpoints for managing direct user permissions"
 * )
 */
class UserPermissionController extends Controller
{
    /**
     * Assign a single permission to a user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users/assign-permission",
     *     summary="Assign permission to user",
     *     description="Assigns a specific permission directly to a user, handling duplicate assignments gracefully",
     *     tags={"RBAC - User Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="User ID"),
     *             @OA\Property(property="permission_id", type="integer", example=3, description="Permission ID to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission assigned to user successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="permission_id", type="integer", example=3),
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
     *         description="User or permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
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
            'user_id' => 'required|integer',
            'permission_id' => 'required|integer'
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $permission = Permission::find($request->permission_id);
        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        if ($user->hasDirectPermission($permission)) {
            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to user successfully',
                'data' => [
                    'user_id' => $user->id,
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name,
                    'note' => 'Permission was already assigned'
                ]
            ]);
        }

        $user->givePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned to user successfully',
            'data' => [
                'user_id' => $user->id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]
        ]);
    }

    /**
     * Remove a specific permission from a user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users/remove-permission",
     *     summary="Remove permission from user",
     *     description="Removes a specific permission from a user, with protection for critical permissions on admin users",
     *     tags={"RBAC - User Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="User ID"),
     *             @OA\Property(property="permission_id", type="integer", example=3, description="Permission ID to remove")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission removed from user successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="permission_id", type="integer", example=3),
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
     *         description="User or permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot remove permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot remove critical permissions from admin users")
     *         )
     *     )
     * )
     */
    public function removePermission(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'permission_id' => 'required|integer'
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $permission = Permission::find($request->permission_id);
        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        if ($user->hasRole('admin') && $this->isCriticalPermissionForAdmin($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove critical permissions from admin users'
            ], 422);
        }

        $user->revokePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission removed from user successfully',
            'data' => [
                'user_id' => $user->id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]
        ]);
    }

    /**
     * Assign multiple permissions to a user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users/assign-multiple-permissions",
     *     summary="Assign multiple permissions to user",
     *     description="Assigns multiple permissions directly to a user in a single operation",
     *     tags={"RBAC - User Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="User ID"),
     *             @OA\Property(property="permission_ids", type="array", @OA\Items(type="integer"), example={3, 4}, description="Array of permission IDs to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Multiple permissions assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Multiple permissions assigned to user successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
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
     *         description="User or permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
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
            'user_id' => 'required|integer',
            'permission_ids' => 'required|array|min:1',
            'permission_ids.*' => 'integer|exists:permissions,id'
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $permissions = Permission::whereIn('id', $request->permission_ids)->get();
        $assignedPermissions = [];

        foreach ($permissions as $permission) {
            if (!$user->hasDirectPermission($permission)) {
                $user->givePermissionTo($permission);
            }
            $assignedPermissions[] = $permission->name;
        }

        return response()->json([
            'success' => true,
            'message' => 'Multiple permissions assigned to user successfully',
            'data' => [
                'user_id' => $user->id,
                'assigned_permissions' => $assignedPermissions,
                'total_assigned' => count($assignedPermissions)
            ]
        ]);
    }

    /**
     * Remove all direct permissions from a user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users/remove-all-permissions",
     *     summary="Remove all direct permissions from user",
     *     description="Removes all direct permissions from a user, with protection for admin users with critical permissions",
     *     tags={"RBAC - User Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="User ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All direct permissions removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All direct permissions removed from user successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
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
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot remove all permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot remove all permissions from admin user with critical permissions")
     *         )
     *     )
     * )
     */
    public function removeAllPermissions(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->hasRole('admin') && $this->hasCriticalPermissions($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove all permissions from admin user with critical permissions'
            ], 422);
        }

        $removedPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
        $user->revokePermissionTo($user->getDirectPermissions());

        return response()->json([
            'success' => true,
            'message' => 'All direct permissions removed from user successfully',
            'data' => [
                'user_id' => $user->id,
                'removed_permissions' => $removedPermissions,
                'total_removed' => count($removedPermissions)
            ]
        ]);
    }

    /**
     * Check if permission is critical for admin users
     */
    private function isCriticalPermissionForAdmin(Permission $permission): bool
    {
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
     * Check if user has critical permissions
     */
    private function hasCriticalPermissions(User $user): bool
    {
        $criticalPermissions = [
            'manage_users',
            'view_roles',
            'manage_roles',
            'manage_permissions',
            'view_permissions'
        ];

        foreach ($criticalPermissions as $permissionName) {
            if ($user->hasDirectPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }
}

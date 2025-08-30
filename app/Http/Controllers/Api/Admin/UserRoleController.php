<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="RBAC - User Role Assignment",
 *     description="API Endpoints for assigning and managing user roles"
 * )
 */
class UserRoleController extends Controller
{
    /**
     * Assign a single role to a user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users/assign-role",
     *     summary="Assign role to user",
     *     description="Assigns a specific role to a user, handling duplicate assignments gracefully",
     *     tags={"RBAC - User Role Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="User ID"),
     *             @OA\Property(property="role_id", type="integer", example=2, description="Role ID to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role assigned to user successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="role_name", type="string", example="manager"),
     *                 @OA\Property(property="note", type="string", example="Role was already assigned", nullable=true)
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
     *         description="User or role not found",
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
    public function assignRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'role_id' => 'required|integer'
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $role = Role::find($request->role_id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        if ($user->hasRole($role)) {
            return response()->json([
                'success' => true,
                'message' => 'Role assigned to user successfully',
                'data' => [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'note' => 'Role was already assigned'
                ]
            ]);
        }

        $user->assignRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Role assigned to user successfully',
            'data' => [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]
        ]);
    }

    /**
     * Assign multiple roles to a user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users/assign-multiple-roles",
     *     summary="Assign multiple roles to user",
     *     description="Assigns multiple roles to a user in a single operation",
     *     tags={"RBAC - User Role Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="User ID"),
     *             @OA\Property(property="role_ids", type="array", @OA\Items(type="integer"), example={2, 3}, description="Array of role IDs to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Multiple roles assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Multiple roles assigned to user successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="assigned_roles", type="array", @OA\Items(type="string"), example={"manager", "editor"}),
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
     *         description="User or role not found",
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
    public function assignMultipleRoles(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'integer|exists:roles,id'
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $roles = Role::whereIn('id', $request->role_ids)->get();
        $assignedRoles = [];

        foreach ($roles as $role) {
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
            $assignedRoles[] = $role->name;
        }

        return response()->json([
            'success' => true,
            'message' => 'Multiple roles assigned to user successfully',
            'data' => [
                'user_id' => $user->id,
                'assigned_roles' => $assignedRoles,
                'total_assigned' => count($assignedRoles)
            ]
        ]);
    }

    /**
     * Remove a specific role from a user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users/remove-role",
     *     summary="Remove role from user",
     *     description="Removes a specific role from a user, with protection for admin roles",
     *     tags={"RBAC - User Role Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="User ID"),
     *             @OA\Property(property="role_id", type="integer", example=2, description="Role ID to remove")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role removed from user successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="role_name", type="string", example="manager")
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
     *         description="User or role not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot remove role",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot remove admin role from last admin user")
     *         )
     *     )
     * )
     */
    public function removeRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'role_id' => 'required|integer'
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $role = Role::find($request->role_id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        if ($role->name === 'admin' && $this->isLastAdminUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove admin role from last admin user'
            ], 422);
        }

        $user->removeRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Role removed from user successfully',
            'data' => [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]
        ]);
    }

    /**
     * Remove all roles from a user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users/remove-all-roles",
     *     summary="Remove all roles from user",
     *     description="Removes all roles from a user, with protection for admin users",
     *     tags={"RBAC - User Role Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="User ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All roles removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All roles removed from user successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="removed_roles", type="array", @OA\Items(type="string"), example={"admin", "manager"}),
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
     *         description="Cannot remove all roles",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot remove all roles from admin user")
     *         )
     *     )
     * )
     */
    public function removeAllRoles(Request $request): JsonResponse
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

        if ($user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove all roles from admin user'
            ], 422);
        }

        $removedRoles = $user->getRoleNames()->toArray();
        $user->syncRoles([]);

        return response()->json([
            'success' => true,
            'message' => 'All roles removed from user successfully',
            'data' => [
                'user_id' => $user->id,
                'removed_roles' => $removedRoles,
                'total_removed' => count($removedRoles)
            ]
        ]);
    }

    /**
     * Get user permissions overview
     *
     * @OA\Get(
     *     path="/api/v1/admin/users/{id}/permissions",
     *     summary="Get user permissions",
     *     description="Retrieves comprehensive overview of user permissions including role-based and direct permissions",
     *     tags={"RBAC - User Role Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User permissions retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="user_name", type="string", example="John Doe"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="role_name", type="string", example="admin"),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"manage_users", "view_roles"})
     *                 )),
     *                 @OA\Property(property="direct_permissions", type="array", @OA\Items(type="string"), example={"custom_permission"}),
     *                 @OA\Property(property="all_permissions", type="array", @OA\Items(type="string"), example={"manage_users", "view_roles", "custom_permission"})
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
     *     )
     * )
     */
    public function getUserPermissions(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $roles = $user->roles->map(function ($role) {
            return [
                'role_name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ];
        });

        $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
        $allPermissions = $user->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'success' => true,
            'message' => 'User permissions retrieved successfully',
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'roles' => $roles,
                'direct_permissions' => $directPermissions,
                'all_permissions' => $allPermissions
            ]
        ]);
    }

    /**
     * Check if user is the last admin user
     */
    private function isLastAdminUser(User $user): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }

        $adminUsersCount = User::role('admin')->count();
        return $adminUsersCount <= 1;
    }
}

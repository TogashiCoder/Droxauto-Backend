<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Administrative endpoints for user and role management"
 * )
 */
class UserController extends Controller
{
    /**
     * Display a listing of users
     *
     * @OA\Get(
     *     path="/api/v1/admin/users",
     *     summary="List all users",
     *     description="Retrieves a list of all users with their roles and permissions",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"user"}),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={}),
     *                     @OA\Property(property="is_admin", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
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
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve users",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve users"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::with('roles')->get();

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRolesArray(),
                        'permissions' => $user->getPermissionsArray(),
                        'is_admin' => $user->isAdmin(),
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created user
     *
     * @OA\Post(
     *     path="/api/v1/admin/users",
     *     summary="Create a new user",
     *     description="Creates a new user with optional role assignments",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Jane Doe", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="jane@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", minLength=8, example="password123", description="User's password"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"user", "moderator"}, description="Array of role names to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", example="jane@example.com"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"user", "moderator"}),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={}),
     *                 @OA\Property(property="is_admin", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
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
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden.")
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create user"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            } else {
                // Assign basic user role with minimal permissions
                $user->assignRole('basic_user');
            }

            $user->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRolesArray(),
                    'permissions' => $user->getPermissionsArray(),
                    'is_admin' => $user->isAdmin(),
                    'created_at' => $user->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user
     *
     * @OA\Get(
     *     path="/api/v1/admin/users/{id}",
     *     summary="Get a specific user",
     *     description="Retrieves detailed information about a specific user",
     *     tags={"Admin"},
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
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"user"}),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={}),
     *                 @OA\Property(property="is_admin", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve user"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show(string $user): JsonResponse
    {
        try {
            // Manually find the user by ID
            $userModel = User::find($user);

            if (!$userModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'user_not_found',
                    'debug' => [
                        'requested_id' => $user,
                        'user_found' => false
                    ]
                ], 404);
            }

            $userModel->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => [
                    'id' => $userModel->id,
                    'name' => $userModel->name,
                    'email' => $userModel->email,
                    'roles' => $userModel->getRolesArray(),
                    'permissions' => $userModel->getPermissionsArray(),
                    'is_admin' => $userModel->isAdmin(),
                    'created_at' => $userModel->created_at,
                    'updated_at' => $userModel->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified user
     *
     * @OA\Put(
     *     path="/api/v1/admin/users/{id}",
     *     summary="Update a user",
     *     description="Updates an existing user's information",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="John Smith", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="john.smith@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", minLength=8, example="newpassword123", description="User's new password (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Smith"),
     *                 @OA\Property(property="email", type="string", example="john.smith@example.com"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"user"}),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={}),
     *                 @OA\Property(property="is_admin", type="boolean", example=false),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             @OA\Property(property="error", type="string")
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update user"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $user): JsonResponse
    {
        // Manually find the user by ID
        $userModel = User::find($user);

        if (!$userModel) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => 'user_not_found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,' . $userModel->id,
            'password' => 'string|min:8|nullable',
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [];

            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }

            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            if (!empty($updateData)) {
                $userModel->update($updateData);
            }

            if ($request->has('roles')) {
                $userModel->syncRoles($request->roles);
            }

            $userModel->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $userModel->id,
                    'name' => $userModel->name,
                    'email' => $userModel->email,
                    'roles' => $userModel->getRolesArray(),
                    'permissions' => $userModel->getPermissionsArray(),
                    'is_admin' => $userModel->isAdmin(),
                    'updated_at' => $userModel->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user
     *
     * @OA\Delete(
     *     path="/api/v1/admin/users/{id}",
     *     summary="Delete a user",
     *     description="Deletes a user from the system",
     *     tags={"Admin"},
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
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully"),
     *             @OA\Property(property="data", type="object")
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
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete user"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, string $user): JsonResponse
    {
        try {
            // 1. Find the user to be deleted
            $userModel = User::find($user);

            if (!$userModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'user_not_found',
                    'error_type' => 'resource_not_found'
                ], 404);
            }

            $currentUser = $request->user();
            $userToDelete = $userModel;

            // 2. Self-deletion protection (CRITICAL)
            if ($userToDelete->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account',
                    'error' => 'self_deletion_not_allowed',
                    'error_type' => 'business_rule_violation',
                    'details' => 'Self-deletion would result in account lockout'
                ], 422);
            }

            // 3. Last admin protection (CRITICAL)
            if ($userToDelete->hasRole('admin')) {
                $adminCount = User::role('admin')->count();

                if ($adminCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete the last admin account',
                        'error' => 'last_admin_protected',
                        'error_type' => 'business_rule_violation',
                        'details' => 'System requires at least one admin account to function'
                    ], 403);
                }
            }

            // 4. Role-based deletion permissions (SECURITY)
            if ($userToDelete->hasRole('admin') && !$currentUser->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to delete admin account',
                    'error' => 'insufficient_permissions',
                    'error_type' => 'authorization_failed',
                    'details' => 'Only admin users can delete other admin accounts'
                ], 403);
            }

            // 5. Prevent deletion of system-critical users (BUSINESS LOGIC)
            if ($userToDelete->email === 'admin@example.com' && $userToDelete->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete primary system administrator',
                    'error' => 'primary_admin_protected',
                    'error_type' => 'business_rule_violation',
                    'details' => 'Primary admin account is protected from deletion'
                ], 403);
            }

            // 6. Audit logging before deletion (COMPLIANCE)
            \Illuminate\Support\Facades\Log::info('User deletion initiated', [
                'deleted_by' => [
                    'id' => $currentUser->id,
                    'email' => $currentUser->email,
                    'roles' => $currentUser->getRolesArray()
                ],
                'user_to_delete' => [
                    'id' => $userToDelete->id,
                    'email' => $userToDelete->email,
                    'roles' => $userToDelete->getRolesArray(),
                    'created_at' => $userToDelete->created_at,
                    'last_login' => $userToDelete->last_login_at ?? 'Never'
                ],
                'deletion_reason' => $request->input('reason', 'No reason provided'),
                'timestamp' => now()->toISOString()
            ]);

            // 7. Store user data for potential recovery (DATA PROTECTION)
            $deletedUserData = [
                'id' => $userToDelete->id,
                'name' => $userToDelete->name,
                'email' => $userToDelete->email,
                'roles' => $userToDelete->getRolesArray(),
                'permissions' => $userToDelete->getPermissionsArray(),
                'created_at' => $userToDelete->created_at,
                'deleted_at' => now(),
                'deleted_by' => $currentUser->id
            ];

            // 8. Perform the deletion
            $userToDelete->delete();

            // 9. Success response with comprehensive details
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
                'data' => [
                    'deleted_user' => [
                        'id' => $deletedUserData['id'],
                        'email' => $deletedUserData['email'],
                        'deleted_at' => $deletedUserData['deleted_at']->format('Y-m-d H:i:s')
                    ],
                    'deletion_details' => [
                        'deleted_by' => [
                            'id' => $currentUser->id,
                            'email' => $currentUser->email
                        ],
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                        'reason' => $request->input('reason', 'No reason provided')
                    ],
                    'system_status' => [
                        'remaining_admins' => User::role('admin')->count(),
                        'total_users' => User::count()
                    ]
                ]
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // Database-specific errors
            \Illuminate\Support\Facades\Log::error('Database error during user deletion', [
                'user_id' => $user ?? 'unknown',
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred during user deletion',
                'error' => 'database_error',
                'error_type' => 'system_error',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal database error'
            ], 500);
        } catch (\Exception $e) {
            // General errors
            \Illuminate\Support\Facades\Log::error('Unexpected error during user deletion', [
                'user_id' => $user ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during user deletion',
                'error' => 'unexpected_error',
                'error_type' => 'system_error',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all available roles
     *
     * @OA\Get(
     *     path="/api/v1/admin/roles",
     *     summary="List all roles",
     *     description="Retrieves a list of all available roles",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Roles retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="user"),
     *                     @OA\Property(property="guard_name", type="string", example="web"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
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
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve roles",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve roles"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function roles(): JsonResponse
    {
        try {
            $roles = Role::all();

            return response()->json([
                'success' => true,
                'message' => 'Roles retrieved successfully',
                'data' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user roles
     *
     * @OA\Put(
     *     path="/api/v1/admin/users/{user}/roles",
     *     summary="Update user roles",
     *     description="Updates the roles assigned to a specific user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"roles"},
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"user", "moderator"}, description="Array of role names to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User roles updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User roles updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"user", "moderator"}),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={}),
     *                 @OA\Property(property="is_admin", type="boolean", example=false),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             @OA\Property(property="error", type="string")
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update user roles",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update user roles"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateRoles(Request $request, string $user): JsonResponse
    {
        // Manually find the user by ID
        $userModel = User::find($user);

        if (!$userModel) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => 'user_not_found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userModel->syncRoles($request->roles);
            $userModel->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'User roles updated successfully',
                'data' => [
                    'id' => $userModel->id,
                    'name' => $userModel->name,
                    'email' => $userModel->email,
                    'roles' => $userModel->getRolesArray(),
                    'permissions' => $userModel->getPermissionsArray(),
                    'is_admin' => $userModel->isAdmin(),
                    'updated_at' => $userModel->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

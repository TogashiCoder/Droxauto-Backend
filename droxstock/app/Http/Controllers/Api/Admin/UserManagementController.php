<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * List all users with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['roles', 'permissions']);

            // Search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Role filter
            if ($request->has('role') && $request->role) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            $users = $query->paginate(15);

            // Transform users to include is_admin field
            $transformedUsers = $users->getCollection()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles,
                    'permissions' => $user->permissions,
                    'is_admin' => $user->hasRole('admin'),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'data' => $transformedUsers,
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total()
                ]
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
     * Get specific user details
     */
    public function show($userId): JsonResponse
    {
        try {
            // Manually find the user
            $user = User::find($userId);

            // Debug: Check if user exists and has data
            if (!$user || !$user->exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->load(['roles', 'permissions']);

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles,
                    'permissions' => $user->permissions,
                    'is_admin' => $user->hasRole('admin'),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
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
     * Create new user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        try {

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'registration_status' => 'approved',
                'is_active' => true,
                'registration_date' => now(),
                'approved_at' => now(),
            ]);

            // Assign roles if specified, otherwise assign basic_user role by default
            if (isset($validated['roles']) && !empty($validated['roles'])) {
                $user->assignRole($validated['roles']);
            } else {
                $basicRole = Role::where('name', 'basic_user')->first();
                if ($basicRole) {
                    $user->assignRole($basicRole);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles,
                    'permissions' => $user->permissions,
                    'is_admin' => $user->hasRole('admin'),
                    'created_at' => $user->created_at
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
     * Update user information
     */
    public function update(Request $request, $userId): JsonResponse
    {
        // Manually find the user
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'sometimes|string|min:8|regex:/^(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        ]);

        try {
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'updated_at' => $user->updated_at
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
     * Delete user
     */
    public function destroy($userId): JsonResponse
    {
        try {
            // Manually find the user
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Prevent admin from deleting themselves
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user roles
     */
    public function getUserRoles($userId): JsonResponse
    {
        try {
            // Manually find the user
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->load(['roles', 'permissions']);

            return response()->json([
                'success' => true,
                'message' => 'User roles retrieved successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->permissions->pluck('name')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user roles
     */
    public function updateUserRoles(Request $request, $userId): JsonResponse
    {
        // Manually find the user
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        try {
            // Remove all existing roles
            $user->syncRoles($validated['roles']);

            return response()->json([
                'success' => true,
                'message' => 'User roles updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'roles' => $user->fresh()->roles
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

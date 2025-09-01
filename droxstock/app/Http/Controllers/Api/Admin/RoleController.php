<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of roles with pagination and caching
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cacheKey = "roles_page_{$request->get('page', 1)}_per_page_{$request->get('per_page', 15)}";

            $roles = Cache::tags(['rbac', 'roles'])->remember($cacheKey, 3600, function () use ($request) {
                return $this->roleService->getPaginatedRoles(
                    $request->get('per_page', 15),
                    $request->get('search'),
                    $request->get('sort_by', 'name'),
                    $request->get('sort_direction', 'asc')
                );
            });

            Log::info('Roles retrieved successfully', [
                'user_id' => auth()->id(),
                'count' => $roles->count(),
                'total' => $roles->total()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Roles retrieved successfully',
                'data' => [
                    'roles' => RoleResource::collection($roles)->items(),
                    'pagination' => [
                        'current_page' => $roles->currentPage(),
                        'per_page' => $roles->perPage(),
                        'total' => $roles->total(),
                        'last_page' => $roles->lastPage()
                    ]
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve roles', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created role
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        try {
            $role = $this->roleService->createRole($request->validated());

            // Clear cache
            Cache::tags(['rbac', 'roles'])->flush();

            Log::info('Role created successfully', [
                'user_id' => auth()->id(),
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => [
                    'role' => new RoleResource($role)
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Failed to create role', [
                'user_id' => auth()->id(),
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified role
     */
    public function show(int $id): JsonResponse
    {
        try {
            $role = Role::with('permissions')->where('id', $id)->where('guard_name', 'api')->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], Response::HTTP_NOT_FOUND);
            }

            Log::info('Role retrieved successfully', [
                'user_id' => auth()->id(),
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role retrieved successfully',
                'data' => [
                    'role' => new RoleResource($role)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve role', [
                'user_id' => auth()->id(),
                'role_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified role
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        try {
            $role = Role::where('id', $id)->where('guard_name', 'api')->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if role name change is allowed
            $newRoleName = $request->validated()['name'] ?? null;
            if ($newRoleName && $newRoleName !== $role->name) {
                $validationError = \App\Services\RoleConfigService::validateRoleOperation('rename', $role->name, $newRoleName);
                if ($validationError) {
                    return response()->json([
                        'success' => false,
                        'message' => $validationError,
                        'error_type' => 'role_protection_violation'
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            // Prevent updating other aspects of system roles
            if (\App\Services\RoleConfigService::isSystemRole($role->name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify system roles'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $updatedRole = $this->roleService->updateRole($role, $request->validated());

            // Clear cache
            Cache::tags(['rbac', 'roles'])->flush();

            Log::info('Role updated successfully', [
                'user_id' => auth()->id(),
                'role_id' => $role->id,
                'role_name' => $role->name,
                'changes' => $request->validated()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => [
                    'role' => new RoleResource($updatedRole)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update role', [
                'user_id' => auth()->id(),
                'role_id' => $id,
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $role = Role::where('id', $id)->where('guard_name', 'api')->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Validate role deletion
            $validationError = \App\Services\RoleConfigService::validateRoleOperation('delete', $role->name);
            if ($validationError) {
                return response()->json([
                    'success' => false,
                    'message' => $validationError,
                    'error_type' => 'role_protection_violation'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check if role has assigned users
            if ($this->roleService->hasAssignedUsers($role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role with assigned users'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->roleService->deleteRole($role);

            // Clear cache
            Cache::tags(['rbac', 'roles'])->flush();

            Log::info('Role deleted successfully', [
                'user_id' => auth()->id(),
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete role', [
                'user_id' => auth()->id(),
                'role_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

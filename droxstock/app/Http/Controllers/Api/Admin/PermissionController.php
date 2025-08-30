<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Display a listing of permissions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getPaginatedPermissions(
                $request->get('per_page', 15),
                $request->get('search'),
                $request->get('sort_by', 'name'),
                $request->get('sort_direction', 'asc')
            );

            Log::info('Permissions retrieved successfully', [
                'count' => $permissions->count(),
                'total' => $permissions->total()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permissions retrieved successfully',
                'data' => [
                    'permissions' => PermissionResource::collection($permissions)->items(),
                    'pagination' => [
                        'current_page' => $permissions->currentPage(),
                        'last_page' => $permissions->lastPage(),
                        'per_page' => $permissions->perPage(),
                        'total' => $permissions->total(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created permission
     */
    public function store(CreatePermissionRequest $request): JsonResponse
    {
        try {
            $permission = $this->permissionService->createPermission($request->validated());

            // Flush cache
            Cache::tags(['rbac', 'permissions'])->flush();

            Log::info('Permission created successfully', [
                'permission_id' => $permission->id,
                'name' => $permission->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => [
                    'permission' => new PermissionResource($permission)
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create permission', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified permission
     */
    public function show(int $id): JsonResponse
    {
        try {
            $permission = Permission::where('id', $id)->where('guard_name', 'api')->first();
            
            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], Response::HTTP_NOT_FOUND);
            }

            Log::info('Permission retrieved successfully', [
                'permission_id' => $permission->id,
                'name' => $permission->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission retrieved successfully',
                'data' => [
                    'permission' => new PermissionResource($permission)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve permission', [
                'permission_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve permission',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $permission = Permission::where('id', $id)->where('guard_name', 'api')->first();
            
            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if it's a system permission
            if ($this->permissionService->isSystemPermission($permission->name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify system permissions'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
                'description' => 'nullable|string|max:1000'
            ]);

            $updatedPermission = $this->permissionService->updatePermission($permission, $validated);

            // Flush cache
            Cache::tags(['rbac', 'permissions'])->flush();

            Log::info('Permission updated successfully', [
                'permission_id' => $permission->id,
                'name' => $permission->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => [
                    'permission' => new PermissionResource($updatedPermission)
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Permission update validation failed', [
                'permission_id' => $id,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update permission', [
                'permission_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified permission
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $permission = Permission::where('id', $id)->where('guard_name', 'api')->first();
            
            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if it's a system permission
            if ($this->permissionService->isSystemPermission($permission->name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system permissions'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check if permission has assigned roles
            if ($this->permissionService->hasAssignedRoles($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission with assigned roles'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $deleted = $this->permissionService->deletePermission($permission);

            if ($deleted) {
                // Flush cache
                Cache::tags(['rbac', 'permissions'])->flush();

                Log::info('Permission deleted successfully', [
                    'permission_id' => $permission->id,
                    'name' => $permission->name
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Permission deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to delete permission', [
                'permission_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Clone a permission
     */
    public function clone(Request $request, int $id): JsonResponse
    {
        try {
            $permission = Permission::where('id', $id)->where('guard_name', 'api')->first();
            
            if (!$permission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name',
                'description' => 'nullable|string|max:1000'
            ]);

            $clonedPermission = $this->permissionService->clonePermission($permission, $validated['name'], $validated['description'] ?? null);

            // Flush cache
            Cache::tags(['rbac', 'permissions'])->flush();

            Log::info('Permission cloned successfully', [
                'original_permission_id' => $permission->id,
                'cloned_permission_id' => $clonedPermission->id,
                'name' => $clonedPermission->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission cloned successfully',
                'data' => [
                    'permission' => new PermissionResource($clonedPermission)
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Permission cloning validation failed', [
                'permission_id' => $id,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to clone permission', [
                'permission_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clone permission',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get permission statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->permissionService->getPermissionStatistics();

            Log::info('Permission statistics retrieved successfully');

            return response()->json([
                'success' => true,
                'message' => 'Permission statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve permission statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve permission statistics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}

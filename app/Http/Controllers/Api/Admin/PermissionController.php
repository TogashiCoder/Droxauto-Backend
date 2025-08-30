<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="RBAC - Permission Management",
 *     description="API Endpoints for managing system permissions and access control"
 * )
 */
class PermissionController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of permissions
     *
     * @OA\Get(
     *     path="/api/v1/admin/permissions",
     *     summary="List all permissions",
     *     description="Retrieves a paginated list of all permissions with their metadata and role assignments",
     *     tags={"RBAC - Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for filtering permissions",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/PermissionResource")
     *                 ),
     *                 @OA\Property(
     *                     property="pagination",
     *                     ref="#/components/schemas/PaginationMeta"
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
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getPaginatedPermissions($request);
            
            return response()->json([
                'success' => true,
                'message' => 'Permissions retrieved successfully',
                'data' => [
                    'permissions' => PermissionResource::collection($permissions),
                    'pagination' => [
                        'current_page' => $permissions->currentPage(),
                        'last_page' => $permissions->lastPage(),
                        'per_page' => $permissions->perPage(),
                        'total' => $permissions->total(),
                        'from' => $permissions->firstItem(),
                        'to' => $permissions->lastItem(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve permissions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created permission
     *
     * @OA\Post(
     *     path="/api/v1/admin/permissions",
     *     summary="Create a new permission",
     *     description="Creates a new permission with specified metadata and guard",
     *     tags={"RBAC - Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreatePermissionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="permission", ref="#/components/schemas/PermissionResource")
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
    public function store(CreatePermissionRequest $request): JsonResponse
    {
        try {
            $permission = $this->permissionService->createPermission($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => [
                    'permission' => new PermissionResource($permission)
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified permission
     *
     * @OA\Get(
     *     path="/api/v1/admin/permissions/{id}",
     *     summary="Get permission by ID",
     *     description="Retrieves a specific permission with its metadata and role assignments",
     *     tags={"RBAC - Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PermissionResource")
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
     *         description="Permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Permission not found")
     *         )
     *     )
     * )
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
            
            return response()->json([
                'success' => true,
                'message' => 'Permission retrieved successfully',
                'data' => new PermissionResource($permission)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified permission
     *
     * @OA\Put(
     *     path="/api/v1/admin/permissions/{id}",
     *     summary="Update permission",
     *     description="Updates an existing permission with new metadata",
     *     tags={"RBAC - Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="updated_permission"),
     *             @OA\Property(property="description", type="string", example="Updated permission description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PermissionResource")
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
     *         description="Permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Permission not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot update permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot update system permission")
     *         )
     *     )
     * )
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
            
            if ($this->permissionService->isSystemPermission($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update system permission'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $updatedPermission = $this->permissionService->updatePermission($permission, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => new PermissionResource($updatedPermission)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified permission
     *
     * @OA\Delete(
     *     path="/api/v1/admin/permissions/{id}",
     *     summary="Delete permission",
     *     description="Deletes a permission if it has no assigned roles and is not a system permission",
     *     tags={"RBAC - Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission deleted successfully")
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
     *         description="Permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Permission not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot delete permission assigned to roles")
     *         )
     *     )
     * )
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
            
            if ($this->permissionService->isSystemPermission($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system permission'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            if ($this->permissionService->hasAssignedRoles($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission assigned to roles'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $this->permissionService->deletePermission($permission);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permission statistics
     *
     * @OA\Get(
     *     path="/api/v1/admin/permissions/statistics",
     *     summary="Get permission statistics",
     *     description="Retrieves comprehensive statistics about permissions including counts, usage, and distribution",
     *     tags={"RBAC - Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Permission statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission statistics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_permissions", type="integer", example=25),
     *                 @OA\Property(property="system_permissions_count", type="integer", example=8),
     *                 @OA\Property(property="custom_permissions_count", type="integer", example=17),
     *                 @OA\Property(
     *                     property="permissions_by_guard",
     *                     type="object",
     *                     @OA\Property(property="api", type="integer", example=25),
     *                     @OA\Property(property="web", type="integer", example=0)
     *                 ),
     *                 @OA\Property(
     *                     property="most_used_permissions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="view_users"),
     *                         @OA\Property(property="usage_count", type="integer", example=5)
     *                     )
     *                 ),
     *                 @OA\Property(property="unused_permissions_count", type="integer", example=3)
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
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->permissionService->getPermissionStatistics();
            
            return response()->json([
                'success' => true,
                'message' => 'Permission statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve permission statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve permission statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clone an existing permission
     *
     * @OA\Post(
     *     path="/api/v1/admin/permissions/{id}/clone",
     *     summary="Clone permission",
     *     description="Creates a copy of an existing permission with a new name",
     *     tags={"RBAC - Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID to clone",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="new_name", type="string", example="cloned_permission"),
     *             @OA\Property(property="description", type="string", example="Cloned permission description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission cloned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission cloned successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="permission", ref="#/components/schemas/PermissionResource")
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
     *         description="Permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Permission not found")
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
            
            $request->validate([
                'new_name' => 'required|string|max:255|unique:permissions,name',
                'description' => 'nullable|string|max:500'
            ]);
            
            $clonedPermission = $this->permissionService->clonePermission($permission, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Permission cloned successfully',
                'data' => [
                    'permission' => new PermissionResource($clonedPermission)
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to clone permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

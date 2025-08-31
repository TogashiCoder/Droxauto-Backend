<?php

namespace App\Http\Controllers\Api\Daparto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Daparto\StoreDapartoRequest;
use App\Http\Requests\Daparto\UpdateDapartoRequest;
use App\Http\Requests\Daparto\UploadCsvRequest;
use App\Http\Resources\Daparto\DapartoCollection;
use App\Http\Resources\Daparto\DapartoResource;
use App\Models\Daparto;
use App\Services\DapartoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Daparto",
 *     description="API Endpoints for managing Daparto parts and inventory"
 * )
 */
class DapartoController extends Controller
{
    protected DapartoService $dapartoService;

    public function __construct(DapartoService $dapartoService)
    {
        $this->dapartoService = $dapartoService;
    }

    /**
     * Display a listing of dapartos
     *
     * @OA\Get(
     *     path="/api/v1/dapartos",
     *     summary="List all dapartos",
     *     description="Retrieves a paginated list of dapartos with optional filtering and sorting",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for filtering dapartos",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="brand",
     *         in="query",
     *         description="Filter by brand",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "price", "brand", "created_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dapartos retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dapartos retrieved successfully"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=75)
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
     *         response=500,
     *         description="Failed to retrieve dapartos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve dapartos"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'brand', 'min_price', 'max_price', 'sort_by', 'sort_order', 'per_page']);
            $dapartos = $this->dapartoService->getPaginatedDapartos($filters);

            return response()->json([
                'success' => true,
                'message' => 'Dapartos retrieved successfully',
                'data' => new DapartoCollection($dapartos)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dapartos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created daparto
     *
     * @OA\Post(
     *     path="/api/v1/dapartos",
     *     summary="Create a new daparto",
     *     description="Creates a new daparto part in the inventory",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreDapartoRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Daparto created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daparto created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/DapartoResource")
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
     *         description="Failed to create daparto",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create daparto"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(StoreDapartoRequest $request): JsonResponse
    {
        try {
            $daparto = $this->dapartoService->createDaparto($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Daparto created successfully',
                'data' => new DapartoResource($daparto)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create daparto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified daparto
     *
     * @OA\Get(
     *     path="/api/v1/dapartos/{id}",
     *     summary="Get a specific daparto",
     *     description="Retrieves detailed information about a specific daparto part",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Daparto ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daparto retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daparto retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/DapartoResource")
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
     *         response=404,
     *         description="Daparto not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Daparto not found"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="error_type", type="string", example="not_found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve daparto",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve daparto"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show(int $dapartoId): JsonResponse
    {
        try {
            $daparto = Daparto::find($dapartoId);

            if (!$daparto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daparto not found',
                    'error' => 'No part found with ID: ' . $dapartoId,
                    'error_type' => 'not_found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Daparto retrieved successfully',
                'data' => new DapartoResource($daparto)
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database operation failed',
                'error' => 'Failed to retrieve the part due to database error',
                'error_type' => 'database_error',
                'debug_info' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retrieve operation failed',
                'error' => 'An unexpected error occurred while retrieving the part',
                'error_type' => 'general_error'
            ], 500);
        }
    }

    /**
     * Update the specified daparto with comprehensive error handling
     *
     * @OA\Put(
     *     path="/api/v1/dapartos/{id}",
     *     summary="Update a daparto",
     *     description="Updates an existing daparto part with comprehensive error handling",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Daparto ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateDapartoRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daparto updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daparto updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/DapartoResource"),
     *             @OA\Property(property="changes", type="object"),
     *             @OA\Property(property="unchanged", type="boolean", example=false)
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
     *         response=404,
     *         description="Daparto not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Daparto not found"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="error_type", type="string", example="not_found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="error_type", type="string", example="validation_error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Database operation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Database operation failed"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="error_type", type="string", example="database_error")
     *         )
     *     )
     * )
     */
    public function update(UpdateDapartoRequest $request, int $dapartoId): JsonResponse
    {
        try {
            // Check if daparto exists
            $daparto = Daparto::find($dapartoId);

            if (!$daparto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daparto not found',
                    'error' => 'No part found with ID: ' . $dapartoId,
                    'error_type' => 'not_found'
                ], 404);
            }

            $result = $this->dapartoService->updateDaparto($daparto, $request->validated());

            // Check if no changes were made
            if ($result['unchanged']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'],
                    'changes' => $result['changes'],
                    'unchanged' => true
                ], 200);
            }

            // Return successful update with change details
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
                'changes' => $result['changes'],
                'unchanged' => false
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_type' => 'validation_error'
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database errors
            return response()->json([
                'success' => false,
                'message' => 'Database operation failed',
                'error' => 'Database error occurred while updating the record',
                'error_type' => 'database_error',
                'debug_info' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            // Handle business logic errors
            return response()->json([
                'success' => false,
                'message' => 'Update failed',
                'error' => $e->getMessage(),
                'error_type' => 'business_logic_error'
            ], 400);
        }
    }

    /**
     * Remove the specified daparto
     *
     * @OA\Delete(
     *     path="/api/v1/dapartos/{id}",
     *     summary="Delete a daparto",
     *     description="Soft deletes a daparto part from the inventory",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Daparto ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daparto deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daparto deleted successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/DapartoResource")
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
     *         response=404,
     *         description="Daparto not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Daparto not found"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="error_type", type="string", example="not_found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete daparto",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete daparto"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $dapartoId): JsonResponse
    {
        try {
            // Check if daparto exists
            $daparto = Daparto::find($dapartoId);

            if (!$daparto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daparto not found',
                    'error' => 'No part found with ID: ' . $dapartoId,
                    'error_type' => 'not_found'
                ], 404);
            }

            // Check if already deleted
            if ($daparto->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daparto already deleted',
                    'error' => 'This part has already been deleted',
                    'error_type' => 'already_deleted'
                ], 400);
            }

            $this->dapartoService->deleteDaparto($daparto);

            return response()->json([
                'success' => true,
                'message' => 'Daparto deleted successfully',
                'data' => [
                    'id' => $dapartoId,
                    'interne_artikelnummer' => $daparto->interne_artikelnummer
                ]
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database operation failed',
                'error' => 'Failed to delete the part due to database error',
                'error_type' => 'database_error',
                'debug_info' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete operation failed',
                'error' => 'An unexpected error occurred while deleting the part',
                'error_type' => 'general_error'
            ], 500);
        }
    }

    /**
     * Get daparto statistics
     *
     * @OA\Get(
     *     path="/api/v1/dapartos-stats",
     *     summary="Get daparto statistics",
     *     description="Retrieves comprehensive statistics about daparto inventory",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_count", type="integer", example=1000),
     *                 @OA\Property(property="total_value", type="number", format="float", example=50000.00),
     *                 @OA\Property(property="brands_count", type="integer", example=25),
     *                 @OA\Property(property="categories_count", type="integer", example=10)
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
     *         response=500,
     *         description="Failed to retrieve statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve statistics"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->dapartoService->getDapartoStatistics();

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a deleted daparto
     *
     * @OA\Post(
     *     path="/api/v1/dapartos/{id}/restore",
     *     summary="Restore a deleted daparto",
     *     description="Restores a soft-deleted daparto part back to the inventory",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Daparto ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daparto restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daparto restored successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/DapartoResource")
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
     *         response=404,
     *         description="Daparto not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Daparto not found"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="error_type", type="string", example="not_found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to restore daparto",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to restore daparto"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function restore(int $dapartoId): JsonResponse
    {
        try {
            // Check if daparto exists (including soft-deleted)
            $daparto = Daparto::withTrashed()->find($dapartoId);

            if (!$daparto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daparto not found',
                    'error' => 'No part found with ID: ' . $dapartoId,
                    'error_type' => 'not_found'
                ], 404);
            }

            // Check if not deleted
            if (!$daparto->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daparto not deleted',
                    'error' => 'This part is already active and does not need restoration',
                    'error_type' => 'not_deleted'
                ], 400);
            }

            $this->dapartoService->restoreDaparto($dapartoId);

            return response()->json([
                'success' => true,
                'message' => 'Daparto restored successfully',
                'data' => [
                    'id' => $dapartoId,
                    'interne_artikelnummer' => $daparto->interne_artikelnummer,
                    'restored_at' => now()
                ]
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database operation failed',
                'error' => 'Failed to restore the part due to database error',
                'error_type' => 'database_error',
                'debug_info' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restore operation failed',
                'error' => 'An unexpected error occurred while restoring the part',
                'error_type' => 'general_error'
            ], 500);
        }
    }

    /**
     * Get daparto by article number
     *
     * @OA\Get(
     *     path="/api/v1/dapartos-by-number/{interne_artikelnummer}",
     *     summary="Get daparto by article number",
     *     description="Retrieves a daparto part by its internal article number",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="interne_artikelnummer",
     *         in="path",
     *         description="Internal article number",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daparto retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daparto retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/DapartoResource")
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
     *         response=404,
     *         description="Daparto not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Daparto not found"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="error_type", type="string", example="not_found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve daparto",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve daparto"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getByNumber(string $interneArtikelnummer): JsonResponse
    {
        try {
            $daparto = $this->dapartoService->getDapartoByNumber($interneArtikelnummer);

            if (!$daparto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daparto not found',
                    'error' => 'No part found with interne artikelnummer: ' . $interneArtikelnummer
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Daparto retrieved successfully',
                'data' => new DapartoResource($daparto)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve daparto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload CSV file for bulk daparto import
     *
     * @OA\Post(
     *     path="/api/v1/dapartos-upload-csv",
     *     summary="Upload CSV for bulk import",
     *     description="Uploads a CSV file for bulk import of daparto parts",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="csv_file",
     *                     type="string",
     *                     format="binary",
     *                     description="CSV file to upload"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV upload initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="CSV upload initiated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="job_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="status", type="string", example="queued")
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
     *         description="Failed to upload CSV",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to upload CSV"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function uploadCsv(UploadCsvRequest $request): JsonResponse
    {
        try {
            $file = $request->file('csv_file');
            $fileSize = $file->getSize();
            $options = [
                'validation_mode' => $request->input('validation_mode', 'strict'),
                'update_existing' => $request->boolean('update_existing', true),
                'skip_duplicates' => $request->boolean('skip_duplicates', false),
                'batch_size' => $request->integer('batch_size', 1000),
                'rollback_on_error' => true,
                'email_notification' => $request->boolean('email_notification', true),
                'user_email' => $request->input('user_email'),
            ];

            // ALWAYS use background processing for CSV files
            // This ensures consistent user experience and prevents timeouts
            return $this->processCsvWithBackgroundJob($file, $options, $request->user()->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'CSV validation failed',
                'error' => 'The uploaded file does not meet the required format and validation criteria',
                'error_type' => 'validation_error',
                'validation_errors' => $e->errors(),
                'file_requirements' => [
                    'format' => 'CSV with semicolon (;) delimiter',
                    'max_size' => '50MB',
                    'required_columns' => ['interne Artikelnummer', 'Preis', 'Zustand'],
                    'encoding' => 'UTF-8 recommended'
                ]
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('CSV upload database error', [
                'file' => $request->file('csv_file')?->getClientOriginalName(),
                'error' => $e->getMessage(),
                'sql' => $e->getSql()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database operation failed during CSV processing',
                'error' => 'The CSV data could not be saved due to database constraints',
                'error_type' => 'database_error',
                'suggestion' => 'Please check your data format and try again. If the problem persists, contact support.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('CSV upload system error', [
                'file' => $request->file('csv_file')?->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'CSV processing failed due to system error',
                'error' => 'An unexpected error occurred while processing your CSV file',
                'error_type' => 'system_error',
                'suggestion' => 'Please try again later. If the problem persists, contact support with file details.'
            ], 500);
        }
    }

    /**
     * Process CSV file using background job (recommended approach)
     */
    private function processCsvWithBackgroundJob($file, array $options, $userId): JsonResponse
    {
        try {
            // Store file temporarily
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs('temp/csv', uniqid('csv_') . '_' . $fileName);

            // Generate job ID first
            $jobId = uniqid();

            // Dispatch background job with job ID
            $job = new \App\Jobs\ProcessCsvUploadJob($filePath, $fileName, $options, $userId, $jobId);
            dispatch($job);

            // Auto-start queue worker if not already running
            $this->startQueueWorkerIfNeeded();

            return response()->json([
                'success' => true,
                'message' => 'CSV file uploaded successfully and queued for background processing',
                'data' => [
                    'file_info' => [
                        'name' => $fileName,
                        'size' => $file->getSize(),
                        'uploaded_at' => now(),
                    ],
                    'processing_status' => 'queued',
                    'estimated_time' => 'Processing time depends on row count and complexity',
                    'job_id' => $jobId,
                    'email_notification' => $options['email_notification'] ? 'enabled' : 'disabled',
                    'recommendation' => 'You will receive an email notification when processing is complete'
                ],
                'summary' => [
                    'file_processed' => $fileName,
                    'processing_mode' => 'background_job',
                    'status' => 'Queued for processing',
                    'next_step' => 'Wait for email notification or check job status'
                ]
            ], 202); // Accepted for processing

        } catch (\Exception $e) {
            Log::error('Failed to queue large CSV file', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue file for processing',
                'error' => 'The file was uploaded but could not be queued for background processing',
                'error_type' => 'queue_error',
                'suggestion' => 'Please try again or contact support if the problem persists.'
            ], 500);
        }
    }

    /**
     * Process small CSV file immediately
     */
    private function processSmallFileImmediately($file, array $options): JsonResponse
    {
        try {
            // Initialize professional CSV service
            $csvService = new \App\Services\ProfessionalCsvService();

            // Process the CSV file with professional features
            $results = $csvService->processCsvFile($file, $options);

            // Determine response based on results
            if ($results['success']) {
                $message = $this->generateSuccessMessage($results);
                $statusCode = 200;
            } else {
                $message = $this->generateErrorMessage($results);
                $statusCode = $this->determineHttpStatusCode($results);
            }

            return response()->json([
                'success' => $results['success'],
                'message' => $message,
                'data' => $results,
                'summary' => [
                    'file_processed' => $results['file_info']['name'],
                    'total_rows' => $results['processing_stats']['total_rows'],
                    'success_rate' => $results['validation_summary']['data_quality_score'] . '%',
                    'processing_time' => $results['performance']['duration'] . 's',
                    'memory_usage' => $this->formatBytes($results['performance']['memory_peak']),
                ]
            ], $statusCode);
        } catch (\Exception $e) {
            Log::error('Immediate CSV processing failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'CSV processing failed',
                'error' => 'An error occurred while processing the CSV file immediately',
                'error_type' => 'processing_error',
                'suggestion' => 'Please try again or use background processing for large files.'
            ], 500);
        }
    }

    /**
     * Generate success message based on processing results
     */
    private function generateSuccessMessage(array $results): string
    {
        $stats = $results['processing_stats'];
        $quality = $results['validation_summary']['data_quality_score'];

        if ($quality >= 95) {
            return "CSV file processed successfully with excellent data quality ({$quality}%)";
        } elseif ($quality >= 80) {
            return "CSV file processed successfully with good data quality ({$quality}%)";
        } else {
            return "CSV file processed with some data quality issues ({$quality}%)";
        }
    }

    /**
     * Generate error message based on processing results
     */
    private function generateErrorMessage(array $results): string
    {
        $stats = $results['processing_stats'];
        $errorCount = count($stats['errors']);

        if ($errorCount === 0) {
            return "CSV processing completed with warnings";
        }

        return "CSV processing completed with {$errorCount} error(s) that need attention";
    }

    /**
     * Determine appropriate HTTP status code based on results
     */
    private function determineHttpStatusCode(array $results): int
    {
        $stats = $results['processing_stats'];
        $errorCount = count($stats['errors']);
        $totalRows = $stats['total_rows'];

        if ($errorCount === 0) {
            return 200; // Success
        }

        // If errors are less than 10% of total rows, return 207 (Multi-Status)
        if ($totalRows > 0 && ($errorCount / $totalRows) <= 0.1) {
            return 207; // Partial success
        }

        return 422; // Unprocessable Entity
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get CSV job processing status
     *
     * @OA\Get(
     *     path="/api/v1/csv-job-status/{jobId}",
     *     summary="Get CSV job status",
     *     description="Retrieves the status of a CSV processing job",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="jobId",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Job status retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="job_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="progress", type="integer", example=100),
     *                 @OA\Property(property="total_records", type="integer", example=1000),
     *                 @OA\Property(property="processed_records", type="integer", example=1000),
     *                 @OA\Property(property="failed_records", type="integer", example=0)
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
     *         response=404,
     *         description="Job not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Job not found"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve job status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve job status"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getCsvJobStatus(string $jobId): JsonResponse
    {
        try {
            // Get job results from cache
            $jobResults = \Illuminate\Support\Facades\Cache::get("csv_job_{$jobId}");

            if (!$jobResults) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or expired',
                    'error' => 'The job results are no longer available',
                    'error_type' => 'job_not_found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job status retrieved successfully',
                'data' => [
                    'job_id' => $jobId,
                    'status' => $jobResults['results']['success'] ? 'completed' : 'failed',
                    'results' => $jobResults['results'],
                    'completed_at' => $jobResults['completed_at'],
                    'file_name' => $jobResults['file_name']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all dapartos
     *
     * @OA\Delete(
     *     path="/api/v1/dapartos-delete-all",
     *     summary="Delete all dapartos",
     *     description="Deletes all daparto parts from the inventory (use with caution)",
     *     tags={"Daparto"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All dapartos deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All dapartos deleted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="deleted_count", type="integer", example=1000)
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
     *         response=500,
     *         description="Failed to delete dapartos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete dapartos"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteAll(Request $request): JsonResponse
    {
        try {
            // Check if user has permission to delete all data
            if (!$request->user()->hasPermissionTo('delete dapartos')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                    'error' => 'You do not have permission to delete all Daparto data',
                    'error_type' => 'insufficient_permissions'
                ], 403);
            }

            // Get count before deletion for confirmation
            $totalCount = Daparto::count();

            if ($totalCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data to delete',
                    'error' => 'The Daparto table is already empty',
                    'error_type' => 'no_data'
                ], 400);
            }

            // Delete all records
            Daparto::truncate();

            // Log the action
            Log::info('All Daparto data deleted', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'records_deleted' => $totalCount,
                'deleted_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All Daparto data deleted successfully',
                'data' => [
                    'records_deleted' => $totalCount,
                    'deleted_at' => now()->format('Y-m-d H:i:s'),
                    'deleted_by' => [
                        'user_id' => $request->user()->id,
                        'user_email' => $request->user()->email
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete all Daparto data', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete all Daparto data',
                'error' => $e->getMessage(),
                'error_type' => 'deletion_failed'
            ], 500);
        }
    }

    /**
     * Auto-start queue worker if not already running
     */
    private function startQueueWorkerIfNeeded(): void
    {
        try {
            // Check if queue worker is already running
            if (!$this->isQueueWorkerRunning()) {
                // Start queue worker using artisan command
                $this->startQueueWorker();

                Log::info('Queue worker started automatically for CSV processing');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to auto-start queue worker', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if queue worker is already running
     */
    private function isQueueWorkerRunning(): bool
    {
        try {
            // Check if there are any queue worker processes
            $output = shell_exec('ps aux | grep "queue:work" | grep -v grep');
            return !empty($output);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Start queue worker using artisan command
     */
    private function startQueueWorker(): void
    {
        try {
            // Use the artisan command to start the worker
            $command = 'php ' . base_path('artisan') . ' queue:start-worker --daemon';

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                $command = 'start /B ' . $command;
            } else {
                // Linux/Mac
                $command .= ' > /dev/null 2>&1 &';
            }

            shell_exec($command);

            // Wait a moment for worker to start
            sleep(2);
        } catch (\Exception $e) {
            Log::error('Failed to start queue worker', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

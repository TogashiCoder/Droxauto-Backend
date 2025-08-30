<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Droxstock API",
 *     description="Comprehensive API for managing Daparto parts inventory and user authentication",
 *     @OA\Contact(
 *         email="support@droxstock.com",
 *         name="Droxstock Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     title="Pagination Metadata",
 *     description="Standard pagination metadata structure",
 *     @OA\Property(property="current_page", type="integer", example=1, description="Current page number"),
 *     @OA\Property(property="last_page", type="integer", example=5, description="Last page number"),
 *     @OA\Property(property="per_page", type="integer", example=15, description="Items per page"),
 *     @OA\Property(property="total", type="integer", example=75, description="Total number of items"),
 *     @OA\Property(property="from", type="integer", example=1, description="First item number on current page"),
 *     @OA\Property(property="to", type="integer", example=15, description="Last item number on current page")
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

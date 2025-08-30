<?php

use App\Http\Controllers\Api\Daparto\DapartoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::prefix('v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']); // Public refresh endpoint
});

// Protected routes
Route::prefix('v1')->middleware('auth:api')->group(function () {

    // Authentication routes (protected)
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Daparto CRUD routes (protected)
    Route::apiResource('dapartos', DapartoController::class);

    // Additional Daparto routes (protected)
    Route::get('dapartos-stats', [DapartoController::class, 'stats']);
    Route::get('dapartos-by-number/{interne_artikelnummer}', [DapartoController::class, 'getByNumber']);
    Route::post('dapartos/{id}/restore', [DapartoController::class, 'restore']);

    // CSV processing status (protected)
    Route::get('csv-job-status/{jobId}', [DapartoController::class, 'getCsvJobStatus']);
    Route::post('dapartos-upload-csv', [DapartoController::class, 'uploadCsv']);

    // Data management (protected)
    Route::delete('dapartos-delete-all', [DapartoController::class, 'deleteAll']);

    // Admin routes (admin role required)
    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // Role management
        Route::apiResource('roles', RoleController::class);
        Route::get('roles/{role}/permissions', [RoleController::class, 'permissions']);

        // User management
        Route::apiResource('users', UserController::class);
        Route::get('users/{user}/roles', [UserController::class, 'roles']);
        Route::put('users/{user}/roles', [UserController::class, 'updateRoles']);
    });
});

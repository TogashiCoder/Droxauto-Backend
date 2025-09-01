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

// Public user registration routes (no authentication required)
Route::prefix('v1/register')->group(function () {
    Route::post('user', [\App\Http\Controllers\Api\Auth\UserRegistrationController::class, 'register']);
    Route::get('status', [\App\Http\Controllers\Api\Auth\UserRegistrationController::class, 'checkStatus']);
    Route::post('resend-verification', [\App\Http\Controllers\Api\Auth\UserRegistrationController::class, 'resendVerification']);
});

// Protected routes
Route::prefix('v1')->middleware(['auth:api', 'user.active'])->group(function () {

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

        // Pending users management
        Route::get('pending-users', [\App\Http\Controllers\Api\Admin\PendingUsersController::class, 'index']);
        Route::get('pending-users/{id}', [\App\Http\Controllers\Api\Admin\PendingUsersController::class, 'show']);
        Route::post('pending-users/{id}/approve', [\App\Http\Controllers\Api\Admin\PendingUsersController::class, 'approve']);
        Route::post('pending-users/{id}/reject', [\App\Http\Controllers\Api\Admin\PendingUsersController::class, 'reject']);
        Route::get('pending-users-statistics', [\App\Http\Controllers\Api\Admin\PendingUsersController::class, 'statistics']);

        // User management
        Route::get('users', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'index']);
        Route::get('users/{userId}', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'show']);
        Route::post('users', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'store']);
        Route::put('users/{userId}', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'update']);
        Route::delete('users/{userId}', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'destroy']);
        Route::get('users/{userId}/roles', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'getUserRoles']);
        Route::put('users/{userId}/roles', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'updateUserRoles']);
    });
});

// RBAC Management Routes
Route::prefix('v1/admin')->middleware(['auth:api', 'user.active', 'role:admin'])->group(function () {

    // Role Management
    Route::apiResource('roles', \App\Http\Controllers\Api\Admin\RoleController::class)->names([
        'index' => 'api.v1.admin.roles.index',
        'store' => 'api.v1.admin.roles.store',
        'show' => 'api.v1.admin.roles.show',
        'update' => 'api.v1.admin.roles.update',
        'destroy' => 'api.v1.admin.roles.destroy',
    ]);

    // Permission Management
    Route::get('permissions/statistics', [\App\Http\Controllers\Api\Admin\PermissionController::class, 'statistics']);
    Route::post('permissions/{id}/clone', [\App\Http\Controllers\Api\Admin\PermissionController::class, 'clone']);
    Route::apiResource('permissions', \App\Http\Controllers\Api\Admin\PermissionController::class)->names([
        'index' => 'api.v1.admin.permissions.index',
        'store' => 'api.v1.admin.permissions.store',
        'show' => 'api.v1.admin.permissions.show',
        'update' => 'api.v1.admin.permissions.update',
        'destroy' => 'api.v1.admin.permissions.destroy',
    ]);

    // User Role Assignment
    Route::post('users/assign-role', [\App\Http\Controllers\Api\Admin\UserRoleController::class, 'assignRole']);
    Route::post('users/assign-multiple-roles', [\App\Http\Controllers\Api\Admin\UserRoleController::class, 'assignMultipleRoles']);
    Route::post('users/remove-role', [\App\Http\Controllers\Api\Admin\UserRoleController::class, 'removeRole']);
    Route::post('users/remove-all-roles', [\App\Http\Controllers\Api\Admin\UserRoleController::class, 'removeAllRoles']);
    Route::get('users/{user}/permissions', [\App\Http\Controllers\Api\Admin\UserRoleController::class, 'getUserPermissions']);

    // User Permission Management
    Route::post('users/assign-permission', [\App\Http\Controllers\Api\Admin\UserPermissionController::class, 'assignPermission']);
    Route::post('users/assign-multiple-permissions', [\App\Http\Controllers\Api\Admin\UserPermissionController::class, 'assignMultiplePermissions']);
    Route::post('users/remove-permission', [\App\Http\Controllers\Api\Admin\UserPermissionController::class, 'removePermission']);
    Route::post('users/remove-all-permissions', [\App\Http\Controllers\Api\Admin\UserPermissionController::class, 'removeAllPermissions']);

    // Role Permission Management
    Route::post('roles/assign-permission', [\App\Http\Controllers\Api\Admin\RolePermissionController::class, 'assignPermission']);
    Route::post('roles/assign-multiple-permissions', [\App\Http\Controllers\Api\Admin\RolePermissionController::class, 'assignMultiplePermissions']);
    Route::post('roles/remove-permission', [\App\Http\Controllers\Api\Admin\RolePermissionController::class, 'removePermission']);
    Route::post('roles/remove-all-permissions', [\App\Http\Controllers\Api\Admin\RolePermissionController::class, 'removeAllPermissions']);
});

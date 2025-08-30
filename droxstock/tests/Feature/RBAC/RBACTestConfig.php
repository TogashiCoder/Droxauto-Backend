<?php

namespace Tests\Feature\RBAC;

/**
 * RBAC Test Configuration
 *
 * This file contains all configuration constants and settings
 * for the RBAC testing suite.
 */
class RBACTestConfig
{
    /**
     * API Endpoints
     */
    const API_ENDPOINTS = [
        // Role Management
        'ROLES_CREATE' => '/api/v1/admin/roles',
        'ROLES_LIST' => '/api/v1/admin/roles',
        'ROLES_SHOW' => '/api/v1/admin/roles/{id}',
        'ROLES_UPDATE' => '/api/v1/admin/roles/{id}',
        'ROLES_DELETE' => '/api/v1/admin/roles/{id}',

        // Permission Management
        'PERMISSIONS_CREATE' => '/api/v1/admin/permissions',
        'PERMISSIONS_LIST' => '/api/v1/admin/permissions',
        'PERMISSIONS_SHOW' => '/api/v1/admin/permissions/{id}',
        'PERMISSIONS_UPDATE' => '/api/v1/admin/permissions/{id}',
        'PERMISSIONS_DELETE' => '/api/v1/admin/permissions/{id}',

        // User Role Assignment
        'USERS_ASSIGN_ROLE' => '/api/v1/admin/users/assign-role',
        'USERS_ASSIGN_MULTIPLE_ROLES' => '/api/v1/admin/users/assign-multiple-roles',
        'USERS_REMOVE_ROLE' => '/api/v1/admin/users/remove-role',
        'USERS_REMOVE_ALL_ROLES' => '/api/v1/admin/users/remove-all-roles',
        'USERS_PERMISSIONS' => '/api/v1/admin/users/{id}/permissions',

        // User Permission Management
        'USERS_ASSIGN_PERMISSION' => '/api/v1/admin/users/assign-permission',
        'USERS_REMOVE_PERMISSION' => '/api/v1/admin/users/remove-permission',

        // Role Permission Management
        'ROLES_ASSIGN_PERMISSION' => '/api/v1/admin/roles/assign-permission',
        'ROLES_ASSIGN_MULTIPLE_PERMISSIONS' => '/api/v1/admin/roles/assign-multiple-permissions',
        'ROLES_REMOVE_PERMISSION' => '/api/v1/admin/roles/remove-permission',
        'ROLES_REMOVE_ALL_PERMISSIONS' => '/api/v1/admin/roles/remove-all-permissions',
    ];

    /**
     * HTTP Status Codes
     */
    const HTTP_STATUS = [
        'OK' => 200,
        'CREATED' => 201,
        'NO_CONTENT' => 204,
        'BAD_REQUEST' => 400,
        'UNAUTHORIZED' => 401,
        'FORBIDDEN' => 403,
        'NOT_FOUND' => 404,
        'UNPROCESSABLE_ENTITY' => 422,
        'TOO_MANY_REQUESTS' => 429,
        'INTERNAL_SERVER_ERROR' => 500
    ];

    /**
     * Response Messages
     */
    const RESPONSE_MESSAGES = [
        // Success Messages
        'ROLE_CREATED' => 'Role created successfully',
        'ROLE_UPDATED' => 'Role updated successfully',
        'ROLE_DELETED' => 'Role deleted successfully',
        'ROLES_RETRIEVED' => 'Roles retrieved successfully',
        'ROLE_RETRIEVED' => 'Role retrieved successfully',

        'PERMISSION_CREATED' => 'Permission created successfully',
        'PERMISSION_UPDATED' => 'Permission updated successfully',
        'PERMISSION_DELETED' => 'Permission deleted successfully',
        'PERMISSIONS_RETRIEVED' => 'Permissions retrieved successfully',
        'PERMISSION_RETRIEVED' => 'Permission retrieved successfully',

        'ROLE_ASSIGNED' => 'Role assigned successfully',
        'ROLES_ASSIGNED' => 'Roles assigned successfully',
        'ROLE_REMOVED' => 'Role removed successfully',
        'ALL_ROLES_REMOVED' => 'All roles removed successfully',

        'PERMISSION_ASSIGNED_TO_ROLE' => 'Permission assigned to role successfully',
        'PERMISSIONS_ASSIGNED_TO_ROLE' => 'Permissions assigned to role successfully',
        'PERMISSION_REMOVED_FROM_ROLE' => 'Permission removed from role successfully',
        'ALL_PERMISSIONS_REMOVED_FROM_ROLE' => 'All permissions removed from role successfully',

        'PERMISSION_ASSIGNED_TO_USER' => 'Permission assigned to user successfully',
        'PERMISSION_REMOVED_FROM_USER' => 'Permission removed from user successfully',
        'USER_PERMISSIONS_RETRIEVED' => 'User permissions retrieved successfully',

        // Error Messages
        'INSUFFICIENT_PERMISSIONS' => 'Insufficient permissions',
        'ROLE_NOT_FOUND' => 'Role not found',
        'PERMISSION_NOT_FOUND' => 'Permission not found',
        'USER_NOT_FOUND' => 'User not found',
        'CANNOT_DELETE_ROLE_WITH_USERS' => 'Cannot delete role with assigned users',
        'CANNOT_DELETE_SYSTEM_ROLES' => 'Cannot delete system roles',
        'CANNOT_DELETE_PERMISSION_ASSIGNED_TO_ROLES' => 'Cannot delete permission assigned to roles',
        'CANNOT_REMOVE_ADMIN_ROLE_FROM_ADMIN_USER' => 'Cannot remove admin role from admin user',
        'CANNOT_MODIFY_SYSTEM_ROLE_PERMISSIONS' => 'Cannot modify system role permissions',
        'VALIDATION_ERROR' => 'Validation failed',
        'DUPLICATE_ENTRY' => 'Duplicate entry',
        'INVALID_GUARD' => 'Invalid guard name',
        'ROLE_ALREADY_ASSIGNED' => 'Role already assigned to user',
        'PERMISSION_ALREADY_ASSIGNED' => 'Permission already assigned to role'
    ];

    /**
     * Validation Rules
     */
    const VALIDATION_RULES = [
        'ROLE' => [
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'required|string|in:api,web',
            'description' => 'nullable|string|max:1000'
        ],
        'PERMISSION' => [
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'required|string|in:api,web',
            'description' => 'nullable|string|max:1000'
        ],
        'ROLE_ASSIGNMENT' => [
            'user_id' => 'required|integer|exists:users,id',
            'role_name' => 'required|string|exists:roles,name'
        ],
        'PERMISSION_ASSIGNMENT' => [
            'role_id' => 'required|integer|exists:roles,id',
            'permission_name' => 'required|string|exists:permissions,name'
        ]
    ];

    /**
     * Test Data Configuration
     */
    const TEST_DATA = [
        'ROLES' => [
            'SYSTEM_ROLES' => ['admin', 'basic_user', 'manager'],
            'TEST_ROLES' => [
                'super_admin',
                'editor',
                'viewer',
                'moderator',
                'contributor',
                'guest',
                'test_role_1',
                'test_role_2'
            ],
            'INVALID_NAMES' => [
                '',
                null,
                str_repeat('a', 256),
                'role@#$%',
                '   ',
                '12345'
            ]
        ],
        'PERMISSIONS' => [
            'SYSTEM_PERMISSIONS' => [
                'view users',
                'create users',
                'edit users',
                'delete users',
                'view roles',
                'create roles',
                'edit roles',
                'delete roles',
                'view permissions',
                'create permissions',
                'edit permissions',
                'delete permissions',
                'view dapartos',
                'create dapartos',
                'edit dapartos',
                'delete dapartos',
                'upload csv',
                'view csv status',
                'access admin panel',
                'view system stats'
            ],
            'TEST_PERMISSIONS' => [
                'edit_articles',
                'moderate_content',
                'manage_billing',
                'view_financial_data',
                'access_api',
                'manage_api_keys'
            ],
            'INVALID_NAMES' => [
                '',
                null,
                str_repeat('a', 256),
                'permission@#$%',
                '   ',
                '12345'
            ]
        ],
        'USERS' => [
            'TEST_EMAILS' => [
                'superadmin@test.com',
                'admin@test.com',
                'manager@test.com',
                'editor@test.com',
                'moderator@test.com',
                'contributor@test.com',
                'viewer@test.com',
                'guest@test.com',
                'multirole@test.com',
                'norole@test.com'
            ]
        ]
    ];

    /**
     * Performance Thresholds
     */
    const PERFORMANCE_THRESHOLDS = [
        'ROLE_LISTING' => 1.0,      // 1 second for listing roles
        'PERMISSION_LISTING' => 1.0, // 1 second for listing permissions
        'PERMISSION_CHECK' => 0.5,   // 500ms for permission checks
        'ROLE_ASSIGNMENT' => 0.2,    // 200ms for role assignment
        'PERMISSION_ASSIGNMENT' => 0.2 // 200ms for permission assignment
    ];

    /**
     * Security Configuration
     */
    const SECURITY_CONFIG = [
        'PREVENT_PRIVILEGE_ESCALATION' => true,
        'PREVENT_SELF_ROLE_REMOVAL' => true,
        'PROTECT_SYSTEM_ROLES' => true,
        'AUDIT_ROLE_CHANGES' => true,
        'MAX_ROLES_PER_USER' => 10,
        'MAX_PERMISSIONS_PER_ROLE' => 50
    ];

    /**
     * Database Configuration
     */
    const DATABASE_CONFIG = [
        'GUARD_NAME' => 'api',
        'CASCADE_DELETE' => false,
        'SOFT_DELETES' => false,
        'AUDIT_TABLES' => false
    ];

    /**
     * Cache Configuration
     */
    const CACHE_CONFIG = [
        'ENABLE_CACHING' => true,
        'CACHE_TTL' => 3600, // 1 hour
        'CACHE_TAGS' => ['rbac', 'roles', 'permissions', 'users']
    ];

    /**
     * Rate Limiting Configuration
     */
    const RATE_LIMITING = [
        'ENABLE_RATE_LIMITING' => true,
        'MAX_ATTEMPTS' => 60,
        'DECAY_MINUTES' => 1,
        'ENDPOINTS' => [
            'ROLE_CREATION' => ['max' => 10, 'decay' => 1],
            'PERMISSION_CREATION' => ['max' => 10, 'decay' => 1],
            'ROLE_ASSIGNMENT' => ['max' => 30, 'decay' => 1],
            'PERMISSION_ASSIGNMENT' => ['max' => 30, 'decay' => 1]
        ]
    ];

    /**
     * Test Categories
     */
    const TEST_CATEGORIES = [
        'ROLE_MANAGEMENT' => 'Role Management',
        'PERMISSION_MANAGEMENT' => 'Permission Management',
        'USER_ROLE_ASSIGNMENT' => 'User Role Assignment',
        'ROLE_PERMISSION_MANAGEMENT' => 'Role Permission Management',
        'USER_PERMISSION_MANAGEMENT' => 'User Permission Management',
        'RBAC_VALIDATION_SECURITY' => 'RBAC Validation and Security',
        'RBAC_PERFORMANCE_SCALABILITY' => 'RBAC Performance and Scalability'
    ];

    /**
     * Test Priorities
     */
    const TEST_PRIORITIES = [
        'CRITICAL' => 1,
        'HIGH' => 2,
        'MEDIUM' => 3,
        'LOW' => 4
    ];

    /**
     * Get API endpoint with ID replacement
     */
    public static function getEndpoint(string $endpoint, array $replacements = []): string
    {
        $url = self::API_ENDPOINTS[$endpoint] ?? $endpoint;

        foreach ($replacements as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }

        return $url;
    }

    /**
     * Get validation rules for specific entity
     */
    public static function getValidationRules(string $entity): array
    {
        return self::VALIDATION_RULES[$entity] ?? [];
    }

    /**
     * Get test data for specific category
     */
    public static function getTestData(string $category): array
    {
        return self::TEST_DATA[$category] ?? [];
    }

    /**
     * Get performance threshold for specific operation
     */
    public static function getPerformanceThreshold(string $operation): float
    {
        return self::PERFORMANCE_THRESHOLDS[$operation] ?? 1.0;
    }

    /**
     * Check if security feature is enabled
     */
    public static function isSecurityFeatureEnabled(string $feature): bool
    {
        return self::SECURITY_CONFIG[$feature] ?? false;
    }

    /**
     * Get cache configuration
     */
    public static function getCacheConfig(string $key): mixed
    {
        return self::CACHE_CONFIG[$key] ?? null;
    }

    /**
     * Get rate limiting configuration
     */
    public static function getRateLimitingConfig(string $endpoint): array
    {
        return self::RATE_LIMITING['ENDPOINTS'][$endpoint] ?? [
            'max' => self::RATE_LIMITING['MAX_ATTEMPTS'],
            'decay' => self::RATE_LIMITING['DECAY_MINUTES']
        ];
    }
}

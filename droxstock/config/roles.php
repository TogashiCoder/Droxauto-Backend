<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Roles Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines the core roles used throughout the application.
    | These can be modified as needed, and the entire application will
    | dynamically adapt to use the new role names.
    |
    */

    'system_roles' => [
        'admin' => env('ROLE_ADMIN', 'admin'),
        'manager' => env('ROLE_MANAGER', 'manager'),
        'basic_user' => env('ROLE_BASIC_USER', 'basic_user'),
        'user' => env('ROLE_USER', 'user'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Categories
    |--------------------------------------------------------------------------
    |
    | Define permission categories for better organization
    |
    */
    'permission_categories' => [
        'user_management' => [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'approve user registrations',
            'view profile',
        ],
        'role_management' => [
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
        ],
        'permission_management' => [
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
        ],
        'daparto_management' => [
            'view dapartos',
            'create dapartos',
            'edit dapartos',
            'delete dapartos',
            'upload csv',
            'view csv status',
        ],
        'system_access' => [
            'access admin panel',
            'view system stats',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Hierarchy
    |--------------------------------------------------------------------------
    |
    | Define which roles inherit permissions from other roles
    |
    */
    'role_hierarchy' => [
        'admin' => ['manager', 'basic_user', 'user'], // Admin inherits from all
        'manager' => ['basic_user'], // Manager inherits from basic_user
        'basic_user' => [], // No inheritance
        'user' => [], // No inheritance
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Roles
    |--------------------------------------------------------------------------
    |
    | Roles that cannot be deleted to maintain system integrity
    |
    */
    'protected_roles' => [
        'admin', 'basic_user', 'user'
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Roles for Registration Types
    |--------------------------------------------------------------------------
    |
    | Define which role is assigned for different registration methods
    |
    */
    'default_roles' => [
        'self_registration' => 'user', // Self-registered users
        'admin_created' => 'basic_user', // Admin-created users
    ],
];

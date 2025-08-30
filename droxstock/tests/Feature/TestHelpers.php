<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Test Helper Functions for Authentication and Authorization Testing
 */
class TestHelpers
{
    /**
     * Create and seed roles and permissions for testing
     */
    public static function seedRolesAndPermissions(): void
    {
        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Role management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            // Permission management
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',

            // Daparto management
            'view dapartos',
            'create dapartos',
            'edit dapartos',
            'delete dapartos',
            'upload csv',
            'view csv status',

            // System access
            'access admin panel',
            'view system stats',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $basicUserRole = Role::create(['name' => 'basic_user', 'guard_name' => 'api']);
        $managerRole = Role::create(['name' => 'manager', 'guard_name' => 'api']);

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());

        $basicUserRole->givePermissionTo([
            'view dapartos',
            'view csv status',
        ]);

        $managerRole->givePermissionTo([
            'view dapartos',
            'create dapartos',
            'edit dapartos',
            'upload csv',
            'view csv status',
            'view users',
            'view system stats',
        ]);
    }

    /**
     * Create an admin user with proper role assignment
     */
    public static function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    /**
     * Create a basic user with proper role assignment
     */
    public static function createBasicUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('basic_user');
        return $user;
    }

    /**
     * Create a manager user with proper role assignment
     */
    public static function createManagerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('manager');
        return $user;
    }

    /**
     * Create a user without any roles (simulating self-registered user)
     */
    public static function createUnauthorizedUser(): User
    {
        return User::factory()->create();
    }

    /**
     * Assert that a user has specific permissions
     */
    public static function assertUserHasPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            expect($user->hasPermissionTo($permission))->toBeTrue();
        }
    }

    /**
     * Assert that a user does not have specific permissions
     */
    public static function assertUserDoesNotHavePermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            expect($user->hasPermissionTo($permission))->toBeFalse();
        }
    }

    /**
     * Assert that a user has specific roles
     */
    public static function assertUserHasRoles(User $user, array $roles): void
    {
        foreach ($roles as $role) {
            expect($user->hasRole($role))->toBeTrue();
        }
    }

    /**
     * Assert that a user does not have specific roles
     */
    public static function assertUserDoesNotHaveRoles(User $user, array $roles): void
    {
        foreach ($roles as $role) {
            expect($user->hasRole($role))->toBeFalse();
        }
    }

    /**
     * Generate valid user data for testing
     */
    public static function validUserData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ], $overrides);
    }

    /**
     * Generate valid admin user data for testing
     */
    public static function validAdminUserData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Admin User',
            'email' => 'admin@company.com',
            'password' => 'SecurePassword123!'
        ], $overrides);
    }

    /**
     * Generate valid login data for testing
     */
    public static function validLoginData(array $overrides = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!'
        ], $overrides);
    }
}

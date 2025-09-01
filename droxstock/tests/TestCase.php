<?php

namespace Tests;

use App\Models\User;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions for testing
        $this->seedRolesAndPermissions();
    }

    /**
     * Seed roles and permissions for testing
     */
    protected function seedRolesAndPermissions(): void
    {
        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view profile', // Permission to view own profile

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
            'view profile', // All users can view their own profile
        ]);

        $managerRole->givePermissionTo([
            'view dapartos',
            'create dapartos',
            'edit dapartos',
            'upload csv',
            'view csv status',
            'view users',
            'view system stats',
            'view profile', // All users can view their own profile
        ]);
    }

    /**
     * Create and authenticate an admin user
     */
    protected function createAdminUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user, 'api');
        return $user;
    }

    /**
     * Create and authenticate a basic user
     */
    protected function createBasicUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('basic_user');
        $this->actingAs($user, 'api');
        return $user;
    }

    /**
     * Create and authenticate a manager user
     */
    protected function createManagerUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('manager');
        $this->actingAs($user, 'api');
        return $user;
    }

    /**
     * Create and authenticate an admin user with Sanctum token
     */
    protected function createAdminUserWithToken(): array
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('TestToken');

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    /**
     * Create and authenticate a basic user with Sanctum token
     */
    protected function createBasicUserWithToken(): array
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('basic_user');
        $token = $user->createToken('TestToken');

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    /**
     * Create and authenticate a manager user with Sanctum token
     */
    protected function createManagerUserWithToken(): array
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('manager');
        $token = $user->createToken('TestToken');

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }



    /**
     * Create a user without any roles (simulating self-registered user)
     */
    protected function createUnauthorizedUser(): User
    {
        $user = User::factory()->create();
        // No roles assigned - simulating self-registered user
        return $user;
    }

    /**
     * Assert that a user has specific permissions
     */
    protected function assertUserHasPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->assertTrue($user->hasPermissionTo($permission));
        }
    }

    /**
     * Assert that a user does not have specific permissions
     */
    protected function assertUserDoesNotHavePermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->assertFalse($user->hasPermissionTo($permission));
        }
    }

    /**
     * Assert that a user has specific roles
     */
    protected function assertUserHasRoles(User $user, array $roles): void
    {
        foreach ($roles as $role) {
            $this->assertTrue($user->hasRole($role));
        }
    }

    /**
     * Assert that a user does not have specific roles
     */
    protected function assertUserDoesNotHaveRoles(User $user, array $roles): void
    {
        foreach ($roles as $role) {
            $this->assertFalse($user->hasRole($role));
        }
    }
}

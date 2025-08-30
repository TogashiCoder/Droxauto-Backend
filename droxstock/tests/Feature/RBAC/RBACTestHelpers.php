<?php

namespace Tests\Feature\RBAC;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\TestCase;

trait RBACTestHelpers
{
    /**
     * Create a test role with specified permissions
     */
    protected function createTestRole(string $name, array $permissions = []): Role
    {
        $role = Role::create(['name' => $name, 'guard_name' => 'api']);

        if (!empty($permissions)) {
            $role->givePermissionTo($permissions);
        }

        return $role;
    }

    /**
     * Create a test permission
     */
    protected function createTestPermission(string $name): Permission
    {
        return Permission::create(['name' => $name, 'guard_name' => 'api']);
    }

    /**
     * Create a user with specific roles
     */
    protected function createUserWithRoles(array $roleNames): User
    {
        $user = User::factory()->create();

        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        return $user;
    }

    /**
     * Create a user with specific permissions
     */
    protected function createUserWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create();

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $user->givePermissionTo($permission);
            }
        }

        return $user;
    }

    /**
     * Create a complex role hierarchy for testing
     */
    protected function createRoleHierarchy(): array
    {
        // Create hierarchical roles
        $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $manager = Role::create(['name' => 'manager', 'guard_name' => 'api']);
        $editor = Role::create(['name' => 'editor', 'guard_name' => 'api']);
        $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'api']);

        // Create permissions
        $allPermissions = Permission::create(['name' => 'all_permissions', 'guard_name' => 'api']);
        $adminPermissions = Permission::create(['name' => 'admin_permissions', 'guard_name' => 'api']);
        $managerPermissions = Permission::create(['name' => 'manager_permissions', 'guard_name' => 'api']);
        $editPermissions = Permission::create(['name' => 'edit_permissions', 'guard_name' => 'api']);
        $viewPermissions = Permission::create(['name' => 'view_permissions', 'guard_name' => 'api']);

        // Assign permissions to roles
        $superAdmin->givePermissionTo($allPermissions);
        $admin->givePermissionTo($adminPermissions);
        $manager->givePermissionTo([$managerPermissions, $editPermissions, $viewPermissions]);
        $editor->givePermissionTo([$editPermissions, $viewPermissions]);
        $viewer->givePermissionTo($viewPermissions);

        return [
            'super_admin' => $superAdmin,
            'admin' => $admin,
            'manager' => $manager,
            'editor' => $editor,
            'viewer' => $viewer,
            'permissions' => [
                'all' => $allPermissions,
                'admin' => $adminPermissions,
                'manager' => $managerPermissions,
                'edit' => $editPermissions,
                'view' => $viewPermissions
            ]
        ];
    }

    /**
     * Create a test user with complex role-permission structure
     */
    protected function createComplexUser(): User
    {
        $user = User::factory()->create();

        // Create roles and permissions
        $role1 = Role::create(['name' => 'role_1', 'guard_name' => 'api']);
        $role2 = Role::create(['name' => 'role_2', 'guard_name' => 'api']);

        $permission1 = Permission::create(['name' => 'permission_1', 'guard_name' => 'api']);
        $permission2 = Permission::create(['name' => 'permission_2', 'guard_name' => 'api']);
        $permission3 = Permission::create(['name' => 'permission_3', 'guard_name' => 'api']);

        // Assign permissions to roles
        $role1->givePermissionTo([$permission1, $permission2]);
        $role2->givePermissionTo([$permission2, $permission3]);

        // Assign roles to user
        $user->assignRole([$role1, $role2]);

        // Assign direct permission
        $user->givePermissionTo('direct_permission');

        return $user;
    }

    /**
     * Assert that a user has specific roles
     */
    protected function assertUserHasRoles(User $user, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $this->assertTrue($user->hasRole($roleName), "User should have role: {$roleName}");
        }
    }

    /**
     * Assert that a user does not have specific roles
     */
    protected function assertUserDoesNotHaveRoles(User $user, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $this->assertFalse($user->hasRole($roleName), "User should not have role: {$roleName}");
        }
    }

    /**
     * Assert that a user has specific permissions
     */
    protected function assertUserHasPermissions(User $user, array $permissionNames): void
    {
        foreach ($permissionNames as $permissionName) {
            $this->assertTrue($user->hasPermissionTo($permissionName), "User should have permission: {$permissionName}");
        }
    }

    /**
     * Assert that a user does not have specific permissions
     */
    protected function assertUserDoesNotHavePermissions(User $user, array $permissionNames): void
    {
        foreach ($permissionNames as $permissionName) {
            $this->assertFalse($user->hasPermissionTo($permissionName), "User should not have permission: {$permissionName}");
        }
    }

    /**
     * Assert that a role has specific permissions
     */
    protected function assertRoleHasPermissions(Role $role, array $permissionNames): void
    {
        foreach ($permissionNames as $permissionName) {
            $this->assertTrue($role->hasPermissionTo($permissionName), "Role should have permission: {$permissionName}");
        }
    }

    /**
     * Assert that a role does not have specific permissions
     */
    protected function assertRoleDoesNotHavePermissions(Role $role, array $permissionNames): void
    {
        foreach ($permissionNames as $permissionName) {
            $this->assertFalse($role->hasPermissionTo($permissionName), "Role should not have permission: {$permissionName}");
        }
    }

    /**
     * Create test data for role creation
     */
    protected function getValidRoleData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'test_role_' . uniqid(),
            'guard_name' => 'api',
            'description' => 'Test role description'
        ], $overrides);
    }

    /**
     * Create test data for permission creation
     */
    protected function getValidPermissionData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'test_permission_' . uniqid(),
            'guard_name' => 'api',
            'description' => 'Test permission description'
        ], $overrides);
    }

    /**
     * Create test data for role assignment
     */
    protected function getValidRoleAssignmentData(int $userId, string $roleName): array
    {
        return [
            'user_id' => $userId,
            'role_name' => $roleName
        ];
    }

    /**
     * Create test data for permission assignment
     */
    protected function getValidPermissionAssignmentData(int $roleId, string $permissionName): array
    {
        return [
            'role_id' => $roleId,
            'permission_name' => $permissionName
        ];
    }

    /**
     * Clean up test data
     */
    protected function cleanupTestData(): void
    {
        // Remove test roles (excluding system roles)
        Role::whereNotIn('name', ['admin', 'basic_user', 'manager'])
            ->where('guard_name', 'api')
            ->delete();

        // Remove test permissions (excluding system permissions)
        Permission::whereNotIn('name', [
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
        ])->where('guard_name', 'api')->delete();
    }

    /**
     * Verify database state for role
     */
    protected function assertRoleExists(string $roleName, string $guardName = 'api'): void
    {
        $this->assertDatabaseHas('roles', [
            'name' => $roleName,
            'guard_name' => $guardName
        ]);
    }

    /**
     * Verify database state for permission
     */
    protected function assertPermissionExists(string $permissionName, string $guardName = 'api'): void
    {
        $this->assertDatabaseHas('permissions', [
            'name' => $permissionName,
            'guard_name' => $guardName
        ]);
    }

    /**
     * Verify role-permission relationship exists
     */
    protected function assertRoleHasPermission(string $roleName, string $permissionName, string $guardName = 'api'): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', $guardName)->first();
        $permission = Permission::where('name', $permissionName)->where('guard_name', $guardName)->first();

        $this->assertNotNull($role, "Role {$roleName} should exist");
        $this->assertNotNull($permission, "Permission {$permissionName} should exist");
        $this->assertTrue($role->hasPermissionTo($permission), "Role {$roleName} should have permission {$permissionName}");
    }

    /**
     * Verify user-role relationship exists
     */
    protected function assertUserHasRole(int $userId, string $roleName, string $guardName = 'api'): void
    {
        $user = User::find($userId);
        $this->assertNotNull($user, "User with ID {$userId} should exist");
        $this->assertTrue($user->hasRole($roleName), "User should have role {$roleName}");
    }

    /**
     * Verify user-permission relationship exists
     */
    protected function assertUserHasPermission(int $userId, string $permissionName, string $guardName = 'api'): void
    {
        $user = User::find($userId);
        $this->assertNotNull($user, "User with ID {$userId} should exist");
        $this->assertTrue($user->hasPermissionTo($permissionName), "User should have permission {$permissionName}");
    }
}

<?php

namespace Tests\Feature\RBAC;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class RBACTestDataFactory
{
    /**
     * Create a standard set of test roles
     */
    public static function createStandardRoles(): array
    {
        $roles = [
            'super_admin' => Role::create(['name' => 'super_admin', 'guard_name' => 'api']),
            'admin' => Role::create(['name' => 'admin', 'guard_name' => 'api']),
            'manager' => Role::create(['name' => 'manager', 'guard_name' => 'api']),
            'editor' => Role::create(['name' => 'editor', 'guard_name' => 'api']),
            'viewer' => Role::create(['name' => 'viewer', 'guard_name' => 'api']),
            'moderator' => Role::create(['name' => 'moderator', 'guard_name' => 'api']),
            'contributor' => Role::create(['name' => 'contributor', 'guard_name' => 'api']),
            'guest' => Role::create(['name' => 'guest', 'guard_name' => 'api'])
        ];

        return $roles;
    }

    /**
     * Create a standard set of test permissions
     */
    public static function createStandardPermissions(): array
    {
        $permissions = [
            // User management
            'view_users' => Permission::create(['name' => 'view_users', 'guard_name' => 'api']),
            'create_users' => Permission::create(['name' => 'create_users', 'guard_name' => 'api']),
            'edit_users' => Permission::create(['name' => 'edit_users', 'guard_name' => 'api']),
            'delete_users' => Permission::create(['name' => 'delete_users', 'guard_name' => 'api']),
            'ban_users' => Permission::create(['name' => 'ban_users', 'guard_name' => 'api']),

            // Role management
            'view_roles' => Permission::create(['name' => 'view_roles', 'guard_name' => 'api']),
            'create_roles' => Permission::create(['name' => 'create_roles', 'guard_name' => 'api']),
            'edit_roles' => Permission::create(['name' => 'edit_roles', 'guard_name' => 'api']),
            'delete_roles' => Permission::create(['name' => 'delete_roles', 'guard_name' => 'api']),
            'assign_roles' => Permission::create(['name' => 'assign_roles', 'guard_name' => 'api']),

            // Permission management
            'view_permissions' => Permission::create(['name' => 'view_permissions', 'guard_name' => 'api']),
            'create_permissions' => Permission::create(['name' => 'create_permissions', 'guard_name' => 'api']),
            'edit_permissions' => Permission::create(['name' => 'edit_permissions', 'guard_name' => 'api']),
            'delete_permissions' => Permission::create(['name' => 'delete_permissions', 'guard_name' => 'api']),
            'assign_permissions' => Permission::create(['name' => 'assign_permissions', 'guard_name' => 'api']),

            // Content management
            'view_content' => Permission::create(['name' => 'view_content', 'guard_name' => 'api']),
            'create_content' => Permission::create(['name' => 'create_content', 'guard_name' => 'api']),
            'edit_content' => Permission::create(['name' => 'edit_content', 'guard_name' => 'api']),
            'delete_content' => Permission::create(['name' => 'delete_content', 'guard_name' => 'api']),
            'publish_content' => Permission::create(['name' => 'publish_content', 'guard_name' => 'api']),
            'moderate_content' => Permission::create(['name' => 'moderate_content', 'guard_name' => 'api']),

            // System management
            'view_system_stats' => Permission::create(['name' => 'view_system_stats', 'guard_name' => 'api']),
            'manage_system_settings' => Permission::create(['name' => 'manage_system_settings', 'guard_name' => 'api']),
            'view_audit_logs' => Permission::create(['name' => 'view_audit_logs', 'guard_name' => 'api']),
            'export_data' => Permission::create(['name' => 'export_data', 'guard_name' => 'api']),
            'import_data' => Permission::create(['name' => 'import_data', 'guard_name' => 'api']),

            // Financial permissions
            'view_financial_data' => Permission::create(['name' => 'view_financial_data', 'guard_name' => 'api']),
            'manage_billing' => Permission::create(['name' => 'manage_billing', 'guard_name' => 'api']),
            'process_refunds' => Permission::create(['name' => 'process_refunds', 'guard_name' => 'api']),

            // API permissions
            'access_api' => Permission::create(['name' => 'access_api', 'guard_name' => 'api']),
            'manage_api_keys' => Permission::create(['name' => 'manage_api_keys', 'guard_name' => 'api']),
            'view_api_usage' => Permission::create(['name' => 'view_api_usage', 'guard_name' => 'api'])
        ];

        return $permissions;
    }

    /**
     * Create a hierarchical role structure with permissions
     */
    public static function createHierarchicalRoleStructure(): array
    {
        $roles = self::createStandardRoles();
        $permissions = self::createStandardPermissions();

        // Super Admin - All permissions
        $roles['super_admin']->givePermissionTo($permissions);

        // Admin - Most permissions except super admin specific
        $adminPermissions = array_filter($permissions, function ($key) {
            return !in_array($key, ['manage_system_settings', 'view_audit_logs']);
        }, ARRAY_FILTER_USE_KEY);
        $roles['admin']->givePermissionTo($adminPermissions);

        // Manager - Content and user management
        $managerPermissions = [
            $permissions['view_users'],
            $permissions['edit_users'],
            $permissions['view_content'],
            $permissions['create_content'],
            $permissions['edit_content'],
            $permissions['delete_content'],
            $permissions['publish_content'],
            $permissions['moderate_content'],
            $permissions['view_system_stats']
        ];
        $roles['manager']->givePermissionTo($managerPermissions);

        // Editor - Content management
        $editorPermissions = [
            $permissions['view_content'],
            $permissions['create_content'],
            $permissions['edit_content'],
            $permissions['publish_content']
        ];
        $roles['editor']->givePermissionTo($editorPermissions);

        // Moderator - Content moderation
        $moderatorPermissions = [
            $permissions['view_content'],
            $permissions['moderate_content'],
            $permissions['ban_users']
        ];
        $roles['moderator']->givePermissionTo($moderatorPermissions);

        // Contributor - Limited content creation
        $contributorPermissions = [
            $permissions['view_content'],
            $permissions['create_content']
        ];
        $roles['contributor']->givePermissionTo($contributorPermissions);

        // Viewer - Read-only access
        $viewerPermissions = [
            $permissions['view_content'],
            $permissions['view_users']
        ];
        $roles['viewer']->givePermissionTo($viewerPermissions);

        // Guest - Minimal access
        $guestPermissions = [
            $permissions['view_content']
        ];
        $roles['guest']->givePermissionTo($guestPermissions);

        return [
            'roles' => $roles,
            'permissions' => $permissions
        ];
    }

    /**
     * Create test users with different role combinations
     */
    public static function createTestUsersWithRoles(): array
    {
        $roleStructure = self::createHierarchicalRoleStructure();
        $roles = $roleStructure['roles'];

        $users = [
            'super_admin_user' => User::factory()->create([
                'name' => 'Super Admin User',
                'email' => 'superadmin@test.com'
            ]),
            'admin_user' => User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@test.com'
            ]),
            'manager_user' => User::factory()->create([
                'name' => 'Manager User',
                'email' => 'manager@test.com'
            ]),
            'editor_user' => User::factory()->create([
                'name' => 'Editor User',
                'email' => 'editor@test.com'
            ]),
            'moderator_user' => User::factory()->create([
                'name' => 'Moderator User',
                'email' => 'moderator@test.com'
            ]),
            'contributor_user' => User::factory()->create([
                'name' => 'Contributor User',
                'email' => 'contributor@test.com'
            ]),
            'viewer_user' => User::factory()->create([
                'name' => 'Viewer User',
                'email' => 'viewer@test.com'
            ]),
            'guest_user' => User::factory()->create([
                'name' => 'Guest User',
                'email' => 'guest@test.com'
            ]),
            'multi_role_user' => User::factory()->create([
                'name' => 'Multi Role User',
                'email' => 'multirole@test.com'
            ]),
            'no_role_user' => User::factory()->create([
                'name' => 'No Role User',
                'email' => 'norole@test.com'
            ])
        ];

        // Assign roles to users
        $users['super_admin_user']->assignRole($roles['super_admin']);
        $users['admin_user']->assignRole($roles['admin']);
        $users['manager_user']->assignRole($roles['manager']);
        $users['editor_user']->assignRole($roles['editor']);
        $users['moderator_user']->assignRole($roles['moderator']);
        $users['contributor_user']->assignRole($roles['contributor']);
        $users['viewer_user']->assignRole($roles['viewer']);
        $users['guest_user']->assignRole($roles['guest']);

        // Multi-role user
        $users['multi_role_user']->assignRole([
            $roles['editor'],
            $roles['moderator']
        ]);

        // No role user - already created without roles

        return [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $roleStructure['permissions']
        ];
    }

    /**
     * Create complex permission scenarios for testing
     */
    public static function createComplexPermissionScenarios(): array
    {
        $scenarios = [];

        // Scenario 1: User with direct permissions that override role permissions
        $user1 = User::factory()->create(['name' => 'Direct Permission User']);
        $role1 = Role::create(['name' => 'restricted_role', 'guard_name' => 'api']);
        $role1->givePermissionTo(['view_content', 'create_content']);

        $user1->assignRole($role1);
        $user1->givePermissionTo(['delete_content']); // Direct permission

        $scenarios['direct_override'] = [
            'user' => $user1,
            'role' => $role1,
            'description' => 'User with role permissions plus direct permissions'
        ];

        // Scenario 2: User with multiple roles and conflicting permissions
        $user2 = User::factory()->create(['name' => 'Multi Role User']);
        $role2a = Role::create(['name' => 'content_creator', 'guard_name' => 'api']);
        $role2b = Role::create(['name' => 'content_moderator', 'guard_name' => 'api']);

        $role2a->givePermissionTo(['create_content', 'edit_content']);
        $role2b->givePermissionTo(['moderate_content', 'delete_content']);

        $user2->assignRole([$role2a, $role2b]);

        $scenarios['multi_role_conflict'] = [
            'user' => $user2,
            'roles' => [$role2a, $role2b],
            'description' => 'User with multiple roles and different permission sets'
        ];

        // Scenario 3: Role hierarchy with inheritance
        $user3 = User::factory()->create(['name' => 'Hierarchy User']);
        $seniorRole = Role::create(['name' => 'senior_editor', 'guard_name' => 'api']);
        $juniorRole = Role::create(['name' => 'junior_editor', 'guard_name' => 'api']);

        $seniorRole->givePermissionTo(['create_content', 'edit_content', 'delete_content', 'publish_content']);
        $juniorRole->givePermissionTo(['create_content', 'edit_content']);

        $user3->assignRole($seniorRole);

        $scenarios['role_hierarchy'] = [
            'user' => $user3,
            'senior_role' => $seniorRole,
            'junior_role' => $juniorRole,
            'description' => 'User with senior role that includes junior role permissions'
        ];

        // Scenario 4: User with temporary permissions
        $user4 = User::factory()->create(['name' => 'Temporary Permission User']);
        $role4 = Role::create(['name' => 'basic_user', 'guard_name' => 'api']);
        $role4->givePermissionTo(['view_content']);

        $user4->assignRole($role4);
        $user4->givePermissionTo(['create_content']); // Temporary direct permission

        $scenarios['temporary_permissions'] = [
            'user' => $user4,
            'role' => $role4,
            'description' => 'User with basic role plus temporary direct permissions'
        ];

        return $scenarios;
    }

    /**
     * Create test data for validation testing
     */
    public static function getInvalidRoleData(): array
    {
        return [
            'empty_name' => ['name' => '', 'guard_name' => 'api'],
            'null_name' => ['name' => null, 'guard_name' => 'api'],
            'invalid_guard' => ['name' => 'test_role', 'guard_name' => 'invalid_guard'],
            'empty_guard' => ['name' => 'test_role', 'guard_name' => ''],
            'very_long_name' => ['name' => str_repeat('a', 256), 'guard_name' => 'api'],
            'special_chars_name' => ['name' => 'role@#$%', 'guard_name' => 'api'],
            'spaces_only_name' => ['name' => '   ', 'guard_name' => 'api'],
            'numeric_name' => ['name' => '12345', 'guard_name' => 'api']
        ];
    }

    /**
     * Create test data for permission validation
     */
    public static function getInvalidPermissionData(): array
    {
        return [
            'empty_name' => ['name' => '', 'guard_name' => 'api'],
            'null_name' => ['name' => null, 'guard_name' => 'api'],
            'invalid_guard' => ['name' => 'test_permission', 'guard_name' => 'invalid_guard'],
            'empty_guard' => ['name' => 'test_permission', 'guard_name' => ''],
            'very_long_name' => ['name' => str_repeat('a', 256), 'guard_name' => 'api'],
            'special_chars_name' => ['name' => 'permission@#$%', 'guard_name' => 'api'],
            'spaces_only_name' => ['name' => '   ', 'guard_name' => 'api'],
            'numeric_name' => ['name' => '12345', 'guard_name' => 'api']
        ];
    }

    /**
     * Create test data for role assignment validation
     */
    public static function getInvalidRoleAssignmentData(): array
    {
        return [
            'invalid_user_id' => ['user_id' => '99999999-9999-9999-9999-999999999999', 'role_name' => 'admin'],
            'invalid_uuid_format' => ['user_id' => 'invalid-uuid-format', 'role_name' => 'admin'],
            'empty_user_id' => ['user_id' => '', 'role_name' => 'admin'],
            'null_user_id' => ['user_id' => null, 'role_name' => 'admin'],
            'empty_role_name' => ['user_id' => '550e8400-e29b-41d4-a716-446655440000', 'role_name' => ''],
            'null_role_name' => ['user_id' => '550e8400-e29b-41d4-a716-446655440000', 'role_name' => null],
            'non_existent_role' => ['user_id' => '550e8400-e29b-41d4-a716-446655440000', 'role_name' => 'non_existent_role'],
            'missing_user_id' => ['role_name' => 'admin'],
            'missing_role_name' => ['user_id' => '550e8400-e29b-41d4-a716-446655440000']
        ];
    }

    /**
     * Create test data for permission assignment validation
     */
    public static function getInvalidPermissionAssignmentData(): array
    {
        return [
            'invalid_role_id' => ['role_id' => 99999, 'permission_name' => 'view_users'],
            'negative_role_id' => ['role_id' => -1, 'permission_name' => 'view_users'],
            'zero_role_id' => ['role_id' => 0, 'permission_name' => 'view_users'],
            'string_role_id' => ['role_id' => 'invalid', 'permission_name' => 'view_users'],
            'empty_permission_name' => ['role_id' => 1, 'permission_name' => ''],
            'null_permission_name' => ['role_id' => 1, 'permission_name' => null],
            'non_existent_permission' => ['role_id' => 1, 'permission_name' => 'non_existent_permission'],
            'missing_role_id' => ['permission_name' => 'view_users'],
            'missing_permission_name' => ['role_id' => 1]
        ];
    }

    /**
     * Clean up all test data
     */
    public static function cleanupAllTestData(): void
    {
        // Remove test users
        User::whereIn('email', [
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
        ])->delete();

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
}

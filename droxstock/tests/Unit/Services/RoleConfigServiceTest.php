<?php

namespace Tests\Unit\Services;

use App\Services\RoleConfigService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Unit Tests for RoleConfigService
 *
 * Tests the core logic of role configuration and protection
 * without database dependencies.
 */
class RoleConfigServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('roles.system_roles', [
            'admin' => 'admin',
            'manager' => 'manager',
            'basic_user' => 'basic_user',
            'user' => 'user',
        ]);

        Config::set('roles.protected_roles', [
            'admin', 'basic_user', 'user'
        ]);
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_get_role_returns_correct_role_name(): void
    {
        $this->assertEquals('admin', RoleConfigService::getRole('admin'));
        $this->assertEquals('manager', RoleConfigService::getRole('manager'));
        $this->assertEquals('basic_user', RoleConfigService::getRole('basic_user'));
        $this->assertEquals('user', RoleConfigService::getRole('user'));
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_get_role_returns_fallback_for_unknown_key(): void
    {
        $this->assertEquals('unknown_role', RoleConfigService::getRole('unknown_role'));
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_specific_role_getters(): void
    {
        $this->assertEquals('admin', RoleConfigService::getAdminRole());
        $this->assertEquals('manager', RoleConfigService::getManagerRole());
        $this->assertEquals('basic_user', RoleConfigService::getBasicUserRole());
        $this->assertEquals('user', RoleConfigService::getUserRole());
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_get_all_roles(): void
    {
        $expected = [
            'admin' => 'admin',
            'manager' => 'manager',
            'basic_user' => 'basic_user',
            'user' => 'user',
        ];

        $this->assertEquals($expected, RoleConfigService::getAllRoles());
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_get_system_role_names(): void
    {
        $expected = ['admin', 'manager', 'basic_user', 'user'];
        $actual = RoleConfigService::getSystemRoleNames();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_is_system_role(): void
    {
        // System roles
        $this->assertTrue(RoleConfigService::isSystemRole('admin'));
        $this->assertTrue(RoleConfigService::isSystemRole('manager'));
        $this->assertTrue(RoleConfigService::isSystemRole('basic_user'));
        $this->assertTrue(RoleConfigService::isSystemRole('user'));

        // Non-system roles
        $this->assertFalse(RoleConfigService::isSystemRole('custom_role'));
        $this->assertFalse(RoleConfigService::isSystemRole('editor'));
        $this->assertFalse(RoleConfigService::isSystemRole(''));
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_is_protected_role(): void
    {
        // Protected roles (from config)
        $this->assertTrue(RoleConfigService::isProtectedRole('admin'));
        $this->assertTrue(RoleConfigService::isProtectedRole('basic_user'));
        $this->assertTrue(RoleConfigService::isProtectedRole('user'));

        // System role not in protected list (should still be protected)
        $this->assertTrue(RoleConfigService::isProtectedRole('manager'));

        // Non-protected roles
        $this->assertFalse(RoleConfigService::isProtectedRole('custom_role'));
        $this->assertFalse(RoleConfigService::isProtectedRole('editor'));
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_can_rename_role(): void
    {
        // Cannot rename system roles
        $this->assertFalse(RoleConfigService::canRenameRole('admin', 'super_admin'));
        $this->assertFalse(RoleConfigService::canRenameRole('manager', 'team_lead'));

        // Cannot rename to system role names
        $this->assertFalse(RoleConfigService::canRenameRole('custom_role', 'admin'));
        $this->assertFalse(RoleConfigService::canRenameRole('editor', 'manager'));

        // Can rename custom roles to custom names
        $this->assertTrue(RoleConfigService::canRenameRole('custom_role', 'another_custom'));
        $this->assertTrue(RoleConfigService::canRenameRole('editor', 'content_editor'));

        // Edge cases
        $this->assertTrue(RoleConfigService::canRenameRole('', 'new_role'));
        $this->assertFalse(RoleConfigService::canRenameRole('custom_role', ''));
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_validate_role_operation_delete(): void
    {
        // Protected roles cannot be deleted
        $error = RoleConfigService::validateRoleOperation('delete', 'admin');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Cannot delete protected system role', $error);
        $this->assertStringContainsString('admin', $error);

        // Custom roles can be deleted
        $error = RoleConfigService::validateRoleOperation('delete', 'custom_role');
        $this->assertNull($error);
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_validate_role_operation_rename(): void
    {
        // Cannot rename system roles
        $error = RoleConfigService::validateRoleOperation('rename', 'admin', 'super_admin');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Cannot rename system role', $error);

        // Cannot rename to system role names
        $error = RoleConfigService::validateRoleOperation('rename', 'custom_role', 'admin');
        $this->assertNotNull($error);
        $this->assertStringContainsString('conflicts with a system role name', $error);

        // Can rename custom roles
        $error = RoleConfigService::validateRoleOperation('rename', 'custom_role', 'new_custom');
        $this->assertNull($error);

        // Missing new role name
        $error = RoleConfigService::validateRoleOperation('rename', 'custom_role', null);
        $this->assertNotNull($error);
        $this->assertStringContainsString('New role name is required', $error);

        $error = RoleConfigService::validateRoleOperation('rename', 'custom_role', '');
        $this->assertNotNull($error);
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_validate_role_operation_unknown_operation(): void
    {
        $error = RoleConfigService::validateRoleOperation('unknown_operation', 'admin');
        $this->assertNull($error); // Unknown operations are allowed (no validation)
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_get_admin_middleware(): void
    {
        $middleware = RoleConfigService::getAdminMiddleware();
        $this->assertEquals('role:admin', $middleware);
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_default_role_configuration(): void
    {
        Config::set('roles.default_roles', [
            'self_registration' => 'user',
            'admin_created' => 'basic_user',
        ]);

        $this->assertEquals('user', RoleConfigService::getDefaultSelfRegistrationRole());
        $this->assertEquals('basic_user', RoleConfigService::getDefaultAdminCreatedRole());
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_permission_categories(): void
    {
        Config::set('roles.permission_categories', [
            'user_management' => ['view users', 'create users'],
            'content' => ['view content', 'edit content'],
        ]);

        $categories = RoleConfigService::getPermissionsByCategory();
        $this->assertArrayHasKey('user_management', $categories);
        $this->assertArrayHasKey('content', $categories);

        $allPermissions = RoleConfigService::getAllPermissions();
        $expected = ['view users', 'create users', 'view content', 'edit content'];
        $this->assertEquals($expected, $allPermissions);
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_configuration_with_environment_variables(): void
    {
        // Simulate environment configuration
        Config::set('roles.system_roles', [
            'admin' => 'super_administrator',
            'manager' => 'team_leader',
            'basic_user' => 'standard_user',
            'user' => 'member',
        ]);

        $this->assertEquals('super_administrator', RoleConfigService::getAdminRole());
        $this->assertEquals('team_leader', RoleConfigService::getManagerRole());
        $this->assertEquals('standard_user', RoleConfigService::getBasicUserRole());
        $this->assertEquals('member', RoleConfigService::getUserRole());

        // Protection should work with new names
        $this->assertTrue(RoleConfigService::isSystemRole('super_administrator'));
        $this->assertTrue(RoleConfigService::isSystemRole('team_leader'));
        $this->assertFalse(RoleConfigService::isSystemRole('admin')); // Old name no longer system role
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_edge_cases_with_empty_configuration(): void
    {
        Config::set('roles.system_roles', []);
        Config::set('roles.protected_roles', []);

        $this->assertEquals([], RoleConfigService::getAllRoles());
        $this->assertEquals([], RoleConfigService::getSystemRoleNames());
        $this->assertFalse(RoleConfigService::isSystemRole('admin'));
        $this->assertFalse(RoleConfigService::isProtectedRole('admin'));
        $this->assertTrue(RoleConfigService::canRenameRole('any_role', 'new_name'));
    }

    /**
     * @group unit
     * @group role_config
     */
    public function test_case_sensitivity(): void
    {
        // Role names should be case-sensitive
        $this->assertTrue(RoleConfigService::isSystemRole('admin'));
        $this->assertFalse(RoleConfigService::isSystemRole('Admin'));
        $this->assertFalse(RoleConfigService::isSystemRole('ADMIN'));
    }
}

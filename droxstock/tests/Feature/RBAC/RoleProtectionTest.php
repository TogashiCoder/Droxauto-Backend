<?php

namespace Tests\Feature\RBAC;

use App\Models\User;
use App\Services\RoleConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Professional Test Suite for Role Protection System
 *
 * Tests comprehensive role protection functionality including:
 * - System role deletion protection
 * - System role rename protection
 * - Custom role management freedom
 * - Edge cases and security scenarios
 */
class RoleProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $customRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated admin user for all tests
        $this->adminUser = $this->createAdminUser();

        // Create a custom role for testing
        $this->customRole = Role::create([
            'name' => 'custom_test_role',
            'guard_name' => 'api'
        ]);
    }

    /**
     * @group role_protection
     * @group security
     */
    public function test_cannot_delete_admin_role(): void
    {
        $adminRoleName = RoleConfigService::getAdminRole();
        $adminRole = Role::where('name', $adminRoleName)->first();

        $response = $this->actingAs($this->adminUser, 'api')
            ->deleteJson("/api/v1/admin/roles/{$adminRole->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_type' => 'role_protection_violation'
            ])
            ->assertJsonFragment([
                'message' => "Cannot delete protected system role '{$adminRoleName}'. This role is required for system functionality."
            ]);

        // Verify role still exists
        $this->assertDatabaseHas('roles', [
            'name' => $adminRoleName,
            'guard_name' => 'api'
        ]);
    }

    /**
     * @group role_protection
     * @group security
     */
    public function test_cannot_delete_manager_role(): void
    {
        $managerRoleName = RoleConfigService::getManagerRole();
        $managerRole = Role::where('name', $managerRoleName)->first();

        $response = $this->actingAs($this->adminUser, 'api')
            ->deleteJson("/api/v1/admin/roles/{$managerRole->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_type' => 'role_protection_violation'
            ]);

        // Verify role still exists
        $this->assertDatabaseHas('roles', [
            'name' => $managerRoleName,
            'guard_name' => 'api'
        ]);
    }

    /**
     * @group role_protection
     * @group security
     */
    public function test_cannot_delete_basic_user_role(): void
    {
        $basicUserRoleName = RoleConfigService::getBasicUserRole();
        $basicUserRole = Role::where('name', $basicUserRoleName)->first();

        $response = $this->actingAs($this->adminUser, 'api')
            ->deleteJson("/api/v1/admin/roles/{$basicUserRole->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_type' => 'role_protection_violation'
            ]);

        // Verify role still exists
        $this->assertDatabaseHas('roles', [
            'name' => $basicUserRoleName,
            'guard_name' => 'api'
        ]);
    }

    /**
     * @group role_protection
     * @group security
     */
    public function test_cannot_rename_admin_role(): void
    {
        $adminRoleName = RoleConfigService::getAdminRole();
        $adminRole = Role::where('name', $adminRoleName)->first();

        $response = $this->actingAs($this->adminUser, 'api')
            ->putJson("/api/v1/admin/roles/{$adminRole->id}", [
                'name' => 'super_administrator',
                'description' => 'Updated description'
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name'
                ]
            ])
            ->assertJsonFragment([
                'name' => ["Cannot rename system role '{$adminRoleName}'. System roles are protected to maintain functionality."]
            ]);

        // Verify role name unchanged
        $this->assertDatabaseHas('roles', [
            'name' => $adminRoleName,
            'guard_name' => 'api'
        ]);

        $this->assertDatabaseMissing('roles', [
            'name' => 'super_administrator',
            'guard_name' => 'api'
        ]);
    }

    /**
     * @group role_protection
     * @group security
     */
    public function test_cannot_rename_to_existing_system_role_name(): void
    {
        $adminRoleName = RoleConfigService::getAdminRole();

        $response = $this->actingAs($this->adminUser, 'api')
            ->putJson("/api/v1/admin/roles/{$this->customRole->id}", [
                'name' => $adminRoleName,
                'description' => 'Trying to become admin'
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name'
                ]
            ])
;

        // Check that the protection error message is present
        $responseData = $response->json();
        $nameErrors = $responseData['errors']['name'] ?? [];
        $protectionErrorFound = false;

        foreach ($nameErrors as $error) {
            if (str_contains($error, "Cannot rename to '{$adminRoleName}' as it conflicts with a system role name.")) {
                $protectionErrorFound = true;
                break;
            }
        }

        $this->assertTrue($protectionErrorFound,
            "Expected role protection error message not found in: " . json_encode($nameErrors));

        // Verify custom role name unchanged
        $this->assertDatabaseHas('roles', [
            'id' => $this->customRole->id,
            'name' => 'custom_test_role',
            'guard_name' => 'api'
        ]);
    }

    /**
     * @group role_protection
     * @group functionality
     */
    public function test_can_delete_custom_roles(): void
    {
        $response = $this->actingAs($this->adminUser, 'api')
            ->deleteJson("/api/v1/admin/roles/{$this->customRole->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);

        // Verify role deleted
        $this->assertDatabaseMissing('roles', [
            'id' => $this->customRole->id,
            'name' => 'custom_test_role'
        ]);
    }

    /**
     * @group role_protection
     * @group functionality
     */
    public function test_can_rename_custom_roles(): void
    {
        $newRoleName = 'renamed_custom_role';

        $response = $this->actingAs($this->adminUser, 'api')
            ->putJson("/api/v1/admin/roles/{$this->customRole->id}", [
                'name' => $newRoleName,
                'description' => 'Renamed role description'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role updated successfully'
            ]);

        // Verify role renamed
        $this->assertDatabaseHas('roles', [
            'id' => $this->customRole->id,
            'name' => $newRoleName,
            'guard_name' => 'api'
        ]);

        $this->assertDatabaseMissing('roles', [
            'id' => $this->customRole->id,
            'name' => 'custom_test_role'
        ]);
    }

    /**
     * @group role_protection
     * @group functionality
     */
    public function test_can_create_custom_roles_with_any_name(): void
    {
        $customRoleName = 'content_moderator';

        $response = $this->actingAs($this->adminUser, 'api')
            ->postJson('/api/v1/admin/roles', [
                'name' => $customRoleName,
                'description' => 'Custom role for content moderation'
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Role created successfully'
            ]);

        // Verify role created
        $this->assertDatabaseHas('roles', [
            'name' => $customRoleName,
            'guard_name' => 'api'
        ]);
    }

    /**
     * @group role_protection
     * @group security
     */
    public function test_cannot_create_role_with_system_role_name(): void
    {
        $adminRoleName = RoleConfigService::getAdminRole();

        $response = $this->actingAs($this->adminUser, 'api')
            ->postJson('/api/v1/admin/roles', [
                'name' => $adminRoleName,
                'description' => 'Attempting to create duplicate admin role'
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name'
                ]
            ]);

        // Check that the protection error message is present
        $responseData = $response->json();
        $nameErrors = $responseData['errors']['name'] ?? [];
        $protectionErrorFound = false;

        foreach ($nameErrors as $error) {
            if (str_contains($error, "Cannot create a role with the name '{$adminRoleName}' as it conflicts with a system role name.")) {
                $protectionErrorFound = true;
                break;
            }
        }

        $this->assertTrue($protectionErrorFound,
            "Expected role protection error message not found in: " . json_encode($nameErrors));
    }

    /**
     * @group role_protection
     * @group edge_cases
     */
    public function test_role_config_service_correctly_identifies_system_roles(): void
    {
        // Test all system roles are identified correctly
        $systemRoles = [
            RoleConfigService::getAdminRole(),
            RoleConfigService::getManagerRole(),
            RoleConfigService::getBasicUserRole(),
            RoleConfigService::getUserRole(),
        ];

        foreach ($systemRoles as $roleName) {
            $this->assertTrue(
                RoleConfigService::isSystemRole($roleName),
                "Failed to identify '{$roleName}' as a system role"
            );

            $this->assertTrue(
                RoleConfigService::isProtectedRole($roleName),
                "Failed to identify '{$roleName}' as a protected role"
            );
        }

        // Test custom role is not identified as system role
        $this->assertFalse(
            RoleConfigService::isSystemRole('custom_test_role'),
            "Incorrectly identified 'custom_test_role' as a system role"
        );
    }

    /**
     * @group role_protection
     * @group edge_cases
     */
    public function test_role_rename_validation_logic(): void
    {
        $adminRole = RoleConfigService::getAdminRole();
        $managerRole = RoleConfigService::getManagerRole();

        // Cannot rename system role
        $this->assertFalse(
            RoleConfigService::canRenameRole($adminRole, 'super_admin'),
            'Should not allow renaming system roles'
        );

        // Cannot rename to system role name
        $this->assertFalse(
            RoleConfigService::canRenameRole('custom_role', $managerRole),
            'Should not allow renaming to system role names'
        );

        // Can rename custom role to custom name
        $this->assertTrue(
            RoleConfigService::canRenameRole('custom_role', 'another_custom_role'),
            'Should allow renaming custom roles to custom names'
        );
    }

    /**
     * @group role_protection
     * @group edge_cases
     */
    public function test_validation_error_messages_are_descriptive(): void
    {
        $adminRole = RoleConfigService::getAdminRole();

        // Test delete validation message
        $deleteError = RoleConfigService::validateRoleOperation('delete', $adminRole);
        $this->assertNotNull($deleteError, 'Should return error for system role deletion');
        $this->assertStringContainsString('Cannot delete protected system role', $deleteError);
        $this->assertStringContainsString($adminRole, $deleteError);

        // Test rename validation message
        $renameError = RoleConfigService::validateRoleOperation('rename', $adminRole, 'new_name');
        $this->assertNotNull($renameError, 'Should return error for system role rename');
        $this->assertStringContainsString('Cannot rename system role', $renameError);
        $this->assertStringContainsString($adminRole, $renameError);

        // Test rename to system role name
        $conflictError = RoleConfigService::validateRoleOperation('rename', 'custom_role', $adminRole);
        $this->assertNotNull($conflictError, 'Should return error for rename to system role name');
        $this->assertStringContainsString('conflicts with a system role name', $conflictError);
    }

    /**
     * @group role_protection
     * @group security
     */
    public function test_unauthorized_users_cannot_manage_roles(): void
    {
        $regularUser = User::factory()->create();

        // Test unauthorized deletion attempt
        $response = $this->actingAs($regularUser, 'api')
            ->deleteJson("/api/v1/admin/roles/{$this->customRole->id}");

        $response->assertStatus(403); // Forbidden due to lack of admin role

        // Test unauthorized update attempt
        $response = $this->actingAs($regularUser, 'api')
            ->putJson("/api/v1/admin/roles/{$this->customRole->id}", [
                'name' => 'hacked_role'
            ]);

        $response->assertStatus(403); // Forbidden due to lack of admin role
    }

    /**
     * @group role_protection
     * @group integration
     */
    public function test_protection_works_across_multiple_environments(): void
    {
        // Test with different role configurations
        config(['roles.system_roles.admin' => 'super_administrator']);

        $newAdminRoleName = RoleConfigService::getAdminRole();
        $this->assertEquals('super_administrator', $newAdminRoleName);

        // Create role with new name
        $newAdminRole = Role::create([
            'name' => $newAdminRoleName,
            'guard_name' => 'api'
        ]);

        // Test protection works with new name
        $this->assertTrue(
            RoleConfigService::isSystemRole($newAdminRoleName),
            'Protection should work with environment-configured role names'
        );

        $deleteError = RoleConfigService::validateRoleOperation('delete', $newAdminRoleName);
        $this->assertNotNull($deleteError, 'Should protect dynamically configured role names');
    }

    /**
     * @group role_protection
     * @group performance
     */
    public function test_role_protection_performance(): void
    {
        $startTime = microtime(true);

        // Perform multiple protection checks
        for ($i = 0; $i < 100; $i++) {
            RoleConfigService::isSystemRole('admin');
            RoleConfigService::validateRoleOperation('delete', 'admin');
            RoleConfigService::canRenameRole('custom_role', 'new_name');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete 300 operations in under 100ms
        $this->assertLessThan(0.1, $executionTime, 'Role protection checks should be performant');
    }
}

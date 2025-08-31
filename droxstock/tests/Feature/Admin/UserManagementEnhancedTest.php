<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementEnhancedTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        // Create test user
        $this->testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_deactivate_user_account()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->testUser->id}/deactivate", [
                'reason' => 'User requested deactivation'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User deactivated successfully',
                'data' => [
                    'user_id' => $this->testUser->id,
                    'is_active' => false,
                    'deactivation_reason' => 'User requested deactivation'
                ]
            ]);

        $this->testUser->refresh();
        $this->assertFalse($this->testUser->is_active);
        $this->assertNotNull($this->testUser->deactivated_at);
        $this->assertEquals('User requested deactivation', $this->testUser->deactivation_reason);
    }

    /** @test */
    public function admin_can_activate_deactivated_user_account()
    {
        // First deactivate the user
        $this->testUser->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => 'Test deactivation'
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->testUser->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User activated successfully',
                'data' => [
                    'user_id' => $this->testUser->id,
                    'is_active' => true
                ]
            ]);

        $this->testUser->refresh();
        $this->assertTrue($this->testUser->is_active);
        $this->assertNull($this->testUser->deactivated_at);
        $this->assertNull($this->testUser->deactivation_reason);
    }

    /** @test */
    public function admin_can_reset_user_password()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->testUser->id}/reset-password");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully',
                'data' => [
                    'user_id' => $this->testUser->id
                ]
            ])
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'new_password',
                    'reset_at'
                ]
            ]);

        $this->assertNotEmpty($response->json('data.new_password'));
    }

    /** @test */
    public function admin_can_reset_user_password_with_custom_password()
    {
        $customPassword = 'custompass123';

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->testUser->id}/reset-password", [
                'new_password' => $customPassword
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully',
                'data' => [
                    'user_id' => $this->testUser->id,
                    'new_password' => $customPassword
                ]
            ]);
    }

    /** @test */
    public function deactivated_user_cannot_access_protected_routes()
    {
        // Deactivate the test user
        $this->testUser->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => 'Test deactivation'
        ]);

        $response = $this->actingAs($this->testUser)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Account is deactivated',
                'error' => 'account_deactivated'
            ]);
    }

    /** @test */
    public function user_list_includes_account_status_fields()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'is_active',
                        'last_login_at',
                        'deactivated_at',
                        'deactivation_reason',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function cannot_deactivate_already_deactivated_user()
    {
        $this->testUser->update([
            'is_active' => false,
            'deactivated_at' => now()
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->testUser->id}/deactivate");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'User is already deactivated'
            ]);
    }

    /** @test */
    public function cannot_activate_already_active_user()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->testUser->id}/activate");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'User is already active'
            ]);
    }
}

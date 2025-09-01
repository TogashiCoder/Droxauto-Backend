<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Role-Based Access Control (RBAC) Management', function () {

    describe('User Role Assignment', function () {

        it('assigns a single role to user successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-role', [
                'user_id' => $user->id,
                'role_id' => $role->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Role assigned to user successfully'
                ]);

            // Verify role is assigned to user
            $this->assertTrue($user->hasRole($role));
        });

        it('assigns multiple roles to user successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $role1 = Role::create(['name' => 'role_1', 'guard_name' => 'api']);
            $role2 = Role::create(['name' => 'role_2', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-multiple-roles', [
                'user_id' => $user->id,
                'role_ids' => [$role1->id, $role2->id]
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Multiple roles assigned to user successfully'
                ]);

            // Verify both roles are assigned to user
            $this->assertTrue($user->hasRole($role1));
            $this->assertTrue($user->hasRole($role2));
        });

        it('fails to assign role without admin permissions', function () {
            $basicData = $this->createBasicUserWithToken();

            $user = User::factory()->create();
            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $basicData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-role', [
                'user_id' => $user->id,
                'role_id' => $role->id
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
        });

        it('fails to assign non-existent role to user', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-role', [
                'user_id' => $user->id,
                'role_id' => 99999
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Role not found'
                ]);
        });

        it('fails to assign role to non-existent user', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-role', [
                'user_id' => '99999999-9999-9999-9999-999999999999',
                'role_id' => $role->id
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found'
                ]);
        });

        it('removes a single role from user successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $role = Role::create(['name' => 'removable_role', 'guard_name' => 'api']);
            $user->assignRole($role);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-role', [
                'user_id' => $user->id,
                'role_id' => $role->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Role removed from user successfully'
                ]);

            // Verify role is removed from user
            $this->assertFalse($user->hasRole($role));
        });

        it('removes all roles from user successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $role1 = Role::create(['name' => 'role_1', 'guard_name' => 'api']);
            $role2 = Role::create(['name' => 'role_2', 'guard_name' => 'api']);
            $user->assignRole([$role1, $role2]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-all-roles', [
                'user_id' => $user->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All roles removed from user successfully'
                ]);

            // Verify all roles are removed from user
            $this->assertFalse($user->hasRole($role1));
            $this->assertFalse($user->hasRole($role2));
        });

        it('fails to remove role from non-existent user', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-role', [
                'user_id' => '99999999-9999-9999-9999-999999999999',
                'role_id' => $role->id
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found'
                ]);
        });

        it('fails to remove non-existent role from user', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-role', [
                'user_id' => $user->id,
                'role_id' => 99999
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Role not found'
                ]);
        });

        it('prevents removing admin role from last admin user', function () {
            $adminData = $this->createAdminUserWithToken();

            // Try to remove admin role from the only admin user
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-role', [
                'user_id' => $adminData['user']->id,
                'role_id' => Role::where('name', 'admin')->first()->id
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot remove admin role from last admin user'
                ]);

            // Verify admin role is still assigned
            $this->assertTrue($adminData['user']->hasRole('admin'));
        });

        it('retrieves user permissions successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson("/api/v1/admin/users/{$user->id}/permissions");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'User permissions retrieved successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'user_id',
                        'user_name',
                        'roles' => [
                            '*' => [
                                'id',
                                'name',
                                'guard_name',
                                'permissions' => [
                                    '*' => [
                                        'id',
                                        'name',
                                        'guard_name'
                                    ]
                                ]
                            ]
                        ],
                        'direct_permissions' => [
                            '*' => [
                                'id',
                                'name',
                                'guard_name'
                            ]
                        ],
                        'all_permissions' => [
                            '*' => [
                                'id',
                                'name',
                                'guard_name'
                            ]
                        ]
                    ]
                ]);
        });

        it('fails to retrieve permissions for non-existent user', function () {
            $adminData = $this->createAdminUserWithToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson('/api/v1/admin/users/99999/permissions');

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found'
                ]);
        });

        it('handles assigning duplicate roles gracefully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $role = Role::create(['name' => 'duplicate_role', 'guard_name' => 'api']);

            // Assign role first time
            $user->assignRole($role);

            // Try to assign the same role again
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-role', [
                'user_id' => $user->id,
                'role_id' => $role->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Role assigned to user successfully'
                ]);

            // Verify user still has the role (no duplicate)
            $this->assertTrue($user->hasRole($role));
            $this->assertEquals(1, $user->roles()->where('id', $role->id)->count());
        });

        it('validates required fields for role assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-role', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id', 'role_id']);
        });

        it('validates required fields for multiple role assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-multiple-roles', [
                'user_id' => $user->id
                // Missing role_ids
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['role_ids']);
        });

        it('validates role_ids array for multiple role assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-multiple-roles', [
                'user_id' => $user->id,
                'role_ids' => 'not_an_array'
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['role_ids']);
        });

        it('handles empty role_ids array for multiple role assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-multiple-roles', [
                'user_id' => $user->id,
                'role_ids' => []
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['role_ids']);
        });
    });
});

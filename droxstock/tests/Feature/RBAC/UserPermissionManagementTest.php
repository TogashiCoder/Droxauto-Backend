<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Role-Based Access Control (RBAC) Management', function () {

    describe('User Permission Management', function () {

        it('assigns a single permission to user successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-permission', [
                'user_id' => $user->id,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission assigned to user successfully'
                ]);

            // Verify permission is assigned to user
            $this->assertTrue($user->hasDirectPermission($permission));
        });

        it('assigns multiple permissions to user successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $permission1 = Permission::create(['name' => 'permission_1', 'guard_name' => 'api']);
            $permission2 = Permission::create(['name' => 'permission_2', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-multiple-permissions', [
                'user_id' => $user->id,
                'permission_ids' => [$permission1->id, $permission2->id]
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Multiple permissions assigned to user successfully'
                ]);

            // Verify both permissions are assigned to user
            $this->assertTrue($user->hasDirectPermission($permission1));
            $this->assertTrue($user->hasDirectPermission($permission2));
        });

        it('fails to assign permission without admin permissions', function () {
            $basicData = $this->createBasicUserWithToken();

            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $basicData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-permission', [
                'user_id' => $user->id,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
        });

        it('fails to assign non-existent permission to user', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-permission', [
                'user_id' => $user->id,
                'permission_id' => 99999
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Permission not found'
                ]);
        });

        it('fails to assign permission to non-existent user', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-permission', [
                'user_id' => '99999999-9999-9999-9999-999999999999',
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found'
                ]);
        });

        it('removes a single permission from user successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'removable_permission', 'guard_name' => 'api']);
            $user->givePermissionTo($permission);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-permission', [
                'user_id' => $user->id,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission removed from user successfully'
                ]);

            // Verify permission is removed from user
            $this->assertFalse($user->hasDirectPermission($permission));
        });

        it('removes all permissions from user successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $permission1 = Permission::create(['name' => 'permission_1', 'guard_name' => 'api']);
            $permission2 = Permission::create(['name' => 'permission_2', 'guard_name' => 'api']);
            $user->givePermissionTo([$permission1, $permission2]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-all-permissions', [
                'user_id' => $user->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All permissions removed from user successfully'
                ]);

            // Verify all permissions are removed from user
            $this->assertFalse($user->hasDirectPermission($permission1));
            $this->assertFalse($user->hasDirectPermission($permission2));
        });

        it('fails to remove permission from non-existent user', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-permission', [
                'user_id' => '99999999-9999-9999-9999-999999999999',
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found'
                ]);
        });

        it('fails to remove non-existent permission from user', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-permission', [
                'user_id' => $user->id,
                'permission_id' => 99999
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Permission not found'
                ]);
        });



        it('prevents removing critical permissions from admin users', function () {
            $adminData = $this->createAdminUserWithToken();

            $criticalPermission = Permission::create(['name' => 'manage_users', 'guard_name' => 'api']);
            $adminData['user']->givePermissionTo($criticalPermission);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/remove-permission', [
                'user_id' => $adminData['user']->id,
                'permission_id' => $criticalPermission->id
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot remove critical permissions from admin users'
                ]);

            // Verify critical permission is still assigned
            $this->assertTrue($adminData['user']->hasDirectPermission($criticalPermission));
        });

        it('handles assigning duplicate permissions gracefully', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'duplicate_permission', 'guard_name' => 'api']);

            // Assign permission first time
            $user->givePermissionTo($permission);

            // Try to assign the same permission again
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-permission', [
                'user_id' => $user->id,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission assigned to user successfully'
                ]);

            // Verify user still has the permission (no duplicate)
            $this->assertTrue($user->hasDirectPermission($permission));
            $this->assertEquals(1, $user->permissions()->where('id', $permission->id)->count());
        });

        it('validates required fields for permission assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-permission', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id', 'permission_id']);
        });

        it('validates required fields for multiple permission assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-multiple-permissions', [
                'user_id' => $user->id
                // Missing permission_ids
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['permission_ids']);
        });

        it('validates permission_ids array for multiple permission assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-multiple-permissions', [
                'user_id' => $user->id,
                'permission_ids' => 'not_an_array'
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['permission_ids']);
        });

        it('handles empty permission_ids array for multiple permission assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/users/assign-multiple-permissions', [
                'user_id' => $user->id,
                'permission_ids' => []
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['permission_ids']);
        });

        it('distinguishes between direct permissions and role-based permissions', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $rolePermission = Permission::create(['name' => 'role_permission', 'guard_name' => 'api']);
            $directPermission = Permission::create(['name' => 'direct_permission', 'guard_name' => 'api']);

            $role->givePermissionTo($rolePermission);
            $user->assignRole($role);
            $user->givePermissionTo($directPermission);

            // Verify user has both types of permissions
            $this->assertTrue($user->hasPermissionTo($rolePermission)); // Through role
            $this->assertTrue($user->hasDirectPermission($directPermission)); // Direct
            $this->assertFalse($user->hasDirectPermission($rolePermission)); // Not direct
        });

        it('provides comprehensive user permission overview', function () {
            $adminData = $this->createAdminUserWithToken();

            $user = User::factory()->create();
            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $rolePermission = Permission::create(['name' => 'role_permission', 'guard_name' => 'api']);
            $directPermission = Permission::create(['name' => 'direct_permission', 'guard_name' => 'api']);

            $role->givePermissionTo($rolePermission);
            $user->assignRole($role);
            $user->givePermissionTo($directPermission);

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

            // Verify the response contains the expected data
            $responseData = $response->json('data');
            $this->assertEquals($user->id, $responseData['user_id']);
            $this->assertEquals($user->name, $responseData['user_name']);

            // Check that direct permissions are correctly identified
            $directPermissionNames = collect($responseData['direct_permissions'])->pluck('name');
            $this->assertTrue($directPermissionNames->contains('direct_permission'));
            $this->assertFalse($directPermissionNames->contains('role_permission'));

            // Check that all permissions include both types
            $allPermissionNames = collect($responseData['all_permissions'])->pluck('name');
            $this->assertTrue($allPermissionNames->contains('direct_permission'));
            $this->assertTrue($allPermissionNames->contains('role_permission'));
        });
    });
});

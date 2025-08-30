<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Role-Based Access Control (RBAC) Management', function () {

    describe('Role Permission Management', function () {

        it('assigns a single permission to role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-permission', [
                'role_id' => $role->id,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission assigned to role successfully'
                ]);

            // Verify permission is assigned to role
            $this->assertTrue($role->hasPermissionTo($permission));
        });

        it('assigns multiple permissions to role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $permission1 = Permission::create(['name' => 'permission_1', 'guard_name' => 'api']);
            $permission2 = Permission::create(['name' => 'permission_2', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-multiple-permissions', [
                'role_id' => $role->id,
                'permission_ids' => [$permission1->id, $permission2->id]
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Multiple permissions assigned to role successfully'
                ]);

            // Verify both permissions are assigned to role
            $this->assertTrue($role->hasPermissionTo($permission1));
            $this->assertTrue($role->hasPermissionTo($permission2));
        });

        it('fails to assign permission without admin permissions', function () {
            $basicData = $this->createBasicUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $basicData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-permission', [
                'role_id' => $role->id,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
        });

        it('fails to assign non-existent permission to role', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-permission', [
                'role_id' => $role->id,
                'permission_id' => 99999
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Permission not found'
                ]);
        });

        it('fails to assign permission to non-existent role', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-permission', [
                'role_id' => 99999,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Role not found'
                ]);
        });

        it('removes a single permission from role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $permission = Permission::create(['name' => 'removable_permission', 'guard_name' => 'api']);
            $role->givePermissionTo($permission);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/remove-permission', [
                'role_id' => $role->id,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission removed from role successfully'
                ]);

            // Verify permission is removed from role
            $this->assertFalse($role->hasPermissionTo($permission));
        });

        it('removes all permissions from role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $permission1 = Permission::create(['name' => 'permission_1', 'guard_name' => 'api']);
            $permission2 = Permission::create(['name' => 'permission_2', 'guard_name' => 'api']);
            $role->givePermissionTo([$permission1, $permission2]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/remove-all-permissions', [
                'role_id' => $role->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All permissions removed from role successfully'
                ]);

            // Verify all permissions are removed from role
            $this->assertFalse($role->hasPermissionTo($permission1));
            $this->assertFalse($role->hasPermissionTo($permission2));
        });

        it('fails to remove permission from non-existent role', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/remove-permission', [
                'role_id' => 99999,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Role not found'
                ]);
        });

        it('fails to remove non-existent permission from role', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/remove-permission', [
                'role_id' => $role->id,
                'permission_id' => 99999
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Permission not found'
                ]);
        });

        it('prevents removing critical permissions from admin role', function () {
            $adminData = $this->createAdminUserWithToken();

            $adminRole = Role::where('name', 'admin')->first();
            $criticalPermission = Permission::create(['name' => 'manage_users', 'guard_name' => 'api']);
            $adminRole->givePermissionTo($criticalPermission);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/remove-permission', [
                'role_id' => $adminRole->id,
                'permission_id' => $criticalPermission->id
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot remove critical permissions from admin role'
                ]);

            // Verify critical permission is still assigned
            $this->assertTrue($adminRole->hasPermissionTo($criticalPermission));
        });

        it('handles assigning duplicate permissions gracefully', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $permission = Permission::create(['name' => 'duplicate_permission', 'guard_name' => 'api']);
            
            // Assign permission first time
            $role->givePermissionTo($permission);

            // Try to assign the same permission again
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-permission', [
                'role_id' => $role->id,
                'permission_id' => $permission->id
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission assigned to role successfully'
                ]);

            // Verify role still has the permission (no duplicate)
            $this->assertTrue($role->hasPermissionTo($permission));
            $this->assertEquals(1, $role->permissions()->where('id', $permission->id)->count());
        });

        it('validates required fields for permission assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-permission', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['role_id', 'permission_id']);
        });

        it('validates required fields for multiple permission assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-multiple-permissions', [
                'role_id' => $role->id
                // Missing permission_ids
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['permission_ids']);
        });

        it('validates permission_ids array for multiple permission assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-multiple-permissions', [
                'role_id' => $role->id,
                'permission_ids' => 'not_an_array'
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['permission_ids']);
        });

        it('handles empty permission_ids array for multiple permission assignment', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/assign-multiple-permissions', [
                'role_id' => $role->id,
                'permission_ids' => []
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['permission_ids']);
        });

        it('provides comprehensive role permission overview', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $permission1 = Permission::create(['name' => 'permission_1', 'guard_name' => 'api']);
            $permission2 = Permission::create(['name' => 'permission_2', 'guard_name' => 'api']);
            
            $role->givePermissionTo([$permission1, $permission2]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson("/api/v1/admin/roles/{$role->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Role retrieved successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'role' => [
                            'id',
                            'name',
                            'guard_name',
                            'description',
                            'permissions_count',
                            'permissions',
                            'created_at',
                            'updated_at',
                            'is_system_role',
                            'can_be_deleted',
                            'can_be_modified',
                            'links'
                        ]
                    ]
                ]);

            // Verify the response contains the expected data
            $responseData = $response->json('data.role');
            $this->assertEquals($role->id, $responseData['id']);
            $this->assertEquals($role->name, $responseData['name']);
            $this->assertEquals(2, $responseData['permissions_count']);
            
            // Check that permissions are included
            $permissionNames = collect($responseData['permissions'])->pluck('name');
            $this->assertTrue($permissionNames->contains('permission_1'));
            $this->assertTrue($permissionNames->contains('permission_2'));
        });

        it('handles system role protection correctly', function () {
            $adminData = $this->createAdminUserWithToken();

            $adminRole = Role::where('name', 'admin')->first();
            $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

            // Try to remove all permissions from admin role
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles/remove-all-permissions', [
                'role_id' => $adminRole->id
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot remove all permissions from system roles'
                ]);

            // Verify admin role still has permissions
            $this->assertGreaterThan(0, $adminRole->permissions()->count());
        });
    });
});

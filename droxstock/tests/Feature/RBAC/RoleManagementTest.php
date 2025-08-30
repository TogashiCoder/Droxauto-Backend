<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Role-Based Access Control (RBAC) Management', function () {

    describe('Role Management', function () {

        it('creates a new role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $roleData = [
                'name' => 'test_role',
                'guard_name' => 'api',
                'description' => 'A test role for testing purposes'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles', $roleData);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Role created successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'role' => [
                            'id',
                            'name',
                            'guard_name',
                            'description',
                            'permissions_count',
                            'created_at',
                            'updated_at',
                            'is_system_role',
                            'can_be_deleted',
                            'can_be_modified',
                            'links'
                        ]
                    ]
                ]);

            // Verify role exists in database
            $this->assertDatabaseHas('roles', [
                'name' => 'test_role',
                'guard_name' => 'api',
                'description' => 'A test role for testing purposes'
            ]);
        });

        it('fails to create role without admin permissions', function () {
            $basicData = $this->createBasicUserWithToken();

            $roleData = [
                'name' => 'unauthorized_role',
                'guard_name' => 'api',
                'description' => 'This should fail'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $basicData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles', $roleData);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
        });

        it('fails to create role with invalid guard name', function () {
            $adminData = $this->createAdminUserWithToken();

            $roleData = [
                'name' => 'invalid_guard_role',
                'guard_name' => 'invalid_guard',
                'description' => 'This should fail due to invalid guard'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/roles', $roleData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['guard_name']);
        });

        it('retrieves all roles successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson('/api/v1/admin/roles');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Roles retrieved successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'roles' => [
                            '*' => [
                                'id',
                                'name',
                                'guard_name',
                                'description',
                                'permissions_count',
                                'created_at',
                                'updated_at',
                                'is_system_role',
                                'can_be_deleted',
                                'can_be_modified',
                                'links'
                            ]
                        ],
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total',
                            'last_page'
                        ]
                    ]
                ]);
        });

        it('retrieves specific role by ID successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api', 'description' => 'Test role']);

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
        });

        it('fails to retrieve non-existent role', function () {
            $adminData = $this->createAdminUserWithToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson('/api/v1/admin/roles/99999');

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Role not found'
                ]);
        });

        it('updates role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'updatable_role', 'guard_name' => 'api', 'description' => 'Original description']);

            $updateData = [
                'name' => 'updated_role',
                'description' => 'Updated description'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->putJson("/api/v1/admin/roles/{$role->id}", $updateData);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Role updated successfully'
                ]);

            // Verify role was updated in database
            $this->assertDatabaseHas('roles', [
                'id' => $role->id,
                'name' => 'updated_role',
                'description' => 'Updated description'
            ]);
        });

        it('fails to update role without admin permissions', function () {
            $basicData = $this->createBasicUserWithToken();

            $role = Role::create(['name' => 'updatable_role', 'guard_name' => 'api']);

            $updateData = [
                'name' => 'unauthorized_update',
                'description' => 'This should fail'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $basicData['token'],
                'Accept' => 'application/json'
            ])->putJson("/api/v1/admin/roles/{$role->id}", $updateData);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
        });

        it('deletes role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'deletable_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->deleteJson("/api/v1/admin/roles/{$role->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Role deleted successfully'
                ]);

            // Verify role was deleted from database
            $this->assertDatabaseMissing('roles', [
                'id' => $role->id
            ]);
        });

        it('fails to delete role without admin permissions', function () {
            $basicData = $this->createBasicUserWithToken();

            $role = Role::create(['name' => 'protected_role', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $basicData['token'],
                'Accept' => 'application/json'
            ])->deleteJson("/api/v1/admin/roles/{$role->id}");

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
        });

        it('fails to delete role assigned to users', function () {
            $adminData = $this->createAdminUserWithToken();

            $role = Role::create(['name' => 'assigned_role', 'guard_name' => 'api']);
            $user = User::factory()->create();
            $user->assignRole($role);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->deleteJson("/api/v1/admin/roles/{$role->id}");

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot delete role with assigned users'
                ]);
        });
    });
});

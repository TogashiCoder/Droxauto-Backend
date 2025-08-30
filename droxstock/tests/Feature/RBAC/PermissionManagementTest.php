<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Role-Based Access Control (RBAC) Management', function () {

    describe('Permission Management', function () {

        it('creates a new permission successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $permissionData = [
                'name' => 'edit articles',
                'guard_name' => 'api',
                'description' => 'Permission to edit articles'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/permissions', $permissionData);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission created successfully',
                    'data' => [
                        'permission' => [
                            'name' => 'edit articles',
                            'guard_name' => 'api',
                            'description' => 'Permission to edit articles'
                        ]
                    ]
                ]);

            // Verify permission exists in database
            $this->assertDatabaseHas('permissions', [
                'name' => 'edit articles',
                'guard_name' => 'api'
            ]);
        });

        it('fails to create permission without admin permissions', function () {
            $basicData = $this->createBasicUserWithToken();

            $permissionData = [
                'name' => 'unauthorized_permission',
                'guard_name' => 'api'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $basicData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/permissions', $permissionData);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
        });

        it('fails to create permission with duplicate name', function () {
            $adminData = $this->createAdminUserWithToken();

            // Create first permission
            Permission::create(['name' => 'duplicate_permission', 'guard_name' => 'api']);

            $permissionData = [
                'name' => 'duplicate_permission',
                'guard_name' => 'api'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/permissions', $permissionData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('fails to create permission with invalid guard name', function () {
            $adminData = $this->createAdminUserWithToken();

            $permissionData = [
                'name' => 'invalid_guard_permission',
                'guard_name' => 'invalid_guard'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson('/api/v1/admin/permissions', $permissionData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['guard_name']);
        });

        it('retrieves all permissions successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            // Create some test permissions
            Permission::create(['name' => 'test_permission_1', 'guard_name' => 'api']);
            Permission::create(['name' => 'test_permission_2', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson('/api/v1/admin/permissions');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permissions retrieved successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'permissions' => [
                            '*' => [
                                'id',
                                'name',
                                'guard_name',
                                'description',
                                'roles_count',
                                'created_at',
                                'updated_at',
                                'is_system_permission',
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

        it('retrieves specific permission by ID successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'specific_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson("/api/v1/admin/permissions/{$permission->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'permission' => [
                            'id' => $permission->id,
                            'name' => 'specific_permission',
                            'guard_name' => 'api'
                        ]
                    ]
                ]);
        });

        it('fails to retrieve non-existent permission', function () {
            $adminData = $this->createAdminUserWithToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson('/api/v1/admin/permissions/99999');

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Permission not found'
                ]);
        });

        it('updates permission successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'updatable_permission', 'guard_name' => 'api']);

            $updateData = [
                'name' => 'updated_permission_name',
                'description' => 'Updated description'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->putJson("/api/v1/admin/permissions/{$permission->id}", $updateData);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission updated successfully',
                    'data' => [
                        'permission' => [
                            'name' => 'updated_permission_name',
                            'description' => 'Updated description'
                        ]
                    ]
                ]);

            // Verify database update
            $this->assertDatabaseHas('permissions', [
                'id' => $permission->id,
                'name' => 'updated_permission_name'
            ]);
        });

        it('fails to update permission to duplicate name', function () {
            $adminData = $this->createAdminUserWithToken();

            // Create two permissions
            $permission1 = Permission::create(['name' => 'permission_1', 'guard_name' => 'api']);
            $permission2 = Permission::create(['name' => 'permission_2', 'guard_name' => 'api']);

            $updateData = [
                'name' => 'permission_1' // Try to update permission2 to permission1's name
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->putJson("/api/v1/admin/permissions/{$permission2->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('fails to update system permissions', function () {
            $adminData = $this->createAdminUserWithToken();

            // Get a system permission
            $systemPermission = Permission::where('name', 'view users')->first();

            $updateData = [
                'name' => 'modified_system_permission'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->putJson("/api/v1/admin/permissions/{$systemPermission->id}", $updateData);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot modify system permissions'
                ]);
        });

        it('deletes permission successfully when no roles assigned', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'deletable_permission', 'guard_name' => 'api']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->deleteJson("/api/v1/admin/permissions/{$permission->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission deleted successfully'
                ]);

            // Verify permission is deleted from database
            $this->assertDatabaseMissing('permissions', [
                'id' => $permission->id
            ]);
        });

        it('fails to delete permission with assigned roles', function () {
            $adminData = $this->createAdminUserWithToken();

            // Create a permission and assign it to a role
            $permission = Permission::create(['name' => 'assigned_permission', 'guard_name' => 'api']);
            $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
            $role->givePermissionTo($permission);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->deleteJson("/api/v1/admin/permissions/{$permission->id}");

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot delete permission with assigned roles'
                ]);
        });

        it('fails to delete system permissions', function () {
            $adminData = $this->createAdminUserWithToken();

            // Get a system permission
            $systemPermission = Permission::where('name', 'view users')->first();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->deleteJson("/api/v1/admin/permissions/{$systemPermission->id}");

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot delete system permissions'
                ]);
        });

        it('provides permission statistics', function () {
            $adminData = $this->createAdminUserWithToken();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->getJson('/api/v1/admin/permissions/statistics');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission statistics retrieved successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'total_permissions',
                        'system_permissions_count',
                        'custom_permissions_count',
                        'permissions_by_guard',
                        'most_used_permissions',
                        'unused_permissions_count'
                    ]
                ]);
        });

        it('clones permission successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $originalPermission = Permission::create([
                'name' => 'original_permission',
                'guard_name' => 'api',
                'description' => 'Original permission description'
            ]);

            $cloneData = [
                'name' => 'cloned_permission',
                'description' => 'Cloned permission'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminData['token'],
                'Accept' => 'application/json'
            ])->postJson("/api/v1/admin/permissions/{$originalPermission->id}/clone", $cloneData);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permission cloned successfully',
                    'data' => [
                        'permission' => [
                            'name' => 'cloned_permission',
                            'description' => 'Cloned permission'
                        ]
                    ]
                ]);

            // Verify cloned permission exists
            $this->assertDatabaseHas('permissions', [
                'name' => 'cloned_permission',
                'guard_name' => 'api'
            ]);
        });

        it('assigns permission to role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'assignable_permission', 'guard_name' => 'api']);
            $role = Role::create(['name' => 'assignable_role', 'guard_name' => 'api']);

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

        it('removes permission from role successfully', function () {
            $adminData = $this->createAdminUserWithToken();

            $permission = Permission::create(['name' => 'removable_permission', 'guard_name' => 'api']);
            $role = Role::create(['name' => 'removable_role', 'guard_name' => 'api']);
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
    });
});

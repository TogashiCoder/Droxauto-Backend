<?php

use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Admin User Management Endpoints', function () {
    beforeEach(function () {
        // Create and authenticate admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        $this->actingAs($this->adminUser, 'api');
    });

    describe('User Listing', function () {
        it('successfully lists all users with pagination', function () {
            // Create additional users
            User::factory()->count(15)->create();

            $response = $this->getJson('/api/v1/admin/users');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'email',
                                'roles',
                                'permissions',
                                'is_admin',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Users retrieved successfully'
                ]);

            // Verify pagination
            expect($response->json('data.data'))->toHaveCount(15);
            expect($response->json('data.total'))->toBe(16); // 15 + 1 admin
        });

        it('fails to list users without admin role', function () {
            $regularUser = User::factory()->create();
            $regularUser->assignRole('basic_user');
            $this->actingAs($regularUser, 'api');

            $response = $this->getJson('/api/v1/admin/users');

            $response->assertStatus(403);
        });

        it('fails to list users without authentication', function () {
            // Clear the current authentication by creating a new test instance
            $this->refreshApplication();

            $response = $this->getJson('/api/v1/admin/users');

            $response->assertStatus(401);
        });

        it('filters users by search term', function () {
            User::factory()->create(['name' => 'John Doe']);
            User::factory()->create(['name' => 'Jane Smith']);
            User::factory()->create(['name' => 'Bob Johnson']);

            $response = $this->getJson('/api/v1/admin/users?search=John');

            $response->assertStatus(200);

            $users = $response->json('data.data');
            expect($users)->toHaveCount(2); // John Doe + Bob Johnson
        });

        it('filters users by role', function () {
            $basicUser = User::factory()->create();
            $basicUser->assignRole('basic_user');

            $managerUser = User::factory()->create();
            $managerUser->assignRole('manager');

            $response = $this->getJson('/api/v1/admin/users?role=basic_user');

            $response->assertStatus(200);

            $users = $response->json('data.data');
            expect($users)->toHaveCount(1);
            expect($users[0]['id'])->toBe($basicUser->id);
        });
    });

    describe('User Creation', function () {
        it('successfully creates user with valid data and assigns basic_user role by default', function () {
            $userData = [
                'name' => 'New Employee',
                'email' => 'employee@company.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'permissions',
                        'is_admin',
                        'created_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => [
                        'name' => 'New Employee',
                        'email' => 'employee@company.com',
                        'is_admin' => false
                    ]
                ]);

            // Verify user was created in database
            $this->assertDatabaseHas('users', [
                'name' => 'New Employee',
                'email' => 'employee@company.com'
            ]);

            // Verify user has basic_user role by default
            $user = User::where('email', 'employee@company.com')->first();
            expect($user->hasRole('basic_user'))->toBeTrue();
            expect($user->getPermissionsArray())->toContain('view dapartos');
            expect($user->getPermissionsArray())->toContain('view csv status');
        });

        it('successfully creates user with specific roles', function () {
            $userData = [
                'name' => 'Manager User',
                'email' => 'manager@company.com',
                'password' => 'SecurePassword123!',
                'roles' => ['manager']
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);

            $response->assertStatus(201);

            // Verify user has manager role
            $user = User::where('email', 'manager@company.com')->first();
            expect($user->hasRole('manager'))->toBeTrue();
            expect($user->getPermissionsArray())->toContain('create dapartos');
            expect($user->getPermissionsArray())->toContain('edit dapartos');
        });

        it('fails to create user without admin role', function () {
            $regularUser = User::factory()->create();
            $regularUser->assignRole('basic_user');
            $this->actingAs($regularUser, 'api');

            $userData = [
                'name' => 'Unauthorized User',
                'email' => 'unauthorized@company.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);

            $response->assertStatus(403);
        });

        it('fails to create user with duplicate email', function () {
            User::factory()->create(['email' => 'duplicate@company.com']);

            $userData = [
                'name' => 'Duplicate User',
                'email' => 'duplicate@company.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('fails to create user with invalid role', function () {
            $userData = [
                'name' => 'Invalid Role User',
                'email' => 'invalidrole@company.com',
                'password' => 'SecurePassword123!',
                'roles' => ['non_existent_role']
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['roles.0']);
        });

        it('fails to create user with missing required fields', function () {
            $response = $this->postJson('/api/v1/admin/users', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
        });

        it('fails to create user with weak password', function () {
            $userData = [
                'name' => 'Weak Password User',
                'email' => 'weakpass@company.com',
                'password' => '123'
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('fails to create user with invalid email format', function () {
            $userData = [
                'name' => 'Invalid Email User',
                'email' => 'invalid-email-format',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });
    });

    describe('User Retrieval', function () {
        it('successfully retrieves specific user details', function () {
            $user = User::factory()->create();
            $user->assignRole('basic_user');

            // Debug: Check if user exists
            $this->assertDatabaseHas('users', ['id' => $user->id]);

            $response = $this->getJson("/api/v1/admin/users/{$user->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'permissions',
                        'is_admin',
                        'created_at',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'User retrieved successfully',
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_admin' => false
                    ]
                ]);
        });

        it('fails to retrieve non-existent user', function () {
            $response = $this->getJson('/api/v1/admin/users/99999');

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found'
                ]);
        });

        it('fails to retrieve user without admin role', function () {
            $regularUser = User::factory()->create();
            $regularUser->assignRole('basic_user');
            $this->actingAs($regularUser, 'api');

            $targetUser = User::factory()->create();

            $response = $this->getJson("/api/v1/admin/users/{$targetUser->id}");

            $response->assertStatus(403);
        });
    });

    describe('User Update', function () {
        it('successfully updates user information', function () {
            $user = User::factory()->create();

            $updateData = [
                'name' => 'Updated Name',
                'email' => 'updated@company.com'
            ];

            $response = $this->putJson("/api/v1/admin/users/{$user->id}", $updateData);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'data' => [
                        'name' => 'Updated Name',
                        'email' => 'updated@company.com'
                    ]
                ]);

            // Verify database was updated
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'name' => 'Updated Name',
                'email' => 'updated@company.com'
            ]);
        });

        it('fails to update user with duplicate email', function () {
            $user1 = User::factory()->create(['email' => 'user1@company.com']);
            $user2 = User::factory()->create(['email' => 'user2@company.com']);

            $updateData = [
                'email' => 'user1@company.com'
            ];

            $response = $this->putJson("/api/v1/admin/users/{$user2->id}", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('fails to update non-existent user', function () {
            $updateData = [
                'name' => 'Updated Name'
            ];

            $response = $this->putJson('/api/v1/admin/users/99999', $updateData);

            $response->assertStatus(404);
        });

        it('fails to update user without admin role', function () {
            $regularUser = User::factory()->create();
            $regularUser->assignRole('basic_user');
            $this->actingAs($regularUser, 'api');

            $targetUser = User::factory()->create();

            $updateData = ['name' => 'Updated Name'];

            $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}", $updateData);

            $response->assertStatus(403);
        });
    });

    describe('User Deletion', function () {
        it('successfully deletes user', function () {
            $user = User::factory()->create();

            $response = $this->deleteJson("/api/v1/admin/users/{$user->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);

            // Verify user was deleted
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        });

        it('fails to delete non-existent user', function () {
            $response = $this->deleteJson('/api/v1/admin/users/99999');

            $response->assertStatus(404);
        });

        it('fails to delete user without admin role', function () {
            $regularUser = User::factory()->create();
            $regularUser->assignRole('basic_user');
            $this->actingAs($regularUser, 'api');

            $targetUser = User::factory()->create();

            $response = $this->deleteJson("/api/v1/admin/users/{$targetUser->id}");

            $response->assertStatus(403);
        });

        it('prevents admin from deleting themselves', function () {
            $response = $this->deleteJson("/api/v1/admin/users/{$this->adminUser->id}");

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ]);
        });
    });

    describe('User Role Management', function () {
        it('successfully retrieves user roles', function () {
            $user = User::factory()->create();
            $user->assignRole('basic_user');

            $response = $this->getJson("/api/v1/admin/users/{$user->id}/roles");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'permissions'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'User roles retrieved successfully',
                    'data' => [
                        'id' => $user->id,
                        'roles' => ['basic_user']
                    ]
                ]);
        });

        it('successfully updates user roles', function () {
            $user = User::factory()->create();
            $user->assignRole('basic_user');

            $updateData = [
                'roles' => ['manager', 'basic_user']
            ];

            $response = $this->putJson("/api/v1/admin/users/{$user->id}/roles", $updateData);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'User roles updated successfully'
                ]);

            // Verify roles were updated
            $user->refresh();
            expect($user->hasRole('manager'))->toBeTrue();
            expect($user->hasRole('basic_user'))->toBeTrue();
            expect($user->getPermissionsArray())->toContain('create dapartos');
        });

        it('fails to update user roles with invalid role', function () {
            $user = User::factory()->create();

            $updateData = [
                'roles' => ['invalid_role']
            ];

            $response = $this->putJson("/api/v1/admin/users/{$user->id}/roles", $updateData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['roles.0']);
        });

        it('fails to update user roles without admin role', function () {
            $regularUser = User::factory()->create();
            $regularUser->assignRole('basic_user');
            $this->actingAs($regularUser, 'api');

            $targetUser = User::factory()->create();

            $updateData = ['roles' => ['manager']];

            $response = $this->putJson("/api/v1/admin/users/{$targetUser->id}/roles", $updateData);

            $response->assertStatus(403);
        });
    });

    describe('Security and Validation', function () {
        it('prevents SQL injection attempts', function () {
            $maliciousData = [
                'name' => 'Test User',
                'email' => "'; DROP TABLE users; --",
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/admin/users', $maliciousData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

            // Verify no malicious SQL was executed
            expect(User::count())->toBe(1); // Only admin user
        });

        it('handles XSS attempts in name field', function () {
            $maliciousData = [
                'name' => '<script>alert("XSS")</script>',
                'email' => 'xss@company.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/admin/users', $maliciousData);

            $response->assertStatus(201);

            // Verify XSS content is stored as-is (Laravel handles this)
            $user = User::where('email', 'xss@company.com')->first();
            expect($user->name)->toBe('<script>alert("XSS")</script>');
        });

        it('validates request size limits', function () {
            $largeName = str_repeat('A', 1000);
            $userData = [
                'name' => $largeName,
                'email' => 'large@company.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('handles malformed JSON requests gracefully', function () {
            $response = $this->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post('/api/v1/admin/users', ['invalid' => 'json']);

            $response->assertStatus(422);
        });
    });

    describe('Concurrent Operations', function () {
        it('handles concurrent user creation gracefully', function () {
            $userData = [
                'name' => 'Concurrent User',
                'email' => 'concurrent@company.com',
                'password' => 'SecurePassword123!'
            ];

            // Simulate concurrent requests
            $responses = collect(range(1, 3))->map(function () use ($userData) {
                return $this->postJson('/api/v1/admin/users', $userData);
            });

            // Only one should succeed
            $successCount = $responses->filter(fn($r) => $r->status() === 201)->count();
            expect($successCount)->toBe(1);

            // Verify only one user was created
            expect(User::where('email', 'concurrent@company.com')->count())->toBe(1);
        });

        it('handles concurrent role updates gracefully', function () {
            $user = User::factory()->create();

            $updateData = ['roles' => ['manager']];

            // Simulate concurrent role updates
            $responses = collect(range(1, 3))->map(function () use ($user, $updateData) {
                return $this->putJson("/api/v1/admin/users/{$user->id}/roles", $updateData);
            });

            // All should succeed (idempotent operation)
            $successCount = $responses->filter(fn($r) => $r->status() === 200)->count();
            expect($successCount)->toBe(3);

            // Verify user has manager role
            $user->refresh();
            expect($user->hasRole('manager'))->toBeTrue();
        });
    });

    describe('Data Consistency', function () {
        it('maintains referential integrity on user deletion', function () {
            $user = User::factory()->create();
            $userId = $user->id;

            // Verify user exists
            expect(User::find($userId))->not->toBeNull();

            // Delete user
            $response = $this->deleteJson("/api/v1/admin/users/{$userId}");
            $response->assertStatus(200);

            // Verify user is deleted
            expect(User::find($userId))->toBeNull();
        });

        it('maintains data consistency across multiple operations', function () {
            // Create user
            $userData = [
                'name' => 'Consistency User',
                'email' => 'consistency@company.com',
                'password' => 'SecurePassword123!'
            ];

            $createResponse = $this->postJson('/api/v1/admin/users', $userData);
            $createResponse->assertStatus(201);

            $userId = $createResponse->json('data.id');

            // Update user
            $updateData = ['name' => 'Updated Consistency User'];
            $updateResponse = $this->putJson("/api/v1/admin/users/{$userId}", $updateData);
            $updateResponse->assertStatus(200);

            // Update roles
            $roleData = ['roles' => ['manager']];
            $roleResponse = $this->putJson("/api/v1/admin/users/{$userId}/roles", $roleData);
            $roleResponse->assertStatus(200);

            // Verify final state
            $user = User::find($userId);
            expect($user->name)->toBe('Updated Consistency User');
            expect($user->hasRole('manager'))->toBeTrue();
        });

        it('encrypts sensitive user data', function () {
            $userData = [
                'name' => 'Encrypted User',
                'email' => 'encrypted@company.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/admin/users', $userData);
            $response->assertStatus(201);

            $user = User::where('email', 'encrypted@company.com')->first();

            // Verify password is hashed
            expect($user->password)->not->toBe('SecurePassword123!');
            expect(Hash::check('SecurePassword123!', $user->password))->toBeTrue();
        });
    });
});

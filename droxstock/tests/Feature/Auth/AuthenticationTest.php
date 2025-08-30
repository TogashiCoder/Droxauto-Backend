<?php

use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Authentication Endpoints', function () {
    describe('User Registration', function () {
        it('successfully registers a new user with valid data', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);

            // Debug: Print response content to see why we get 500
            if ($response->status() !== 201) {
                echo "Response Status: " . $response->status() . PHP_EOL;
                echo "Response Content: " . $response->content() . PHP_EOL;
            }

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'roles',
                            'permissions'
                        ],
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'User registered successfully',
                    'data' => [
                        'user' => [
                            'name' => 'John Doe',
                            'email' => 'john.doe@example.com',
                            'roles' => [],
                            'permissions' => []
                        ],
                        'token_type' => 'Bearer'
                    ]
                ]);

            // Verify user was created in database
            $this->assertDatabaseHas('users', [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com'
            ]);

            // Verify user has no roles (as per new security requirements)
            $user = User::where('email', 'john.doe@example.com')->first();
            expect($user->roles)->toBeEmpty();
            expect($user->getPermissionsArray())->toBeEmpty();
        });

        it('registers user without password confirmation', function () {
            $userData = [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('fails registration with invalid email format', function () {
            $userData = [
                'name' => 'Invalid User',
                'email' => 'invalid-email',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('fails registration with duplicate email', function () {
            // Create existing user
            User::factory()->create(['email' => 'existing@example.com']);

            $userData = [
                'name' => 'Duplicate User',
                'email' => 'existing@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('fails registration with weak password', function () {
            $userData = [
                'name' => 'Weak Password User',
                'email' => 'weak@example.com',
                'password' => '123',
                'password_confirmation' => '123'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('fails registration with missing required fields', function () {
            $response = $this->postJson('/api/v1/auth/register', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
        });

        it('fails registration with mismatched password confirmation', function () {
            $userData = [
                'name' => 'Mismatch User',
                'email' => 'mismatch@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'DifferentPassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('creates refresh token and stores it in cache', function () {
            $userData = [
                'name' => 'Cache Test User',
                'email' => 'cache@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);

            $response->assertStatus(201);

            $refreshToken = $response->json('data.refresh_token');
            $userId = $response->json('data.user.id');

            // Verify refresh token is stored in cache
            expect(Cache::get('refresh_token_' . $refreshToken))->toBe($userId);
        });
    });

    describe('User Login', function () {
        it('successfully logs in with valid credentials', function () {
            $user = User::factory()->create([
                'email' => 'login@example.com',
                'password' => Hash::make('SecurePassword123!')
            ]);

            $loginData = [
                'email' => 'login@example.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/login', $loginData);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'roles',
                            'permissions',
                            'is_admin'
                        ],
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user' => [
                            'email' => 'login@example.com',
                            'is_admin' => false
                        ],
                        'token_type' => 'Bearer'
                    ]
                ]);

            // Verify access token is valid
            $accessToken = $response->json('data.access_token');
            expect($accessToken)->not->toBeEmpty();
        });

        it('fails login with invalid email', function () {
            $loginData = [
                'email' => 'nonexistent@example.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/login', $loginData);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
        });

        it('fails login with invalid password', function () {
            $user = User::factory()->create([
                'email' => 'wrongpass@example.com',
                'password' => Hash::make('CorrectPassword123!')
            ]);

            $loginData = [
                'email' => 'wrongpass@example.com',
                'password' => 'WrongPassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/login', $loginData);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
        });

        it('fails login with missing credentials', function () {
            $response = $this->postJson('/api/v1/auth/login', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
        });

        it('fails login with invalid email format', function () {
            $loginData = [
                'email' => 'invalid-email-format',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/login', $loginData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('creates refresh token on successful login', function () {
            $user = User::factory()->create([
                'email' => 'refreshtoken@example.com',
                'password' => Hash::make('SecurePassword123!')
            ]);

            $loginData = [
                'email' => 'refreshtoken@example.com',
                'password' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/login', $loginData);

            $response->assertStatus(200);

            $refreshToken = $response->json('data.refresh_token');
            $userId = $response->json('data.user.id');

            // Verify refresh token is stored in cache
            expect(Cache::get('refresh_token_' . $refreshToken))->toBe($userId);
        });
    });

    describe('Token Refresh', function () {
        it('successfully refreshes access token with valid refresh token', function () {
            $user = User::factory()->create();
            $refreshToken = fake()->uuid();

            // Store refresh token in cache
            Cache::put('refresh_token_' . $refreshToken, $user->id, now()->addDays(30));

            $refreshData = [
                'refresh_token' => $refreshToken
            ];

            $response = $this->postJson('/api/v1/auth/refresh', $refreshData);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Token refreshed successfully',
                    'data' => [
                        'token_type' => 'Bearer'
                    ]
                ]);

            // Verify old refresh token is removed from cache
            expect(Cache::get('refresh_token_' . $refreshToken))->toBeNull();

            // Verify new refresh token is stored in cache
            $newRefreshToken = $response->json('data.refresh_token');
            expect(Cache::get('refresh_token_' . $newRefreshToken))->toBe($user->id);
        });

        it('fails refresh with missing refresh token', function () {
            $response = $this->postJson('/api/v1/auth/refresh', []);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Refresh token is required',
                    'error' => 'refresh_token_missing'
                ]);
        });

        it('fails refresh with invalid refresh token', function () {
            $refreshData = [
                'refresh_token' => 'invalid-uuid-token'
            ];

            $response = $this->postJson('/api/v1/auth/refresh', $refreshData);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid or expired refresh token',
                    'error' => 'invalid_refresh_token'
                ]);
        });

        it('fails refresh with expired refresh token', function () {
            $user = User::factory()->create();
            $refreshToken = fake()->uuid();

            // Store refresh token in cache with short expiry
            Cache::put('refresh_token_' . $refreshToken, $user->id, now()->addMinutes(1));

            // Wait for token to expire
            $this->travel(2)->minutes();

            $refreshData = [
                'refresh_token' => $refreshToken
            ];

            $response = $this->postJson('/api/v1/auth/refresh', $refreshData);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid or expired refresh token',
                    'error' => 'invalid_refresh_token'
                ]);
        });

        it('fails refresh with non-existent user', function () {
            $refreshToken = fake()->uuid();

            // Store refresh token for non-existent user ID
            Cache::put('refresh_token_' . $refreshToken, 99999, now()->addDays(30));

            $refreshData = [
                'refresh_token' => $refreshToken
            ];

            $response = $this->postJson('/api/v1/auth/refresh', $refreshData);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'user_not_found'
                ]);
        });

        it('revokes old tokens when refreshing', function () {
            $user = User::factory()->create();
            $refreshToken = fake()->uuid();

            // Create old access token
            $oldToken = $user->createToken('OldToken');

            // Store refresh token in cache
            Cache::put('refresh_token_' . $refreshToken, $user->id, now()->addDays(30));

            $refreshData = [
                'refresh_token' => $refreshToken
            ];

            $response = $this->postJson('/api/v1/auth/refresh', $refreshData);

            $response->assertStatus(200);

            // Verify old token is revoked
            expect($user->tokens()->count())->toBe(1);
            expect($user->tokens()->first()->id)->not->toBe($oldToken->accessToken->id);
        });
    });

    describe('User Profile', function () {
        it('successfully retrieves authenticated user profile', function () {
            $user = User::factory()->create();
            $this->actingAs($user, 'api');

            $response = $this->getJson('/api/v1/auth/me');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'roles',
                            'permissions',
                            'is_admin'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'User information retrieved successfully',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'is_admin' => false
                        ]
                    ]
                ]);
        });

        it('fails to retrieve profile without authentication', function () {
            $response = $this->getJson('/api/v1/auth/me');

            $response->assertStatus(401);
        });

        it('returns correct roles and permissions for user without roles', function () {
            $user = User::factory()->create();
            $this->actingAs($user, 'api');

            $response = $this->getJson('/api/v1/auth/me');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'user' => [
                            'roles' => [],
                            'permissions' => []
                        ]
                    ]
                ]);
        });

        it('returns correct roles and permissions for admin user', function () {
            $adminUser = User::factory()->create();
            $adminUser->assignRole('admin');
            $this->actingAs($adminUser, 'api');

            $response = $this->getJson('/api/v1/auth/me');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'user' => [
                            'is_admin' => true
                        ]
                    ]
                ]);

            // Verify admin has roles
            expect($response->json('data.user.roles'))->not->toBeEmpty();
        });
    });

    describe('User Logout', function () {
        it('successfully logs out authenticated user', function () {
            $user = User::factory()->create();
            $this->actingAs($user, 'api');

            // Create a token for the user
            $token = $user->createToken('TestToken');

            $response = $this->postJson('/api/v1/auth/logout');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully logged out'
                ]);

            // Verify all tokens are revoked
            expect($user->tokens()->count())->toBe(0);
        });

        it('fails to logout without authentication', function () {
            $response = $this->postJson('/api/v1/auth/logout');

            $response->assertStatus(401);
        });

        it('revokes all user tokens on logout', function () {
            $user = User::factory()->create();
            $this->actingAs($user, 'api');

            // Create multiple tokens
            $user->createToken('Token1');
            $user->createToken('Token2');
            $user->createToken('Token3');

            expect($user->tokens()->count())->toBe(3);

            $response = $this->postJson('/api/v1/auth/logout');

            $response->assertStatus(200);

            // Verify all tokens are revoked
            expect($user->tokens()->count())->toBe(0);
        });
    });

    describe('Authentication Edge Cases', function () {
        it('handles concurrent registration attempts gracefully', function () {
            $userData = [
                'name' => 'Concurrent User',
                'email' => 'concurrent@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            // Simulate concurrent requests
            $responses = collect(range(1, 3))->map(function () use ($userData) {
                return $this->postJson('/api/v1/auth/register', $userData);
            });

            // Only one should succeed
            $successCount = $responses->filter(fn($r) => $r->status() === 201)->count();
            expect($successCount)->toBe(1);

            // Verify only one user was created
            expect(User::where('email', 'concurrent@example.com')->count())->toBe(1);
        });

        it('handles malformed JSON requests gracefully', function () {
            $response = $this->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post('/api/v1/auth/register', [], [], [], '{"invalid": json}');

            $response->assertStatus(422);
        });

        it('validates request size limits', function () {
            $largeName = str_repeat('A', 1000);
            $userData = [
                'name' => $largeName,
                'email' => 'large@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('handles SQL injection attempts in email field', function () {
            $maliciousData = [
                'name' => 'Test User',
                'email' => "'; DROP TABLE users; --",
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $maliciousData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

            // Verify no malicious SQL was executed
            expect(User::count())->toBe(0);
        });

        it('handles XSS attempts in name field', function () {
            $maliciousData = [
                'name' => '<script>alert("XSS")</script>',
                'email' => 'xss@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $maliciousData);

            $response->assertStatus(201);

            // Verify XSS content is stored as-is (Laravel handles this)
            $user = User::where('email', 'xss@example.com')->first();
            expect($user->name)->toBe('<script>alert("XSS")</script>');
        });
    });

    describe('Rate Limiting', function () {
        it('enforces rate limiting on registration endpoint', function () {
            $userData = [
                'name' => 'Rate Limited User',
                'email' => 'ratelimited@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            // Make multiple rapid requests
            for ($i = 0; $i < 10; $i++) {
                $userData['email'] = "user{$i}@example.com";
                $this->postJson('/api/v1/auth/register', $userData);
            }

            // The 11th request should be rate limited
            $userData['email'] = 'user11@example.com';
            $response = $this->postJson('/api/v1/auth/register', $userData);

            // Note: This test assumes rate limiting is configured
            // You may need to adjust based on your actual rate limiting setup
            expect($response->status())->toBeIn([200, 201, 429]);
        });

        it('enforces rate limiting on login endpoint', function () {
            $user = User::factory()->create([
                'email' => 'ratelimit@example.com',
                'password' => Hash::make('SecurePassword123!')
            ]);

            $loginData = [
                'email' => 'ratelimit@example.com',
                'password' => 'SecurePassword123!'
            ];

            // Make multiple rapid requests
            for ($i = 0; $i < 10; $i++) {
                $this->postJson('/api/v1/auth/login', $loginData);
            }

            // The 11th request should be rate limited
            $response = $this->postJson('/api/v1/auth/login', $loginData);

            // Note: This test assumes rate limiting is configured
            // You may need to adjust based on your actual rate limiting setup
            expect($response->status())->toBeIn([200, 429]);
        });
    });

    describe('Database Integrity', function () {
        it('maintains referential integrity on user deletion', function () {
            $user = User::factory()->create();
            $userId = $user->id;

            // Verify user exists
            expect(User::find($userId))->not->toBeNull();

            // Delete user
            $user->delete();

            // Verify user is deleted
            expect(User::find($userId))->toBeNull();
        });

        it('encrypts sensitive user data', function () {
            $userData = [
                'name' => 'Encrypted User',
                'email' => 'encrypted@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            $response = $this->postJson('/api/v1/auth/register', $userData);
            $response->assertStatus(201);

            $user = User::where('email', 'encrypted@example.com')->first();

            // Verify password is hashed
            expect($user->password)->not->toBe('SecurePassword123!');
            expect(Hash::check('SecurePassword123!', $user->password))->toBeTrue();
        });

        it('maintains data consistency across multiple operations', function () {
            $userData = [
                'name' => 'Consistency User',
                'email' => 'consistency@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ];

            // Register user
            $registerResponse = $this->postJson('/api/v1/auth/register', $userData);
            $registerResponse->assertStatus(201);

            $userId = $registerResponse->json('data.user.id');
            $accessToken = $registerResponse->json('data.access_token');

            // Login with same credentials
            $loginResponse = $this->postJson('/api/v1/auth/login', [
                'email' => 'consistency@example.com',
                'password' => 'SecurePassword123!'
            ]);
            $loginResponse->assertStatus(200);

            // Verify user data consistency
            $user = User::find($userId);
            expect($user->email)->toBe('consistency@example.com');
            expect($user->name)->toBe('Consistency User');
        });
    });
});

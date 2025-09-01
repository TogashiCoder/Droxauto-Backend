<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Mail\UserRegistrationPending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User Self-Registration System', function () {

    beforeEach(function () {
        Mail::fake();
    });

    describe('User Registration', function () {

        it('allows users to register with valid data', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = $this->postJson('/api/v1/register/user', $userData);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Registration successful! Your account is pending admin approval. You will receive an email once approved.',
                    'data' => [
                        'email' => 'john.doe@example.com',
                        'status' => 'pending'
                    ]
                ]);

            // Verify user was created with correct status
            $this->assertDatabaseHas('users', [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'is_active' => false,
                'registration_status' => 'pending',
                'email_verified_at' => null,
            ]);

            // Verify password was hashed
            $user = User::where('email', 'john.doe@example.com')->first();
            $this->assertTrue(Hash::check('password123', $user->password));

            // Verify emails were sent
            Mail::assertSent(UserRegistrationPending::class, function ($mail) use ($user) {
                return $mail->hasTo('john.doe@example.com');
            });
        });

        it('rejects registration with invalid email', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = $this->postJson('/api/v1/register/user', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('rejects registration with weak password', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => '123',
                'password_confirmation' => '123',
            ];

            $response = $this->postJson('/api/v1/register/user', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('rejects registration with mismatched password confirmation', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'password_confirmation' => 'differentpassword',
            ];

            $response = $this->postJson('/api/v1/register/user', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('rejects registration with duplicate email', function () {
            // Create existing user
            User::factory()->create(['email' => 'existing@example.com']);

            $userData = [
                'name' => 'John Doe',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = $this->postJson('/api/v1/register/user', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('rejects registration with missing required fields', function () {
            $response = $this->postJson('/api/v1/register/user', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
        });

        it('sets registration date and status correctly', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $this->postJson('/api/v1/register/user', $userData);

            $user = User::where('email', 'john.doe@example.com')->first();

            $this->assertNotNull($user->registration_date);
            $this->assertEquals('pending', $user->registration_status);
            $this->assertEquals(0, $user->is_active);
        });
    });

    describe('Registration Status Check', function () {

        it('allows users to check their registration status', function () {
            $user = User::factory()->create([
                'registration_status' => 'pending',
                'is_active' => false,
                'registration_date' => now(),
            ]);

            $response = $this->getJson('/api/v1/register/status?email=' . $user->email);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'status' => 'pending',
                        'is_active' => false,
                    ]
                ]);
        });

        it('returns 404 for non-existent email', function () {
            $response = $this->getJson('/api/v1/register/status?email=nonexistent@example.com');

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found'
                ]);
        });

        it('requires email parameter', function () {
            $response = $this->getJson('/api/v1/register/status');

            $response->assertStatus(422);
        });
    });

    describe('Resend Verification Email', function () {

        it('allows users to resend verification email for pending accounts', function () {
            $user = User::factory()->create([
                'registration_status' => 'pending',
                'registration_date' => now(),
            ]);

            $response = $this->postJson('/api/v1/register/resend-verification', [
                'email' => $user->email
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Verification email sent successfully'
                ]);

            Mail::assertSent(UserRegistrationPending::class, function ($mail) use ($user) {
                return $mail->hasTo($user->email);
            });
        });

        it('prevents resending for approved accounts', function () {
            $user = User::factory()->create([
                'registration_status' => 'approved',
                'is_active' => true,
            ]);

            $response = $this->postJson('/api/v1/register/resend-verification', [
                'email' => $user->email
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Account already approved'
                ]);
        });

        it('returns 404 for non-existent email', function () {
            $response = $this->postJson('/api/v1/register/resend-verification', [
                'email' => 'nonexistent@example.com'
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found'
                ]);
        });
    });

    describe('Admin Notification', function () {

        it('notifies all admin users when new registration occurs', function () {
            // Create admin users
            $admin1 = User::factory()->create();
            $admin1->assignRole('admin');

            $admin2 = User::factory()->create();
            $admin2->assignRole('admin');

            $userData = [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $this->postJson('/api/v1/register/user', $userData);

            // Verify admin notification emails were sent
            Mail::assertSent(UserRegistrationPending::class, function ($mail) use ($admin1) {
                return $mail->hasTo($admin1->email);
            });

            Mail::assertSent(UserRegistrationPending::class, function ($mail) use ($admin2) {
                return $mail->hasTo($admin2->email);
            });
        });
    });
});

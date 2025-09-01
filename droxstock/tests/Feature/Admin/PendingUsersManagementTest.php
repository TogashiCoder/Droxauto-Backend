<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Mail\UserRegistrationApproved;
use App\Mail\UserRegistrationRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

describe('Pending Users Management System', function () {

    beforeEach(function () {
        Mail::fake();

        // Create permissions required for the tests
        Permission::firstOrCreate(['name' => 'approve user registrations', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'view users', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'access admin panel', 'guard_name' => 'api']);

        // Create admin role and user
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(['approve user registrations', 'view users', 'access admin panel']);
        $this->adminUser = User::factory()->create([
            'registration_status' => 'approved',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole($adminRole);

        // Create basic_user role
        $this->basicUserRole = Role::firstOrCreate(['name' => 'basic_user', 'guard_name' => 'api']);

        // Create pending user
        $this->pendingUser = User::factory()->create([
            'registration_status' => 'pending',
            'is_active' => false,
            'registration_date' => now(),
            'email_verified_at' => null,
        ]);
    });

    describe('List Pending Users', function () {

        it('allows admin to view all pending users', function () {
            // Create additional pending users
            User::factory()->count(3)->create([
                'registration_status' => 'pending',
                'is_active' => false,
                'registration_date' => now(),
            ]);

            $response = $this->actingAs($this->adminUser)
                ->getJson('/api/v1/admin/pending-users');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'registration_status',
                            'registration_date',
                        ]
                    ],
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'from',
                        'to',
                    ]
                ]);

            // Should have at least 4 pending users (1 from beforeEach + 3 created)
            $this->assertGreaterThanOrEqual(4, $response->json('pagination.total'));
        });

        it('requires admin authentication', function () {
            $regularUser = User::factory()->create();

            $response = $this->actingAs($regularUser)
                ->getJson('/api/v1/admin/pending-users');

            $response->assertStatus(403);
        });
    });

    describe('View Pending User Details', function () {

        it('allows admin to view pending user details', function () {
            // Create a fresh pending user right before the test
            $freshPendingUser = User::factory()->create([
                'registration_status' => 'pending',
                'is_active' => false,
                'registration_date' => now(),
                'email_verified_at' => null,
            ]);

            // Debug: Check the user status in database
            $this->assertEquals('pending', $freshPendingUser->registration_status, 'User should be in pending status');
            $this->assertEquals(0, $freshPendingUser->is_active, 'User should be inactive (0)');

            // Debug: Let's also check what users exist in the database
            $allUsers = User::all(['id', 'name', 'email', 'registration_status', 'is_active']);
            // Debug information removed for production

            // Try both users to see if the issue is consistent
            $response1 = $this->actingAs($this->adminUser)
                ->getJson("/api/v1/admin/pending-users/{$freshPendingUser->id}");

            $response2 = $this->actingAs($this->adminUser)
                ->getJson("/api/v1/admin/pending-users/2"); // Try the user from beforeEach

                    // Debug information removed for production

            $response = $response1; // Use the first response for assertions

            // Debug: If we get an error, let's see what it says
            if ($response->status() !== 200) {
                        // Debug information removed for production
            }

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $freshPendingUser->id,
                        'name' => $freshPendingUser->name,
                        'email' => $freshPendingUser->email,
                        'registration_status' => 'pending',
                        'registration_date' => $freshPendingUser->registration_date->toISOString(),
                    ]
                ]);
        });

        it('prevents viewing non-pending users', function () {
            $approvedUser = User::factory()->create([
                'registration_status' => 'approved',
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->adminUser)
                ->getJson("/api/v1/admin/pending-users/{$approvedUser->id}");

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'User is not pending approval'
                ]);
        });

        it('requires admin authentication', function () {
            $regularUser = User::factory()->create();

            $response = $this->actingAs($regularUser)
                ->getJson("/api/v1/admin/pending-users/{$this->pendingUser->id}");

            $response->assertStatus(403);
        });
    });

    describe('Approve User Registration', function () {

        it('allows admin to approve pending user registration', function () {
            $approvalData = [
                'admin_notes' => 'Welcome to our system!',
                'role' => 'basic_user',
            ];

            $response = $this->actingAs($this->adminUser)
                ->postJson("/api/v1/admin/pending-users/{$this->pendingUser->id}/approve", $approvalData);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'User registration approved successfully',
                    'data' => [
                        'user_id' => $this->pendingUser->id,
                        'status' => 'approved',
                        'assigned_role' => 'basic_user',
                    ]
                ]);

            // Verify user was updated
            $this->pendingUser->refresh();
            $this->assertEquals('approved', $this->pendingUser->registration_status);
            $this->assertEquals(1, $this->pendingUser->is_active, 'User should be active (1)');
            $this->assertNotNull($this->pendingUser->approved_at);
            $this->assertEquals('Welcome to our system!', $this->pendingUser->admin_notes);

            // Verify role was assigned
            $this->assertTrue($this->pendingUser->hasRole('basic_user'));

            // Verify approval email was sent
            Mail::assertSent(UserRegistrationApproved::class, function ($mail) {
                return $mail->hasTo($this->pendingUser->email);
            });
        });

        it('assigns default basic_user role when no role specified', function () {
            $response = $this->actingAs($this->adminUser)
                ->postJson("/api/v1/admin/pending-users/{$this->pendingUser->id}/approve", [
                    'admin_notes' => 'Default role assignment'
                ]);

            $response->assertStatus(200);

            $this->pendingUser->refresh();
            $this->assertTrue($this->pendingUser->hasRole('basic_user'));
        });

        it('prevents approving non-pending users', function () {
            $approvedUser = User::factory()->create([
                'registration_status' => 'approved',
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->adminUser)
                ->postJson("/api/v1/admin/pending-users/{$approvedUser->id}/approve", [
                    'admin_notes' => 'Test approval'
                ]);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'User is not pending approval'
                ]);
        });

        it('requires admin authentication', function () {
            $regularUser = User::factory()->create();

            $response = $this->actingAs($regularUser)
                ->postJson("/api/v1/admin/pending-users/{$this->pendingUser->id}/approve", [
                    'admin_notes' => 'Test approval'
                ]);

            $response->assertStatus(403);
        });
    });

    describe('Reject User Registration', function () {

        it('allows admin to reject pending user registration', function () {
            $rejectionData = [
                'rejection_reason' => 'Incomplete information provided',
            ];

            $response = $this->actingAs($this->adminUser)
                ->postJson("/api/v1/admin/pending-users/{$this->pendingUser->id}/reject", $rejectionData);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'User registration rejected and account deleted',
                    'data' => [
                        'user_id' => $this->pendingUser->id,
                        'status' => 'rejected',
                        'rejection_reason' => 'Incomplete information provided',
                    ]
                ]);

            // Note: User is deleted after rejection, so we can't check the database
            // The response already confirms the user was updated before deletion

            // Verify rejection email was sent
            Mail::assertSent(UserRegistrationRejected::class, function ($mail) {
                return $mail->hasTo($this->pendingUser->email);
            });
        });

        it('prevents rejecting non-pending users', function () {
            $approvedUser = User::factory()->create([
                'registration_status' => 'approved',
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->adminUser)
                ->postJson("/api/v1/admin/pending-users/{$approvedUser->id}/reject", [
                    'rejection_reason' => 'Test rejection'
                ]);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'User is not pending approval'
                ]);
        });

        it('requires rejection reason', function () {
            $response = $this->actingAs($this->adminUser)
                ->postJson("/api/v1/admin/pending-users/{$this->pendingUser->id}/reject", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['rejection_reason']);
        });

        it('requires admin authentication', function () {
            $regularUser = User::factory()->create();

            $response = $this->actingAs($regularUser)
                ->postJson("/api/v1/admin/pending-users/{$this->pendingUser->id}/reject", [
                    'rejection_reason' => 'Test rejection'
                ]);

            $response->assertStatus(403);
        });
    });

    describe('Pending Users Statistics', function () {

        it('provides comprehensive pending users statistics', function () {
            // Create additional users with different statuses
            User::factory()->count(2)->create([
                'registration_status' => 'pending',
                'is_active' => false,
                'registration_date' => now(),
            ]);

            User::factory()->create([
                'registration_status' => 'approved',
                'is_active' => true,
                'approved_at' => now(),
            ]);

            $response = $this->actingAs($this->adminUser)
                ->getJson('/api/v1/admin/pending-users-statistics');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_pending',
                        'total_approved_today',
                        'total_rejected_today',
                        'pending_by_date',
                    ]
                ]);

            $data = $response->json('data');
            $this->assertGreaterThanOrEqual(3, $data['total_pending']); // at least 1 from beforeEach + 2 created
            $this->assertEquals(1, $data['total_approved_today']);
        });

        it('requires admin authentication', function () {
            $regularUser = User::factory()->create();

            $response = $this->actingAs($regularUser)
                ->getJson('/api/v1/admin/pending-users-statistics');

            $response->assertStatus(403);
        });
    });

    describe('Email Notifications', function () {

        it('sends approval email with admin notes', function () {
            $approvalData = [
                'admin_notes' => 'Welcome aboard! Your account has been approved.',
                'role' => 'basic_user',
            ];

            $this->actingAs($this->adminUser)
                ->postJson("/api/v1/admin/pending-users/{$this->pendingUser->id}/approve", $approvalData);

            Mail::assertSent(UserRegistrationApproved::class, function ($mail) {
                return $mail->hasTo($this->pendingUser->email) &&
                    $mail->adminNotes === 'Welcome aboard! Your account has been approved.';
            });
        });

        it('sends rejection email with rejection reason', function () {
            $rejectionData = [
                'rejection_reason' => 'Please provide additional documentation',
            ];

            $this->actingAs($this->adminUser)
                ->postJson("/api/v1/admin/pending-users/{$this->pendingUser->id}/reject", $rejectionData);

            Mail::assertSent(UserRegistrationRejected::class, function ($mail) {
                return $mail->hasTo($this->pendingUser->email) &&
                    $mail->rejectionReason === 'Please provide additional documentation';
            });
        });
    });
});

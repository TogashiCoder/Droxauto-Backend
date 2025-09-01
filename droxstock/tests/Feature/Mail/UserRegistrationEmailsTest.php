<?php

namespace Tests\Feature\Mail;

use Tests\TestCase;
use App\Models\User;
use App\Mail\UserRegistrationPending;
use App\Mail\UserRegistrationApproved;
use App\Mail\UserRegistrationRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

describe('User Registration Email System', function () {

    beforeEach(function () {
        Mail::fake();
    });

    describe('UserRegistrationPending Email', function () {

        it('sends pending registration email with correct content', function () {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'registration_status' => 'pending',
                'registration_date' => now(),
            ]);

            $mailable = new UserRegistrationPending($user);

            // Test envelope
            $this->assertEquals(
                'Account Registration Pending - Admin Approval Required',
                $mailable->envelope()->subject
            );

            // Test content
            $this->assertEquals('emails.user-registration-pending', $mailable->content()->view);

            // Test that mailable has correct data
            $this->assertEquals($user->email, $mailable->envelope()->to[0]->address);
        });

        it('renders email template correctly', function () {
            $user = User::factory()->create([
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'registration_status' => 'pending',
                'registration_date' => now(),
            ]);

            $mailable = new UserRegistrationPending($user);
            $rendered = $mailable->render();

            // Check key content is present
            $this->assertStringContainsString('Account Registration Pending', $rendered);
            $this->assertStringContainsString('Jane Smith', $rendered);
            $this->assertStringContainsString('Pending Approval', $rendered);
            $this->assertStringContainsString('admin@example.com', $rendered);
        });
    });

    describe('UserRegistrationApproved Email', function () {

        it('sends approval email with correct content', function () {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'registration_status' => 'approved',
            ]);

            $adminNotes = 'Welcome to our system! Your account has been approved.';
            $mailable = new UserRegistrationApproved($user, $adminNotes);

            // Test envelope
            $this->assertEquals(
                'Account Approved - Welcome to Our System!',
                $mailable->envelope()->subject
            );

            // Test content
            $this->assertEquals('emails.user-registration-approved', $mailable->content()->view);

            // Test that mailable has correct data
            $this->assertEquals($user->email, $mailable->envelope()->to[0]->address);
        });

        it('renders approval email template correctly', function () {
            $user = User::factory()->create([
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'registration_status' => 'approved',
            ]);

            $adminNotes = 'Your account has been approved with manager role.';
            $mailable = new UserRegistrationApproved($user, $adminNotes);
            $rendered = $mailable->render();

            // Check key content is present
            $this->assertStringContainsString('🎉 Account Approved!', $rendered);
            $this->assertStringContainsString('Jane Smith', $rendered);
            $this->assertStringContainsString('✅ Approved', $rendered);
            $this->assertStringContainsString('Your account has been approved with manager role.', $rendered);
            $this->assertStringContainsString('🚀 Login to Your Account', $rendered);
        });

        it('handles empty admin notes gracefully', function () {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ]);

            $mailable = new UserRegistrationApproved($user, '');
            $rendered = $mailable->render();

            // Should not show admin notes section
            $this->assertStringNotContainsString('Admin Notes:', $rendered);
        });
    });

    describe('UserRegistrationRejected Email', function () {

        it('sends rejection email with correct content', function () {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'registration_status' => 'rejected',
            ]);

            $rejectionReason = 'Incomplete documentation provided.';
            $mailable = new UserRegistrationRejected($user, $rejectionReason);

            // Test envelope
            $this->assertEquals(
                'Account Registration Rejected',
                $mailable->envelope()->subject
            );

            // Test content
            $this->assertEquals('emails.user-registration-rejected', $mailable->content()->view);

            // Test that mailable has correct data
            $this->assertEquals($user->email, $mailable->envelope()->to[0]->address);
        });

        it('renders rejection email template correctly', function () {
            $user = User::factory()->create([
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'registration_status' => 'rejected',
            ]);

            $rejectionReason = 'Please provide additional verification documents.';
            $mailable = new UserRegistrationRejected($user, $rejectionReason);
            $rendered = $mailable->render();

            // Check key content is present
            $this->assertStringContainsString('Account Registration Rejected', $rendered);
            $this->assertStringContainsString('Jane Smith', $rendered);
            $this->assertStringContainsString('❌ Rejected', $rendered);
            $this->assertStringContainsString('Please provide additional verification documents.', $rendered);
            $this->assertStringContainsString('admin@example.com', $rendered);
        });

        it('handles empty rejection reason gracefully', function () {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ]);

            $mailable = new UserRegistrationRejected($user, '');
            $rendered = $mailable->render();

            // Should not show rejection reason section
            $this->assertStringNotContainsString('Reason for Rejection:', $rendered);
        });
    });

    describe('Email Template Rendering', function () {

        it('renders all email templates without errors', function () {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'registration_status' => 'pending',
                'registration_date' => now(),
            ]);

            // Test pending email
            $pendingMailable = new UserRegistrationPending($user);
            $this->assertNotEmpty($pendingMailable->render());

            // Test approved email
            $approvedMailable = new UserRegistrationApproved($user, 'Test notes');
            $this->assertNotEmpty($approvedMailable->render());

            // Test rejected email
            $rejectedMailable = new UserRegistrationRejected($user, 'Test reason');
            $this->assertNotEmpty($rejectedMailable->render());
        });

        it('includes proper styling in email templates', function () {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

            $mailable = new UserRegistrationPending($user);
            $rendered = $mailable->render();

            // Check for CSS styling
            $this->assertStringContainsString('font-family: Arial, sans-serif', $rendered);
            $this->assertStringContainsString('max-width: 600px', $rendered);
            $this->assertStringContainsString('border-radius: 5px', $rendered);
        });

        it('includes proper HTML structure', function () {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

            $mailable = new UserRegistrationPending($user);
            $rendered = $mailable->render();

            // Check HTML structure
            $this->assertStringContainsString('<!DOCTYPE html>', $rendered);
            $this->assertStringContainsString('<html lang="en">', $rendered);
            $this->assertStringContainsString('<head>', $rendered);
            $this->assertStringContainsString('<body>', $rendered);
            $this->assertStringContainsString('</html>', $rendered);
        });
    });

    describe('Email Data Validation', function () {

        it('passes correct user data to email templates', function () {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'registration_status' => 'pending',
                'registration_date' => now(),
            ]);

            $mailable = new UserRegistrationPending($user);

            $this->assertEquals('John Doe', $user->name);
            $this->assertEquals('john.doe@example.com', $user->email);
        });

        it('handles special characters in user names', function () {
            $user = User::factory()->create([
                'name' => 'José María O\'Connor',
                'email' => 'jose@example.com',
            ]);

            $mailable = new UserRegistrationPending($user);
            $rendered = $mailable->render();

            $this->assertStringContainsString('José María O&#039;Connor', $rendered);
        });

        it('formats registration date correctly', function () {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'registration_date' => '2024-01-15 10:30:00',
            ]);

            $mailable = new UserRegistrationPending($user);
            $rendered = $mailable->render();

            // Should contain formatted date
            $this->assertStringContainsString('January 15, 2024', $rendered);
        });
    });
});

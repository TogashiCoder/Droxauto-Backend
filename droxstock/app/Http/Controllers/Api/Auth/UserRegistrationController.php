<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Mail\UserRegistrationPending;
use App\Mail\UserRegistrationApproved;
use App\Mail\UserRegistrationRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserRegistrationController extends Controller
{
    /**
     * Register a new user (requires admin approval)
     */
    public function register(RegisterUserRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create user with pending status
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => false,
                'email_verified_at' => null,
                'registration_status' => 'pending',
                'registration_date' => now(),
                'admin_notes' => null,
            ]);

            // Send email to admin about pending registration
            $this->notifyAdminOfPendingRegistration($user);

            // Send confirmation email to user
            Mail::to($user->email)->send(new UserRegistrationPending($user));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Your account is pending admin approval. You will receive an email once approved.',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'status' => 'pending'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get registration status
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $user->registration_status,
                'is_active' => $user->is_active,
                'admin_notes' => $user->admin_notes,
                'registration_date' => $user->registration_date,
                'approved_at' => $user->approved_at,
                'rejected_at' => $user->rejected_at,
            ]
        ]);
    }

    /**
     * Resend verification email
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->registration_status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Account already approved'
            ], 400);
        }

        // Resend appropriate email based on status
        if ($user->registration_status === 'pending') {
            Mail::to($user->email)->send(new UserRegistrationPending($user));
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully'
        ]);
    }

    /**
     * Notify admin of pending registration
     */
    private function notifyAdminOfPendingRegistration(User $user)
    {
        // Get admin users
        $adminUsers = User::role('admin')->get();

        foreach ($adminUsers as $admin) {
            Mail::to($admin->email)->send(new UserRegistrationPending($user));
        }
    }
}

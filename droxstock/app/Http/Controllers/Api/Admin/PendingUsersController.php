<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveUserRequest;
use App\Http\Requests\Admin\RejectUserRequest;
use App\Models\User;
use App\Mail\UserRegistrationApproved;
use App\Mail\UserRegistrationRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class PendingUsersController extends Controller
{
    /**
     * Get all pending user registrations
     */
    public function index(Request $request)
    {
        $pendingUsers = User::where('registration_status', 'pending')
            ->orderBy('registration_date', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $pendingUsers->items(),
            'pagination' => [
                'current_page' => $pendingUsers->currentPage(),
                'last_page' => $pendingUsers->lastPage(),
                'per_page' => $pendingUsers->perPage(),
                'total' => $pendingUsers->total(),
                'from' => $pendingUsers->firstItem(),
                'to' => $pendingUsers->lastItem(),
            ]
        ]);
    }

    /**
     * Get pending user details
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->registration_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'User is not pending approval'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'registration_date' => $user->registration_date,
                'registration_status' => $user->registration_status,
                'admin_notes' => $user->admin_notes,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    /**
     * Approve user registration
     */
    public function approve(ApproveUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        
        if ($user->registration_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'User is not pending approval'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Update user status
            $user->update([
                'registration_status' => 'approved',
                'is_active' => true,
                'approved_at' => now(),
                'admin_notes' => $request->admin_notes,
            ]);

            // Assign basic user role if specified
            if ($request->role) {
                $user->assignRole($request->role);
            } else {
                // Assign default basic_user role
                $user->assignRole('basic_user');
            }

            // Send approval email
            Mail::to($user->email)->send(new UserRegistrationApproved($user, $request->admin_notes));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User registration approved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'status' => 'approved',
                    'approved_at' => $user->approved_at,
                    'assigned_role' => $user->getRoleNames()->first()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve user registration',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Reject user registration
     */
    public function reject(RejectUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        
        if ($user->registration_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'User is not pending approval'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Store rejection reason
            $user->update([
                'registration_status' => 'rejected',
                'rejected_at' => now(),
                'admin_notes' => $request->rejection_reason,
            ]);

            // Send rejection email
            Mail::to($user->email)->send(new UserRegistrationRejected($user, $request->rejection_reason));

            // Delete the user account
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User registration rejected and account deleted',
                'data' => [
                    'user_id' => $user->id,
                    'status' => 'rejected',
                    'rejected_at' => $user->rejected_at,
                    'rejection_reason' => $request->rejection_reason
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject user registration',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get pending users statistics
     */
    public function statistics()
    {
        $stats = [
            'total_pending' => User::where('registration_status', 'pending')->count(),
            'total_approved_today' => User::where('registration_status', 'approved')
                ->whereDate('approved_at', today())->count(),
            'total_rejected_today' => User::where('registration_status', 'rejected')
                ->whereDate('rejected_at', today())->count(),
            'pending_by_date' => User::where('registration_status', 'pending')
                ->selectRaw('DATE(registration_date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}

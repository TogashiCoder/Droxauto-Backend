<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!$user->isActive()) {
                Auth::logout();

                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated',
                    'error' => 'account_deactivated',
                    'deactivation_reason' => $user->getDeactivationReason()
                ], 403);
            }
        }

        return $next($request);
    }
}

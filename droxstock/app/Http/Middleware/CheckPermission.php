<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission, string ...$permissions): Response
    {
        $permissions = array_merge([$permission], $permissions);

        if (!$request->user() || !$request->user()->hasAnyPermission($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
                'error' => 'Insufficient permissions. Required permissions: ' . implode(', ', $permissions)
            ], 403);
        }

        return $next($request);
    }
}

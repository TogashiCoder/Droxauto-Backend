<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role, string ...$roles): Response
    {
        $roles = array_merge([$role], $roles);

        if (!$request->user() || !$request->user()->hasAnyRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
                'error' => 'Insufficient permissions. Required roles: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}

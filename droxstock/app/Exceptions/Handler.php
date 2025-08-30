<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle API requests to always return JSON
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->header('Accept') === 'application/json') {
                return $this->handleApiException($e, $request);
            }
        });
    }

    /**
     * Handle API exceptions to always return JSON
     */
    protected function handleApiException(Throwable $e, Request $request)
    {
        // Handle authentication exceptions
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => 'authentication_required',
                'error_type' => 'unauthorized'
            ], 401);
        }

        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_type' => 'validation_error'
            ], 422);
        }

        // Handle not found exceptions
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
                'error' => 'not_found',
                'error_type' => 'resource_not_found'
            ], 404);
        }

        // Handle OAuth server exceptions
        if (
            str_contains($e->getMessage(), 'OAuthServerException') ||
            str_contains($e->getMessage(), 'access token has expired') ||
            str_contains($e->getMessage(), 'authorization server denied')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired access token',
                'error' => 'token_invalid',
                'error_type' => 'authentication_failed'
            ], 401);
        }

        // Handle other exceptions
        return response()->json([
            'success' => false,
            'message' => 'Server error',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            'error_type' => 'server_error'
        ], 500);
    }
}

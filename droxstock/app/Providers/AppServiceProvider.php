<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel Sanctum will handle JWT tokens automatically

        // Configure Scramble to only include focused Daparto APIs
        Scramble::routes(function () {
            return collect(\Illuminate\Support\Facades\Route::getRoutes())
                ->filter(function ($route) {
                    $uri = $route->uri();
                    $allowedEndpoints = [
                        'v1/auth/login',
                        'v1/register/user',
                        'v1/auth/refresh',
                        'v1/auth/logout',
                        'v1/auth/me',
                        'v1/dapartos',
                        'v1/dapartos-stats',
                        'v1/dapartos-by-number/{interne_artikelnummer}'
                    ];

                    foreach ($allowedEndpoints as $endpoint) {
                        if (str_starts_with($uri, $endpoint)) {
                            return true;
                        }
                    }

                    return false;
                });
        });

        // Configure Scramble to include security schemes
        Scramble::afterOpenApiGenerated(function ($openApi) {
            $openApi->components->securitySchemes = [
                'bearerAuth' => SecurityScheme::http('bearer', 'JWT'),
            ];
        });
    }
}

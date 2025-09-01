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

        // Configure Scramble to include security schemes
        Scramble::afterOpenApiGenerated(function ($openApi) {
            $openApi->components->securitySchemes = [
                'bearerAuth' => SecurityScheme::http('bearer', 'JWT'),
            ];
        });
    }
}

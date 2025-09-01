<?php

namespace App\Scramble;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Routing\Route;

class SecurityExtension extends OperationExtension
{
    public function __construct(
        Infer $infer,
        TypeTransformer $openApiTransformer,
        GeneratorConfig $config
    ) {
        parent::__construct($infer, $openApiTransformer, $config);
    }

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        $route = $routeInfo->route;

        // Check if route has auth middleware
        if ($this->hasAuthMiddleware($route)) {
            // Add security requirement
            $operation->addSecurity(new SecurityRequirement(['bearerAuth' => []]));
        }
    }

    private function hasAuthMiddleware(Route $route): bool
    {
        $middleware = $route->middleware();

        // Check for common auth middleware patterns
        $authPatterns = [
            'auth:api',
            'auth:sanctum',
            'auth',
            'user.active'
        ];

        foreach ($middleware as $middlewareName) {
            if (in_array($middlewareName, $authPatterns)) {
                return true;
            }
        }

        return false;
    }
}

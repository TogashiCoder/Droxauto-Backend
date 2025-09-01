<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring
     */
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => []
        ];

        // Check database
        try {
            DB::connection()->getPdo();
            $health['services']['database'] = [
                'status' => 'up',
                'response_time' => $this->measureResponseTime(function() {
                    DB::select('SELECT 1');
                })
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['services']['database'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
        }

        // Check Redis
        try {
            $redisTime = $this->measureResponseTime(function() {
                Redis::ping();
            });
            $health['services']['redis'] = [
                'status' => 'up',
                'response_time' => $redisTime
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['services']['redis'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
        }

        // Check cache
        try {
            $cacheTime = $this->measureResponseTime(function() {
                Cache::put('health_check', true, 1);
                Cache::get('health_check');
            });
            $health['services']['cache'] = [
                'status' => 'up',
                'response_time' => $cacheTime
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['services']['cache'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
        }

        // Check disk space
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);

        $health['services']['disk'] = [
            'status' => $diskUsagePercent < 90 ? 'up' : 'warning',
            'usage_percent' => $diskUsagePercent,
            'free_gb' => round($diskFree / 1073741824, 2),
            'total_gb' => round($diskTotal / 1073741824, 2)
        ];

        // Check memory
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        $health['services']['memory'] = [
            'status' => 'up',
            'current_mb' => round($memoryUsage / 1048576, 2),
            'peak_mb' => round($memoryPeak / 1048576, 2)
        ];

        // Application info
        $health['application'] = [
            'name' => config('app.name'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Simple health check for load balancers
     */
    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Measure response time of a callback
     */
    private function measureResponseTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2); // Return in milliseconds
    }
}

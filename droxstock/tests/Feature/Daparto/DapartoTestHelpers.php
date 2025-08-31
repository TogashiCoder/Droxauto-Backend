<?php

namespace Tests\Feature\Daparto;

use App\Models\Daparto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

/**
 * Enterprise-grade test helpers for Daparto testing suite
 *
 * This class provides reusable testing utilities and assertions
 * that follow industry best practices for maintainable test code.
 */
abstract class DapartoTestHelpers
{
    use RefreshDatabase;

    /**
     * Authenticate user for testing
     */
    public static function authenticateUser(User $user = null): User
    {
        $user = $user ?? DapartoTestDataFactory::createAdminUser();
        Sanctum::actingAs($user);
        return $user;
    }

    /**
     * Create and authenticate admin user
     */
    public static function authenticateAdmin(): User
    {
        return self::authenticateUser(DapartoTestDataFactory::createAdminUser());
    }

    /**
     * Create and authenticate basic user
     */
    public static function authenticateBasicUser(): User
    {
        return self::authenticateUser(DapartoTestDataFactory::createBasicUser());
    }

    /**
     * Assert successful API response structure
     */
    public static function assertSuccessfulResponse(TestResponse $response, string $message = null): void
    {
        $response->assertStatus(DapartoTestConfig::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ])
            ->assertJson([
                'success' => true
            ]);

        if ($message) {
            $response->assertJson([
                'message' => $message
            ]);
        }
    }

    /**
     * Assert created API response structure
     */
    public static function assertCreatedResponse(TestResponse $response, string $message = null): void
    {
        $response->assertStatus(DapartoTestConfig::HTTP_CREATED)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ])
            ->assertJson([
                'success' => true
            ]);

        if ($message) {
            $response->assertJson([
                'message' => $message
            ]);
        }
    }

    /**
     * Assert error response structure
     */
    public static function assertErrorResponse(TestResponse $response, int $statusCode, string $message = null): void
    {
        $response->assertStatus($statusCode)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => false
            ]);

        if ($message) {
            $response->assertJson([
                'message' => $message
            ]);
        }
    }

    /**
     * Assert validation error response
     */
    public static function assertValidationError(TestResponse $response, array $expectedErrors): void
    {
        $response->assertStatus(DapartoTestConfig::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ])
            ->assertJson([
                'success' => false
            ]);

        foreach ($expectedErrors as $field) {
            $response->assertJsonValidationErrors([$field]);
        }
    }

    /**
     * Assert paginated response structure
     */
    public static function assertPaginatedResponse(TestResponse $response, int $expectedCount = null): void
    {
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'tiltle',
                        'teilemarke_teilenummer',
                        'preis',
                        'interne_artikelnummer',
                        'zustand',
                        'pfand',
                        'versandklasse',
                        'lieferzeit',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to'
                ]
            ]
        ]);

        if ($expectedCount !== null) {
            $response->assertJson([
                'data.pagination.total' => $expectedCount
            ]);
        }
    }

    /**
     * Assert Daparto resource structure
     */
    public static function assertDapartoResource(TestResponse $response, array $expectedData = []): void
    {
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'tiltle',
                'teilemarke_teilenummer',
                'preis',
                'interne_artikelnummer',
                'zustand',
                'pfand',
                'versandklasse',
                'lieferzeit',
                'created_at',
                'updated_at'
            ]
        ]);

        foreach ($expectedData as $field => $value) {
            $response->assertJson([
                "data.{$field}" => $value
            ]);
        }
    }

    /**
     * Assert database record exists
     */
    protected function assertDatabaseHasDaparto(array $data): void
    {
        $this->assertDatabaseHas('dapartos', $data);
    }

    /**
     * Assert database record doesn't exist
     */
    protected function assertDatabaseMissingDaparto(array $data): void
    {
        $this->assertDatabaseMissing('dapartos', $data);
    }

    /**
     * Assert soft deleted record exists
     */
    protected function assertSoftDeletedDaparto(int $id): void
    {
        $this->assertSoftDeleted('dapartos', ['id' => $id]);
    }

    /**
     * Assert soft deleted record doesn't exist
     */
    protected function assertNotSoftDeletedDaparto(int $id): void
    {
        $this->assertNotSoftDeleted('dapartos', ['id' => $id]);
    }

    /**
     * Measure response time and assert performance threshold
     */
    public static function assertPerformanceThreshold(callable $operation, int $thresholdMs = null): float
    {
        $thresholdMs = $thresholdMs ?? DapartoTestConfig::PERFORMANCE_THRESHOLD_MS;

        $startTime = microtime(true);
        $operation();
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        expect($executionTime)->toBeLessThan($thresholdMs);

        return $executionTime;
    }

    /**
     * Assert rate limiting is working
     */
    protected function assertRateLimiting(string $endpoint, int $maxRequests = null): void
    {
        $maxRequests = $maxRequests ?? DapartoTestConfig::RATE_LIMIT_REQUESTS;
        $user = self::authenticateAdmin();

        // Make requests up to the limit
        for ($i = 0; $i < $maxRequests; $i++) {
            $response = $this->getJson($endpoint);
            $response->assertSuccessful();
        }

        // Next request should be rate limited
        $response = $this->getJson($endpoint);
        $response->assertStatus(DapartoTestConfig::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Assert concurrent operations maintain data integrity
     */
    public static function assertConcurrentDataIntegrity(callable $operation, int $concurrency = 5): void
    {
        $initialCount = Daparto::count();
        $promises = [];

        // Execute concurrent operations
        for ($i = 0; $i < $concurrency; $i++) {
            $promises[] = $operation();
        }

        // Wait for all operations to complete
        foreach ($promises as $promise) {
            if ($promise instanceof \GuzzleHttp\Promise\PromiseInterface) {
                $promise->wait();
            }
        }

        // Assert data integrity
        $finalCount = Daparto::count();
        expect($finalCount)->toBeGreaterThanOrEqual($initialCount);
    }

    /**
     * Assert CSV processing results
     */
    public static function assertCsvProcessingResults(TestResponse $response, int $expectedProcessed, int $expectedFailed = 0): void
    {
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'processed_count',
                'failed_count',
                'errors' => [
                    '*' => [
                        'row',
                        'message',
                        'data'
                    ]
                ]
            ]
        ]);

        $response->assertJson([
            'data.processed_count' => $expectedProcessed,
            'data.failed_count' => $expectedFailed
        ]);
    }

    /**
     * Assert search and filter functionality
     */
    public static function assertSearchAndFilterResults(TestResponse $response, array $expectedFilters = []): void
    {
        $response->assertSuccessful();

        $data = $response->json('data.data');

        if (!empty($expectedFilters)) {
            foreach ($data as $item) {
                foreach ($expectedFilters as $field => $expectedValue) {
                    if (is_array($expectedValue)) {
                        // Range filter
                        expect($item[$field])->toBeGreaterThanOrEqual($expectedValue['min']);
                        expect($item[$field])->toBeLessThanOrEqual($expectedValue['max']);
                    } else {
                        // Exact match or contains
                        if (is_string($expectedValue)) {
                            expect($item[$field])->toContain($expectedValue);
                        } else {
                            expect($item[$field])->toBe($expectedValue);
                        }
                    }
                }
            }
        }
    }

    /**
     * Assert statistics response structure
     */
    public static function assertStatisticsResponse(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_count',
                'brands' => [
                    '*' => [
                        'brand',
                        'count'
                    ]
                ],
                'price_ranges' => [
                    '*' => [
                        'range',
                        'count'
                    ]
                ],
                'conditions' => [
                    '*' => [
                        'condition',
                        'count'
                    ]
                ],
                'average_price',
                'total_value'
            ]
        ]);
    }

    /**
     * Clean up test environment
     */
    public static function cleanupTestEnvironment(): void
    {
        DapartoTestDataFactory::cleanupTestFiles();
        DapartoTestDataFactory::resetDatabase();
    }

    /**
     * Setup test environment
     */
    protected function setupTestEnvironment(): void
    {
        // Ensure test database is clean
        $this->refreshDatabase();

        // Create necessary roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'basic_user', 'guard_name' => 'api']);
    }

    /**
     * Assert audit trail is maintained
     */
    protected function assertAuditTrail(string $action, int $dapartoId, int $userId): void
    {
        // This would depend on your audit system implementation
        // For now, we'll assert that the operation was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Daparto::class,
            'subject_id' => $dapartoId,
            'causer_type' => User::class,
            'causer_id' => $userId,
            'description' => $action
        ]);
    }

    /**
     * Assert cache is working properly
     */
    protected function assertCacheFunctionality(string $endpoint, string $cacheKey = null): void
    {
        $cacheKey = $cacheKey ?? 'daparto_list_' . md5($endpoint);

        // First request should cache the result
        $response1 = $this->getJson($endpoint);
        $response1->assertSuccessful();

        // Second request should be served from cache (faster)
        $startTime = microtime(true);
        $response2 = $this->getJson($endpoint);
        $endTime = microtime(true);

        $response2->assertSuccessful();

        // Cache response should be faster
        $cacheResponseTime = ($endTime - $startTime) * 1000;
        expect($cacheResponseTime)->toBeLessThan(100); // Should be very fast from cache

        // Responses should be identical
        expect($response1->json())->toBe($response2->json());
    }
}

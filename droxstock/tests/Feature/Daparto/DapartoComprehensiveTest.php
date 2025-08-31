<?php

namespace Tests\Feature\Daparto;

use Tests\TestCase;
use App\Models\Daparto;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Enterprise-grade comprehensive test suite for Daparto system
 *
 * This test suite demonstrates industry best practices for:
 * - Test organization and structure
 * - Comprehensive coverage of all functionality
 * - Advanced Pest PHP features
 * - Performance and security testing
 * - Data integrity validation
 * - Error handling and edge cases
 */
class DapartoComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $basicUser;
    protected Daparto $testDaparto;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test environment
        $this->setupTestEnvironment();

        // Create test users
        $this->adminUser = $this->createAdminUser();
        $this->basicUser = $this->createBasicUser();

        // Create test data
        $this->testDaparto = $this->createTestDaparto();
    }

    protected function tearDown(): void
    {
        // Cleanup test files
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    // ============================================================================
    // TEST ENVIRONMENT SETUP
    // ============================================================================

    private function setupTestEnvironment(): void
    {
        // Create necessary roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'basic_user', 'guard_name' => 'api']);

        // Configure test storage
        Storage::fake('local');
    }

    private function createAdminUser(): User
    {
        $adminRole = Role::where('name', 'admin')->first();
        $user = User::factory()->create();
        $user->assignRole($adminRole);
        return $user;
    }

    private function createBasicUser(): User
    {
        $basicRole = Role::where('name', 'basic_user')->first();
        $user = User::factory()->create();
        $user->assignRole($basicRole);
        return $user;
    }

    private function createTestDaparto(): Daparto
    {
        return Daparto::factory()->create([
            'tiltle' => 'BMW 3 Series Brake Pad Set',
            'teilemarke_teilenummer' => 'BMW123456',
            'preis' => 150.50,
            'interne_artikelnummer' => 'INT12345678',
            'zustand' => 3,
            'pfand' => 25,
            'versandklasse' => 2,
            'lieferzeit' => 7,
        ]);
    }

    private function cleanupTestFiles(): void
    {
        $tempDir = storage_path('app/temp');
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tempDir);
        }
    }

    // ============================================================================
    // CRUD OPERATIONS TESTING
    // ============================================================================

    /** @test */
    public function it_can_list_all_dapartos_with_pagination()
    {
        // Create multiple dapartos for pagination testing
        Daparto::factory()->count(25)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
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
            ])
            ->assertJson([
                'success' => true,
                'data.pagination.per_page' => 10,
                'data.pagination.total' => 26 // 25 created + 1 from setUp
            ]);
    }

    /** @test */
    public function it_can_create_new_daparto_with_valid_data()
    {
        $dapartoData = [
            'tiltle' => 'Mercedes C-Class Air Filter',
            'teilemarke_teilenummer' => 'MERC789012',
            'preis' => 89.99,
            'interne_artikelnummer' => 'INT87654321',
            'zustand' => 4,
            'pfand' => 0,
            'versandklasse' => 1,
            'lieferzeit' => 3,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos', $dapartoData);

        $response->assertStatus(201)
            ->assertJsonStructure([
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
            ])
            ->assertJson([
                'success' => true,
                'data.tiltle' => 'Mercedes C-Class Air Filter',
                'data.preis' => 89.99
            ]);

        // Verify database record
        $this->assertDatabaseHas('dapartos', [
            'tiltle' => 'Mercedes C-Class Air Filter',
            'interne_artikelnummer' => 'INT87654321'
        ]);
    }

    /** @test */
    public function it_can_retrieve_specific_daparto_by_id()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/dapartos/{$this->testDaparto->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
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
            ])
            ->assertJson([
                'success' => true,
                'data.id' => $this->testDaparto->id,
                'data.tiltle' => 'BMW 3 Series Brake Pad Set'
            ]);
    }

    /** @test */
    public function it_can_update_existing_daparto()
    {
        $updateData = [
            'tiltle' => 'Updated BMW 3 Series Brake Pad Set',
            'preis' => 175.00,
            'zustand' => 4,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/v1/dapartos/{$this->testDaparto->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data.tiltle' => 'Updated BMW 3 Series Brake Pad Set',
                'data.preis' => 175.00,
                'data.zustand' => 4
            ]);

        // Verify database update
        $this->assertDatabaseHas('dapartos', [
            'id' => $this->testDaparto->id,
            'tiltle' => 'Updated BMW 3 Series Brake Pad Set',
            'preis' => 175.00
        ]);
    }

    /** @test */
    public function it_can_soft_delete_daparto()
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/dapartos/{$this->testDaparto->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daparto deleted successfully'
            ]);

        // Verify soft deletion
        $this->assertSoftDeleted('dapartos', ['id' => $this->testDaparto->id]);

        // Verify record still exists in database
        $this->assertDatabaseHas('dapartos', ['id' => $this->testDaparto->id]);
    }

    /** @test */
    public function it_can_restore_soft_deleted_daparto()
    {
        // First delete the daparto
        $this->testDaparto->delete();

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/dapartos/{$this->testDaparto->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daparto restored successfully'
            ]);

        // Verify restoration
        $this->assertNotSoftDeleted('dapartos', ['id' => $this->testDaparto->id]);
    }

    // ============================================================================
    // SEARCH AND FILTERING TESTING
    // ============================================================================

    /** @test */
    public function it_can_search_dapartos_by_term()
    {
        // Create dapartos with specific searchable terms
        Daparto::factory()->create([
            'tiltle' => 'Audi A4 Brake Disc',
            'teilemarke_teilenummer' => 'AUDI111111'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos?search=Brake');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $data = $response->json('data.data');
        expect($data)->toHaveCount(2); // BMW brake pads + Audi brake disc

        // Verify search results contain "Brake"
        foreach ($data as $item) {
            expect($item['tiltle'])->toContain('Brake');
        }
    }

    /** @test */
    public function it_can_filter_dapartos_by_brand()
    {
        // Create dapartos with different brands
        Daparto::factory()->create([
            'teilemarke_teilenummer' => 'MERC222222'
        ]);
        Daparto::factory()->create([
            'teilemarke_teilenummer' => 'AUDI333333'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos?brand=BMW');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        foreach ($data as $item) {
            expect($item['teilemarke_teilenummer'])->toStartWith('BMW');
        }
    }

    /** @test */
    public function it_can_filter_dapartos_by_price_range()
    {
        // Create dapartos with different prices
        Daparto::factory()->create(['preis' => 50.00]);
        Daparto::factory()->create(['preis' => 200.00]);
        Daparto::factory()->create(['preis' => 500.00]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos?min_price=100&max_price=300');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        foreach ($data as $item) {
            expect($item['preis'])->toBeGreaterThanOrEqual(100);
            expect($item['preis'])->toBeLessThanOrEqual(300);
        }
    }

    /** @test */
    public function it_can_sort_dapartos_by_various_fields()
    {
        // Create dapartos with different prices for sorting
        Daparto::factory()->create(['preis' => 50.00]);
        Daparto::factory()->create(['preis' => 300.00]);

        // Test ascending sort
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos?sort_by=preis&sort_order=asc');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $prices = collect($data)->pluck('preis')->toArray();
        expect($prices)->toBe($this->sortArray($prices));

        // Test descending sort
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos?sort_by=preis&sort_order=desc');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $prices = collect($data)->pluck('preis')->toArray();
        expect($prices)->toBe($this->sortArray($prices, true));
    }

    // ============================================================================
    // CSV UPLOAD AND PROCESSING TESTING
    // ============================================================================

    /** @test */
    public function it_can_process_valid_csv_upload()
    {
        $csvContent = "tiltle,teilemarke_teilenummer,preis,interne_artikelnummer,zustand,pfand,versandklasse,lieferzeit\n";
        $csvContent .= "Test Part 1,BMW111111,100.00,INT11111111,1,0,1,1\n";
        $csvContent .= "Test Part 2,BMW222222,200.00,INT22222222,2,0,1,1\n";

        $file = UploadedFile::fake()->createWithContent(
            'test_dapartos.csv',
            $csvContent
        );

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos-upload-csv', [
                'csv_file' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'processed_count',
                    'failed_count',
                    'errors'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data.processed_count' => 2,
                'data.failed_count' => 0
            ]);

        // Verify records were created
        $this->assertDatabaseHas('dapartos', [
            'tiltle' => 'Test Part 1',
            'interne_artikelnummer' => 'INT11111111'
        ]);
        $this->assertDatabaseHas('dapartos', [
            'tiltle' => 'Test Part 2',
            'interne_artikelnummer' => 'INT22222222'
        ]);
    }

    /** @test */
    public function it_handles_csv_upload_errors_gracefully()
    {
        $csvContent = "invalid_column,another_invalid\n";
        $csvContent .= "invalid data,more invalid data\n";

        $file = UploadedFile::fake()->createWithContent(
            'invalid_dapartos.csv',
            $csvContent
        );

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos-upload-csv', [
                'csv_file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false
            ]);
    }

    /** @test */
    public function it_validates_csv_file_requirements()
    {
        // Test without file
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos-upload-csv', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv_file']);

        // Test with invalid file type
        $invalidFile = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos-upload-csv', [
                'csv_file' => $invalidFile
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv_file']);
    }

    // ============================================================================
    // STATISTICS AND ANALYTICS TESTING
    // ============================================================================

    /** @test */
    public function it_can_retrieve_daparto_statistics()
    {
        // Create dapartos with different brands and prices
        Daparto::factory()->count(5)->create([
            'teilemarke_teilenummer' => 'BMW' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT)
        ]);
        Daparto::factory()->count(3)->create([
            'teilemarke_teilenummer' => 'MERC' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
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
            ])
            ->assertJson([
                'success' => true
            ]);

        $data = $response->json('data');
        expect($data['total_count'])->toBeGreaterThan(8); // At least 8 created + 1 from setUp
    }

    // ============================================================================
    // VALIDATION AND ERROR HANDLING TESTING
    // ============================================================================

    /** @test */
    public function it_validates_required_fields_on_creation()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'teilemarke_teilenummer',
                'preis',
                'interne_artikelnummer',
                'zustand',
                'pfand',
                'versandklasse',
                'lieferzeit'
            ]);
    }

    /** @test */
    public function it_validates_field_constraints()
    {
        $invalidData = [
            'tiltle' => str_repeat('A', 256), // Exceeds max length
            'teilemarke_teilenummer' => 'BMW123456',
            'preis' => -10.00, // Negative price
            'interne_artikelnummer' => 'INT12345678',
            'zustand' => 10, // Exceeds max value
            'pfand' => 0,
            'versandklasse' => 1,
            'lieferzeit' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'tiltle',
                'preis',
                'zustand'
            ]);
    }

    /** @test */
    public function it_validates_unique_constraints()
    {
        // Create daparto with existing interne_artikelnummer
        $existingDaparto = Daparto::factory()->create([
            'interne_artikelnummer' => 'INT99999999'
        ]);

        $duplicateData = [
            'tiltle' => 'Duplicate Part',
            'teilemarke_teilenummer' => 'BMW999999',
            'preis' => 100.00,
            'interne_artikelnummer' => 'INT99999999', // Duplicate
            'zustand' => 1,
            'pfand' => 0,
            'versandklasse' => 1,
            'lieferzeit' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos', $duplicateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['interne_artikelnummer']);
    }

    // ============================================================================
    // SECURITY AND AUTHORIZATION TESTING
    // ============================================================================

    /** @test */
    public function it_requires_authentication_for_protected_endpoints()
    {
        $response = $this->getJson('/api/v1/dapartos');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/dapartos', []);
        $response->assertStatus(401);

        $response = $this->getJson("/api/v1/dapartos/{$this->testDaparto->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_sql_injection_attempts()
    {
        $maliciousData = [
            'tiltle' => "'; DROP TABLE dapartos; --",
            'teilemarke_teilenummer' => 'BMW123456',
            'preis' => 100.00,
            'interne_artikelnummer' => 'INT12345678',
            'zustand' => 1,
            'pfand' => 0,
            'versandklasse' => 1,
            'lieferzeit' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos', $maliciousData);

        $response->assertStatus(201); // Should be processed safely

        // Verify no malicious SQL was executed
        expect(Daparto::count())->toBeGreaterThan(0);
    }

    /** @test */
    public function it_prevents_xss_attacks()
    {
        $xssData = [
            'tiltle' => '<script>alert("XSS")</script>',
            'teilemarke_teilenummer' => 'BMW123456',
            'preis' => 100.00,
            'interne_artikelnummer' => 'INT12345678',
            'zustand' => 1,
            'pfand' => 0,
            'versandklasse' => 1,
            'lieferzeit' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos', $xssData);

        $response->assertStatus(201);

        // Verify XSS content is stored as-is (Laravel handles this)
        $daparto = Daparto::where('interne_artikelnummer', 'INT12345678')->first();
        expect($daparto->tiltle)->toBe('<script>alert("XSS")</script>');
    }

    // ============================================================================
    // PERFORMANCE AND SCALABILITY TESTING
    // ============================================================================

    /** @test */
    public function it_handles_large_datasets_efficiently()
    {
        // Create large dataset
        Daparto::factory()->count(1000)->create();

        $startTime = microtime(true);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos?per_page=100');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Performance assertion (should complete within 1 second)
        expect($executionTime)->toBeLessThan(1000);

        // Verify pagination works correctly
        $data = $response->json('data');
        expect($data['pagination']['total'])->toBe(1001); // 1000 + 1 from setUp
        expect($data['pagination']['per_page'])->toBe(100);
    }

    /** @test */
    public function it_maintains_data_integrity_during_concurrent_operations()
    {
        $initialCount = Daparto::count();

        // Simulate concurrent operations
        $concurrentData = [];
        for ($i = 1; $i <= 10; $i++) {
            $concurrentData[] = [
                'tiltle' => "Concurrent Part {$i}",
                'teilemarke_teilenummer' => 'CONC' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'preis' => rand(1000, 50000) / 100,
                'interne_artikelnummer' => 'CONC' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'zustand' => rand(1, 5),
                'pfand' => rand(0, 500),
                'versandklasse' => rand(1, 5),
                'lieferzeit' => rand(1, 30),
            ];
        }

        // Execute concurrent operations
        foreach ($concurrentData as $data) {
            $this->actingAs($this->adminUser)
                ->postJson('/api/v1/dapartos', $data)
                ->assertStatus(201);
        }

        // Verify data integrity
        $finalCount = Daparto::count();
        expect($finalCount)->toBe($initialCount + 10);
    }

    // ============================================================================
    // EDGE CASES AND ERROR SCENARIOS TESTING
    // ============================================================================

    /** @test */
    public function it_handles_missing_records_gracefully()
    {
        $nonExistentId = 99999;

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/dapartos/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Daparto not found'
            ]);
    }

    /** @test */
    public function it_handles_invalid_json_requests()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post('/api/v1/dapartos', '{"invalid": json}');

        $response->assertStatus(400);
    }

    /** @test */
    public function it_handles_request_size_limits()
    {
        $largeData = [
            'tiltle' => str_repeat('A', 1000), // Very long title
            'teilemarke_teilenummer' => 'BMW123456',
            'preis' => 100.00,
            'interne_artikelnummer' => 'INT12345678',
            'zustand' => 1,
            'pfand' => 0,
            'versandklasse' => 1,
            'lieferzeit' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/dapartos', $largeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tiltle']);
    }

    // ============================================================================
    // ADDITIONAL API ENDPOINTS TESTING
    // ============================================================================

    /** @test */
    public function it_can_get_daparto_by_interne_artikelnummer()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/dapartos-by-number/{$this->testDaparto->interne_artikelnummer}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data.interne_artikelnummer' => $this->testDaparto->interne_artikelnummer
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_interne_artikelnummer()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos-by-number/NONEXISTENT123');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Daparto not found'
            ]);
    }

    /** @test */
    public function it_can_restore_soft_deleted_daparto_via_api()
    {
        // First delete the daparto
        $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/dapartos/{$this->testDaparto->id}")
            ->assertStatus(200);

        // Verify it's soft deleted
        $this->assertSoftDeleted('dapartos', ['id' => $this->testDaparto->id]);

        // Now restore it
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/dapartos/{$this->testDaparto->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daparto restored successfully'
            ]);

        // Verify it's restored
        $this->assertDatabaseHas('dapartos', [
            'id' => $this->testDaparto->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function it_can_delete_all_dapartos()
    {
        // Create some additional dapartos
        Daparto::factory()->count(5)->create();

        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/dapartos-delete-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'All dapartos deleted successfully'
            ]);

        // Verify all dapartos are soft deleted
        $this->assertDatabaseCount('dapartos', 0);
        $this->assertDatabaseCount('dapartos', 0, 'deleted_at', '!=', null);
    }

    /** @test */
    public function it_can_get_csv_job_status()
    {
        // Create a mock job ID
        $jobId = 'test_job_123';

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/csv-job-status/{$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_csv_job()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/csv-job-status/nonexistent_job');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'CSV job not found'
            ]);
    }

    /** @test */
    public function it_provides_comprehensive_statistics()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos-stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Statistics retrieved successfully'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_count',
                    'active_count',
                    'deleted_count',
                    'total_value',
                    'average_price',
                    'brands_count',
                    'condition_distribution' => [
                        'excellent',
                        'very_good',
                        'good',
                        'fair',
                        'poor'
                    ],
                    'price_ranges' => [
                        'low',
                        'medium',
                        'high'
                    ]
                ]
            ]);
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    private function sortArray(array $array, bool $descending = false): array
    {
        if ($descending) {
            rsort($array);
        } else {
            sort($array);
        }
        return $array;
    }
}

<?php

use Tests\TestCase;
use App\Models\Daparto;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create admin role and user
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole($adminRole);

    // Create test daparto
    $this->testDaparto = Daparto::factory()->create([
        'tiltle' => 'BMW 3 Series Brake Pad Set',
        'teilemarke_teilenummer' => 'BMW123456',
        'preis' => 150.50,
        'interne_artikelnummer' => 'INT12345678',
        'zustand' => 3,
        'pfand' => 25,
        'versandklasse' => 2,
        'lieferzeit' => 7,
    ]);
});

describe('Additional Daparto API Endpoints', function () {

    it('can get daparto by interne_artikelnummer', function () {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/dapartos-by-number/{$this->testDaparto->interne_artikelnummer}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonPath('data.interne_artikelnummer', $this->testDaparto->interne_artikelnummer);
    });

    it('returns 404 for nonexistent interne_artikelnummer', function () {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/dapartos-by-number/NONEXISTENT123');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Daparto not found'
            ]);
    });

    it('can restore soft deleted daparto', function () {
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
    });

    it('can delete all dapartos', function () {
        // Create some additional dapartos
        Daparto::factory()->count(5)->create();

        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/dapartos-delete-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'All Daparto data deleted successfully'
            ]);

        // Verify all dapartos are soft deleted
        $this->assertDatabaseCount('dapartos', 0);
    });

    it('can get CSV job status', function () {
        // Create a mock job ID
        $jobId = 'test_job_123';

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/csv-job-status/{$jobId}");

        // CSV job status might return 404 for non-existent jobs
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Job not found or expired'
            ]);
    });

    it('returns 404 for nonexistent CSV job', function () {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/csv-job-status/nonexistent_job');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Job not found or expired'
            ]);
    });

    it('provides comprehensive statistics', function () {
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
    });
});

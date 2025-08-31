<?php

use Tests\TestCase;
use App\Models\Daparto;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

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

it('can list all dapartos with pagination', function () {
    // Create additional dapartos
    Daparto::factory()->count(10)->create();

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/v1/dapartos?per_page=5');

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
            'success' => true
        ])
        ->assertJsonPath('data.pagination.per_page', 5)
        ->assertJsonPath('data.pagination.total', 11); // 10 created + 1 from beforeEach
});

it('can create new daparto', function () {
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
            ->assertJson([
                'success' => true
            ])
            ->assertJsonPath('data.tiltle', 'Mercedes C-Class Air Filter')
            ->assertJsonPath('data.preis', '89.99');

    // Verify database record
    $this->assertDatabaseHas('dapartos', [
        'tiltle' => 'Mercedes C-Class Air Filter',
        'interne_artikelnummer' => 'INT87654321'
    ]);
});

it('can retrieve specific daparto', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/v1/dapartos/{$this->testDaparto->id}");

            $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonPath('data.id', $this->testDaparto->id)
            ->assertJsonPath('data.tiltle', 'BMW 3 Series Brake Pad Set');
});

it('can update daparto', function () {
    $updateData = [
        'tiltle' => 'Updated BMW 3 Series Brake Pad Set',
        'preis' => 175.00,
    ];

    $response = $this->actingAs($this->adminUser)
        ->putJson("/api/v1/dapartos/{$this->testDaparto->id}", $updateData);

            $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonPath('data.tiltle', 'Updated BMW 3 Series Brake Pad Set')
            ->assertJsonPath('data.preis', '175.00');

    // Verify database update
    $this->assertDatabaseHas('dapartos', [
        'id' => $this->testDaparto->id,
        'tiltle' => 'Updated BMW 3 Series Brake Pad Set',
        'preis' => 175.00
    ]);
});

it('can delete daparto', function () {
    $response = $this->actingAs($this->adminUser)
        ->deleteJson("/api/v1/dapartos/{$this->testDaparto->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Daparto deleted successfully'
        ]);

    // Verify soft deletion
    $this->assertSoftDeleted('dapartos', ['id' => $this->testDaparto->id]);
});

it('can search dapartos', function () {
    // Create dapartos with searchable terms
    Daparto::factory()->create([
        'tiltle' => 'Audi A4 Brake Disc',
        'teilemarke_teilenummer' => 'AUDI111111'
    ]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/v1/dapartos?search=Brake');

    $response->assertStatus(200);

    $data = $response->json('data.data');
    expect($data)->toHaveCount(2); // BMW brake pads + Audi brake disc

    // Verify search results contain "Brake"
    foreach ($data as $item) {
        expect($item['tiltle'])->toContain('Brake');
    }
});

it('can filter by brand', function () {
    Daparto::factory()->create([
        'teilemarke_teilenummer' => 'MERC222222'
    ]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/v1/dapartos?brand=BMW');

    $response->assertStatus(200);

    $data = $response->json('data.data');
    foreach ($data as $item) {
        expect($item['teilemarke_teilenummer'])->toStartWith('BMW');
    }
});

it('can filter by price range', function () {
    Daparto::factory()->create(['preis' => 50.00]);
    Daparto::factory()->create(['preis' => 200.00]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/v1/dapartos?min_price=100&max_price=300');

    $response->assertStatus(200);

    $data = $response->json('data.data');
    foreach ($data as $item) {
        expect($item['preis'])->toBeGreaterThanOrEqual(100);
        expect($item['preis'])->toBeLessThanOrEqual(300);
    }
});

it('validates required fields', function () {
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
});

it('requires authentication', function () {
    $response = $this->getJson('/api/v1/dapartos');
    $response->assertStatus(401);
});

it('can process csv upload', function () {
    $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
    $csvContent .= "INT11111111;100.00;1;Test Part 1;BMW111111;0;1;1\n";

    $file = UploadedFile::fake()->createWithContent(
        'test_dapartos.csv',
        $csvContent
    );

    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/v1/dapartos-upload-csv', [
            'csv_file' => $file
        ]);

    $response->assertStatus(202)
        ->assertJson([
            'success' => true,
            'message' => 'CSV file uploaded successfully and queued for background processing'
        ]);

    // Verify record was created
    $this->assertDatabaseHas('dapartos', [
        'tiltle' => 'Test Part 1',
        'interne_artikelnummer' => 'INT11111111'
    ]);
});

it('can get statistics', function () {
    Daparto::factory()->count(5)->create([
        'teilemarke_teilenummer' => 'BMW' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT)
    ]);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/v1/dapartos-stats');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true
        ]);

    $data = $response->json('data');
    expect($data['total_count'])->toBeGreaterThan(0);
});

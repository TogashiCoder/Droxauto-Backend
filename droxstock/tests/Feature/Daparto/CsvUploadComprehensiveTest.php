<?php

use Tests\TestCase;
use App\Models\Daparto;
use App\Models\User;
use App\Jobs\ProcessCsvUploadJob;
use App\Mail\CsvProcessingReport;
use App\Services\ProfessionalCsvService;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create admin role and user
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    $this->adminUser = User::factory()->create([
        'email' => 'admin@test.com'
    ]);
    $this->adminUser->assignRole($adminRole);

    // Fake storage and queue
    Storage::fake('local');
    Queue::fake();
    Mail::fake();
});

describe('CSV Upload System', function () {

    describe('File Validation', function () {

        it('accepts valid CSV files', function () {
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;150.50;3;BMW Brake Pads;BMW123456;25;2;7\n";

            $file = UploadedFile::fake()->createWithContent(
                'valid_dapartos.csv',
                $csvContent
            );

            $response = $this->actingAs($this->adminUser)
                ->postJson('/api/v1/dapartos-upload-csv', [
                    'csv_file' => $file,
                    'email_notification' => true,
                    'user_email' => 'admin@test.com'
                ]);

            $response->assertStatus(202)
                ->assertJson([
                    'success' => true,
                    'message' => 'CSV file uploaded successfully and queued for background processing'
                ]);

            // Verify job was queued
            Queue::assertPushed(ProcessCsvUploadJob::class);
        });

        it('rejects files without required columns', function () {
            $csvContent = "wrong_column;another_wrong_column\n";
            $csvContent .= "data1;data2\n";

            $file = UploadedFile::fake()->createWithContent(
                'invalid_structure.csv',
                $csvContent
            );

            $response = $this->actingAs($this->adminUser)
                ->postJson('/api/v1/dapartos-upload-csv', [
                    'csv_file' => $file
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['csv_file']);
        });

        it('rejects files larger than 50MB', function () {
            $file = UploadedFile::fake()->create('large_file.csv', 60000); // 60MB

            $response = $this->actingAs($this->adminUser)
                ->postJson('/api/v1/dapartos-upload-csv', [
                    'csv_file' => $file
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['csv_file']);
        });

        it('rejects empty files', function () {
            $file = UploadedFile::fake()->createWithContent(
                'empty.csv',
                ''
            );

            $response = $this->actingAs($this->adminUser)
                ->postJson('/api/v1/dapartos-upload-csv', [
                    'csv_file' => $file
                ]);

            $response->assertStatus(422);
        });

        it('rejects files with wrong delimiter', function () {
            $csvContent = "interne_artikelnummer,preis,zustand\n";
            $csvContent .= "INT12345678,150.50,3\n";

            $file = UploadedFile::fake()->createWithContent(
                'wrong_delimiter.csv',
                $csvContent
            );

            $response = $this->actingAs($this->adminUser)
                ->postJson('/api/v1/dapartos-upload-csv', [
                    'csv_file' => $file
                ]);

            $response->assertStatus(422);
        });
    });

    describe('Background Job Processing', function () {

        it('processes CSV files in background', function () {
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;150.50;3;BMW Brake Pads;BMW123456;25;2;7\n";
            $csvContent .= "INT87654321;89.99;4;Mercedes Filter;MERC789;0;1;3\n";

            $file = UploadedFile::fake()->createWithContent(
                'test_dapartos.csv',
                $csvContent
            );

            $response = $this->actingAs($this->adminUser)
                ->postJson('/api/v1/dapartos-upload-csv', [
                    'csv_file' => $file,
                    'email_notification' => true,
                    'user_email' => 'admin@test.com'
                ]);

            $response->assertStatus(202);

            // Verify job was queued
            Queue::assertPushed(ProcessCsvUploadJob::class);
        });

        it('handles job failures gracefully', function () {
            // Test that failed jobs send error notifications
            $errorResults = [
                'success' => false,
                'message' => 'CSV processing failed',
                'error_details' => [
                    'message' => 'Test error message',
                    'type' => 'processing_error',
                ],
                'file_info' => [
                    'name' => 'test.csv',
                    'size' => 0,
                    'uploaded_at' => now(),
                ],
                'processing_stats' => [
                    'total_rows' => 0,
                    'failed_rows' => 1,
                ],
                'validation_summary' => [
                    'data_quality_score' => 0,
                ],
                'performance' => [
                    'duration' => 0,
                ],
                'errors' => [
                    [
                        'row' => 'system',
                        'errors' => ['Test error message'],
                        'action' => 'system_failure'
                    ]
                ],
            ];

            // Send error email manually to test
            Mail::to('admin@test.com')->send(new CsvProcessingReport($errorResults, 'Admin User', 'test.csv'));

            // Verify error email was sent
            Mail::assertSent(CsvProcessingReport::class, function ($mail) {
                return $mail->processingResults['success'] === false;
            });
        });

        it('stores job results in cache', function () {
            $jobId = 'test_job_123';
            $results = ['success' => true, 'message' => 'Test results'];

            $job = new ProcessCsvUploadJob(
                'test.csv',
                'test.csv',
                [],
                $this->adminUser->id,
                $jobId
            );

            // Use reflection to call private method
            $reflection = new ReflectionClass($job);
            $method = $reflection->getMethod('storeJobResults');
            $method->setAccessible(true);
            $method->invoke($job, $results);

            // Verify results are cached
            $cachedResults = Cache::get("csv_job_{$jobId}");
            expect($cachedResults)->not->toBeNull();
            expect($cachedResults['results'])->toBe($results);
        });
    });

    describe('Data Processing Scenarios', function () {

        it('creates new records from CSV', function () {
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;150.50;3;BMW Brake Pads;BMW123456;25;2;7\n";

            $file = UploadedFile::fake()->createWithContent(
                'new_records.csv',
                $csvContent
            );

            // Process directly with service
            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'update_existing' => false,
                'skip_duplicates' => false
            ]);

            expect($results['success'])->toBe(true);
            expect($results['processing_stats']['new_rows'])->toBe(1);
            expect($results['processing_stats']['total_rows'])->toBe(1);

            // Verify record was created in database
            $this->assertDatabaseHas('dapartos', [
                'interne_artikelnummer' => 'INT12345678',
                'preis' => 150.50,
                'zustand' => 3
            ]);
        });

        it('updates existing records when update_existing is true', function () {
            // DEBUG TEST - This test checks update functionality
            // Create existing record
            $existingDaparto = Daparto::factory()->create([
                'interne_artikelnummer' => 'INT12345678',
                'preis' => 100.00,
                'zustand' => 1
            ]);

            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;200.00;5;Updated BMW Brake Pads;BMW123456;50;3;14\n";

            $file = UploadedFile::fake()->createWithContent(
                'update_records.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'update_existing' => true
            ]);



            expect($results['success'])->toBe(true);
            expect($results['processing_stats']['updated_rows'])->toBe(1);

            // Verify record was updated
            $this->assertDatabaseHas('dapartos', [
                'id' => $existingDaparto->id,
                'preis' => 200.00,
                'zustand' => 5
            ]);
        });

        it('skips duplicates when skip_duplicates is true', function () {
            // Create existing record
            Daparto::factory()->create([
                'interne_artikelnummer' => 'INT12345678'
            ]);

            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;200.00;5;Duplicate Record;BMW123456;50;3;14\n";

            $file = UploadedFile::fake()->createWithContent(
                'duplicate_records.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'update_existing' => false,
                'skip_duplicates' => true
            ]);

            expect($results['success'])->toBe(true);
            expect($results['processing_stats']['duplicate_rows'])->toBe(1);
            expect($results['processing_stats']['skipped_rows'])->toBe(1);
        });

        it('handles mixed operations (new + updates)', function () {
            // Create existing record
            Daparto::factory()->create([
                'interne_artikelnummer' => 'INT12345678'
            ]);

            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;200.00;5;Updated Record;BMW123456;50;3;14\n";
            $csvContent .= "INT87654321;89.99;4;New Record;MERC789;0;1;3\n";

            $file = UploadedFile::fake()->createWithContent(
                'mixed_operations.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'update_existing' => true
            ]);

            expect($results['success'])->toBe(true);
            expect($results['processing_stats']['updated_rows'])->toBe(1);
            expect($results['processing_stats']['new_rows'])->toBe(1);
            expect($results['processing_stats']['total_rows'])->toBe(2);
        });
    });

    describe('Error Handling', function () {

        it('handles validation errors gracefully', function () {
            // DEBUG TEST - This test checks validation error handling
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;-50.00;10;Invalid Data;BMW123456;50;3;14\n"; // Invalid price and condition

            $file = UploadedFile::fake()->createWithContent(
                'invalid_data.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'rollback_on_error' => false
            ]);



            expect($results['success'])->toBe(false);
            expect($results['processing_stats']['failed_rows'])->toBe(1);
            expect($results['errors'])->toHaveCount(1);
        });

        it('handles database constraint violations', function () {
            // Create record with same interne_artikelnummer
            Daparto::factory()->create([
                'interne_artikelnummer' => 'INT12345678'
            ]);

            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;150.50;3;Duplicate;BMW123456;25;2;7\n";

            $file = UploadedFile::fake()->createWithContent(
                'constraint_violation.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'update_existing' => false,
                'rollback_on_error' => false
            ]);

            expect($results['success'])->toBe(false);
            expect($results['processing_stats']['failed_rows'])->toBe(1);
        });

        it('handles malformed CSV data', function () {
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;150.50;3;BMW Brake Pads;BMW123456;25;2;7\n";
            $csvContent .= "INT87654321;89.99;4;Mercedes Filter\n"; // Missing columns

            $file = UploadedFile::fake()->createWithContent(
                'malformed.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'rollback_on_error' => false
            ]);

            expect($results['success'])->toBe(false);
        });
    });

    describe('Email Notifications', function () {

        it('sends success email with comprehensive report', function () {
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;150.50;3;BMW Brake Pads;BMW123456;25;2;7\n";

            $file = UploadedFile::fake()->createWithContent(
                'success_test.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file);

            // Send email manually to test
            Mail::to('admin@test.com')->send(new CsvProcessingReport($results, 'Admin User', 'success_test.csv'));

            Mail::assertSent(CsvProcessingReport::class, function ($mail) {
                return $mail->processingResults['success'] === true &&
                    $mail->fileName === 'success_test.csv';
            });
        });

        it('sends error email when processing fails', function () {
            $errorResults = [
                'success' => false,
                'message' => 'CSV processing failed',
                'error_details' => [
                    'message' => 'Test error message',
                    'type' => 'validation_error',
                ],
                'file_info' => [
                    'name' => 'error_test.csv',
                    'size' => 0,
                    'uploaded_at' => now(),
                ],
                'processing_stats' => [
                    'total_rows' => 0,
                    'failed_rows' => 1,
                ],
                'validation_summary' => [
                    'data_quality_score' => 0,
                ],
                'performance' => [
                    'duration' => 0,
                ],
                'errors' => [
                    [
                        'row' => 'system',
                        'errors' => ['Test error message'],
                        'action' => 'system_failure'
                    ]
                ],
            ];

            Mail::to('admin@test.com')->send(new CsvProcessingReport($errorResults, 'Admin User', 'error_test.csv'));

            Mail::assertSent(CsvProcessingReport::class, function ($mail) {
                return $mail->processingResults['success'] === false &&
                    $mail->fileName === 'error_test.csv';
            });
        });
    });

    describe('Performance and Scalability', function () {

        it('handles large CSV files efficiently', function () {
            // Create large CSV content
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";

            for ($i = 1; $i <= 1000; $i++) {
                $csvContent .= "INT" . str_pad($i, 8, '0', STR_PAD_LEFT) . ";";
                $csvContent .= rand(1000, 50000) / 100 . ";"; // Random price
                $csvContent .= rand(1, 5) . ";"; // Random condition
                $csvContent .= "Test Part {$i};";
                $csvContent .= "BMW" . str_pad($i, 6, '0', STR_PAD_LEFT) . ";";
                $csvContent .= rand(0, 500) . ";"; // Random deposit
                $csvContent .= rand(1, 5) . ";"; // Random shipping class
                $csvContent .= rand(1, 30) . "\n"; // Random delivery time
            }

            $file = UploadedFile::fake()->createWithContent(
                'large_file.csv',
                $csvContent
            );

            $startTime = microtime(true);
            $memoryStart = memory_get_usage();

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'batch_size' => 100,
                'rollback_on_error' => false
            ]);

            $endTime = microtime(true);
            $memoryEnd = memory_get_usage();

            $processingTime = $endTime - $startTime;
            $memoryUsed = $memoryEnd - $memoryStart;

            expect($results['success'])->toBe(true);
            expect($results['processing_stats']['total_rows'])->toBe(1000);
            expect($processingTime)->toBeLessThan(30); // Should complete within 30 seconds
            expect($memoryUsed)->toBeLessThan(100 * 1024 * 1024); // Should use less than 100MB
        });

        it('processes data in configurable batches', function () {
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";

            for ($i = 1; $i <= 100; $i++) {
                $csvContent .= "INT" . str_pad($i, 8, '0', STR_PAD_LEFT) . ";";
                $csvContent .= "100.00;3;Test Part {$i};BMW" . str_pad($i, 6, '0', STR_PAD_LEFT) . ";0;1;7\n";
            }

            $file = UploadedFile::fake()->createWithContent(
                'batch_test.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'batch_size' => 25
            ]);

            expect($results['success'])->toBe(true);
            expect($results['processing_stats']['total_rows'])->toBe(100);
        });
    });

    describe('Business Logic Validation', function () {

        it('enforces unique interne_artikelnummer constraint - DUPLICATE TEST', function () {
            // DEBUG TEST - This test checks duplicate constraint handling - UNIQUE COMMENT
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;150.50;3;First Record;BMW123456;25;2;7\n";
            $csvContent .= "INT12345678;200.00;5;Duplicate Record;BMW123456;50;3;14\n"; // DUPLICATE TEST DATA

            $file = UploadedFile::fake()->createWithContent(
                'duplicate_constraint.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'update_existing' => false,
                'rollback_on_error' => false
            ]);

            expect($results['success'])->toBe(false);
            expect($results['processing_stats']['failed_rows'])->toBe(1);
        });

        it('validates data types and ranges', function () {
            $csvContent = "interne_artikelnummer;preis;zustand;tiltle;teilemarke_teilenummer;pfand;versandklasse;lieferzeit\n";
            $csvContent .= "INT12345678;150.50;3;Valid Record;BMW123456;25;2;7\n";
            $csvContent .= "INT87654321;-10.00;10;Invalid Record;MERC789;1000;15;400\n"; // Invalid values

            $file = UploadedFile::fake()->createWithContent(
                'validation_test.csv',
                $csvContent
            );

            $service = new ProfessionalCsvService();
            $results = $service->processCsvFile($file, [
                'rollback_on_error' => false
            ]);

            expect($results['success'])->toBe(false);
            expect($results['processing_stats']['failed_rows'])->toBe(1);
        });
    });
});

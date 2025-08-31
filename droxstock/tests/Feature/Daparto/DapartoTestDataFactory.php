<?php

namespace Tests\Feature\Daparto;

use App\Models\Daparto;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Enterprise-grade test data factory for Daparto testing suite
 *
 * This factory provides comprehensive test data generation capabilities
 * for all Daparto testing scenarios, ensuring consistent and reliable
 * test execution across different environments.
 */
class DapartoTestDataFactory
{
    /**
     * Create a single Daparto record with valid data
     */
    public static function createDaparto(array $overrides = []): Daparto
    {
        $defaultData = [
            'tiltle' => 'BMW 3 Series Brake Pad Set',
            'teilemarke_teilenummer' => 'BMW' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            'preis' => rand(5000, 50000) / 100,
            'interne_artikelnummer' => 'INT' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'zustand' => rand(1, 5),
            'pfand' => rand(0, 500),
            'versandklasse' => rand(1, 5),
            'lieferzeit' => rand(1, 30),
        ];

        return Daparto::factory()->create(array_merge($defaultData, $overrides));
    }

    /**
     * Create multiple Daparto records for bulk testing
     */
    public static function createMultipleDapartos(int $count = 10, array $overrides = []): Collection
    {
        $dapartos = collect();

        for ($i = 1; $i <= $count; $i++) {
            $dapartos->push(self::createDaparto(array_merge($overrides, [
                'tiltle' => "Test Part {$i}",
                'interne_artikelnummer' => 'INT' . str_pad($i, 8, '0', STR_PAD_LEFT),
            ])));
        }

        return $dapartos;
    }

    /**
     * Create Daparto records with specific brands for filtering tests
     */
    public static function createDapartosByBrands(array $brands = null): Collection
    {
        $brands = $brands ?? DapartoTestConfig::TEST_BRANDS;
        $dapartos = collect();

        foreach ($brands as $brand) {
            $dapartos->push(self::createDaparto([
                'teilemarke_teilenummer' => $brand . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            ]));
        }

        return $dapartos;
    }

    /**
     * Create Daparto records with specific price ranges
     */
    public static function createDapartosByPriceRange(float $minPrice, float $maxPrice, int $count = 5): Collection
    {
        $dapartos = collect();

        for ($i = 1; $i <= $count; $i++) {
            $dapartos->push(self::createDaparto([
                'preis' => rand($minPrice * 100, $maxPrice * 100) / 100,
            ]));
        }

        return $dapartos;
    }

    /**
     * Create Daparto records with specific conditions
     */
    public static function createDapartosByCondition(int $condition, int $count = 3): Collection
    {
        $dapartos = collect();

        for ($i = 1; $i <= $count; $i++) {
            $dapartos->push(self::createDaparto([
                'zustand' => $condition,
            ]));
        }

        return $dapartos;
    }

    /**
     * Create a user with admin role for testing
     */
    public static function createAdminUser(): User
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        return $user;
    }

    /**
     * Create a user with basic role for testing
     */
    public static function createBasicUser(): User
    {
        $basicRole = Role::firstOrCreate(['name' => 'basic_user', 'guard_name' => 'api']);
        $user = User::factory()->create();
        $user->assignRole($basicRole);

        return $user;
    }

    /**
     * Create test CSV file for upload testing
     */
    public static function createTestCsvFile(string $filename = 'test_dapartos.csv', int $rows = 10): UploadedFile
    {
        $csvContent = "tiltle,teilemarke_teilenummer,preis,interne_artikelnummer,zustand,pfand,versandklasse,lieferzeit\n";

        for ($i = 1; $i <= $rows; $i++) {
            $csvContent .= sprintf(
                "Test Part %d,BMW%06d,%.2f,INT%08d,%d,%d,%d,%d\n",
                $i,
                rand(100000, 999999),
                rand(5000, 50000) / 100,
                $i,
                rand(1, 5),
                rand(0, 500),
                rand(1, 5),
                rand(1, 30)
            );
        }

        $filePath = storage_path('app/temp/' . $filename);
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $csvContent);

        return new UploadedFile(
            $filePath,
            $filename,
            'text/csv',
            null,
            true
        );
    }

    /**
     * Create invalid CSV file for error testing
     */
    public static function createInvalidCsvFile(string $filename = 'invalid_dapartos.csv'): UploadedFile
    {
        $csvContent = "invalid_column,another_invalid\n";
        $csvContent .= "invalid data,more invalid data\n";

        $filePath = storage_path('app/temp/' . $filename);
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $csvContent);

        return new UploadedFile(
            $filePath,
            $filename,
            'text/csv',
            null,
            true
        );
    }

    /**
     * Create large CSV file for performance testing
     */
    public static function createLargeCsvFile(string $filename = 'large_dapartos.csv', int $rows = 1000): UploadedFile
    {
        $csvContent = "tiltle,teilemarke_teilenummer,preis,interne_artikelnummer,zustand,pfand,versandklasse,lieferzeit\n";

        for ($i = 1; $i <= $rows; $i++) {
            $csvContent .= sprintf(
                "Large Test Part %d,BMW%06d,%.2f,INT%08d,%d,%d,%d,%d\n",
                $i,
                rand(100000, 999999),
                rand(5000, 50000) / 100,
                $i,
                rand(1, 5),
                rand(0, 500),
                rand(1, 5),
                rand(1, 30)
            );
        }

        $filePath = storage_path('app/temp/' . $filename);
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $csvContent);

        return new UploadedFile(
            $filePath,
            $filename,
            'text/csv',
            null,
            true
        );
    }

    /**
     * Create malicious CSV file for security testing
     */
    public static function createMaliciousCsvFile(string $filename = 'malicious_dapartos.csv'): UploadedFile
    {
        $csvContent = "tiltle,teilemarke_teilenummer,preis,interne_artikelnummer,zustand,pfand,versandklasse,lieferzeit\n";
        $csvContent .= "<script>alert('XSS')</script>,BMW000001,100.00,INT00000001,1,0,1,1\n";
        $csvContent .= "'; DROP TABLE dapartos; --,BMW000002,200.00,INT00000002,2,0,1,1\n";
        $csvContent .= "../../../etc/passwd,BMW000003,300.00,INT00000003,3,0,1,1\n";

        $filePath = storage_path('app/temp/' . $filename);
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $csvContent);

        return new UploadedFile(
            $filePath,
            $filename,
            'text/csv',
            null,
            true
        );
    }

    /**
     * Create test data for validation testing
     */
    public static function getValidationTestData(): array
    {
        return [
            'valid_data' => [
                'tiltle' => 'Valid BMW Part',
                'teilemarke_teilenummer' => 'BMW123456',
                'preis' => 150.50,
                'interne_artikelnummer' => 'INT12345678',
                'zustand' => 3,
                'pfand' => 25,
                'versandklasse' => 2,
                'lieferzeit' => 7,
            ],
            'invalid_title_length' => [
                'tiltle' => str_repeat('A', DapartoTestConfig::MAX_TITLE_LENGTH + 1),
                'teilemarke_teilenummer' => 'BMW123456',
                'preis' => 150.50,
                'interne_artikelnummer' => 'INT12345678',
                'zustand' => 3,
                'pfand' => 25,
                'versandklasse' => 2,
                'lieferzeit' => 7,
            ],
            'invalid_price_negative' => [
                'tiltle' => 'Valid BMW Part',
                'teilemarke_teilenummer' => 'BMW123456',
                'preis' => -10.00,
                'interne_artikelnummer' => 'INT12345678',
                'zustand' => 3,
                'pfand' => 25,
                'versandklasse' => 2,
                'lieferzeit' => 7,
            ],
            'invalid_condition_range' => [
                'tiltle' => 'Valid BMW Part',
                'teilemarke_teilenummer' => 'BMW123456',
                'preis' => 150.50,
                'interne_artikelnummer' => 'INT12345678',
                'zustand' => 10,
                'pfand' => 25,
                'versandklasse' => 2,
                'lieferzeit' => 7,
            ],
        ];
    }

    /**
     * Create test data for search and filtering testing
     */
    public static function getSearchTestData(): array
    {
        return [
            'search_terms' => ['BMW', 'Mercedes', 'Audi', 'Brake', 'Engine'],
            'price_ranges' => [
                ['min' => 0, 'max' => 100],
                ['min' => 100, 'max' => 500],
                ['min' => 500, 'max' => 1000],
                ['min' => 1000, 'max' => 5000],
            ],
            'conditions' => [1, 2, 3, 4, 5],
            'shipping_classes' => [1, 2, 3, 4, 5],
        ];
    }

    /**
     * Clean up test files
     */
    public static function cleanupTestFiles(): void
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

    /**
     * Reset database to clean state
     */
    public static function resetDatabase(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('dapartos')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Create test data for concurrent testing
     */
    public static function createConcurrentTestData(int $count = 10): array
    {
        $data = [];
        for ($i = 1; $i <= $count; $i++) {
            $data[] = [
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
        return $data;
    }
}

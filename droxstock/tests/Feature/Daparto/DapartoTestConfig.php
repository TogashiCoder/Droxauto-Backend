<?php

namespace Tests\Feature\Daparto;

/**
 * Enterprise-grade test configuration for Daparto testing suite
 *
 * This class provides centralized configuration constants and settings
 * for the Daparto testing suite, following industry best practices
 * for maintainable and scalable test architecture.
 */
class DapartoTestConfig
{
    // API Endpoints
    public const API_BASE_URL = '/api/v1';
    public const DAPARTOS_ENDPOINT = '/api/v1/dapartos';
    public const DAPARTOS_STATS_ENDPOINT = '/api/v1/dapartos-stats';
    public const DAPARTOS_BY_NUMBER_ENDPOINT = '/api/v1/dapartos-by-number';
    public const DAPARTOS_RESTORE_ENDPOINT = '/api/v1/dapartos';
    public const DAPARTOS_UPLOAD_CSV_ENDPOINT = '/api/v1/dapartos-upload-csv';
    public const DAPARTOS_DELETE_ALL_ENDPOINT = '/api/v1/dapartos-delete-all';

    // HTTP Status Codes
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    // Pagination Settings
    public const DEFAULT_PER_PAGE = 15;
    public const MAX_PER_PAGE = 100;
    public const TEST_PAGE_SIZE = 5;

    // Validation Rules
    public const MAX_TITLE_LENGTH = 255;
    public const MAX_TEILEMARKE_LENGTH = 255;
    public const MAX_INTERNE_ARTIKELNUMMER_LENGTH = 100;
    public const MIN_PRICE = 0;
    public const MAX_PRICE = 999999.99;
    public const MIN_ZUSTAND = 0;
    public const MAX_ZUSTAND = 5;
    public const MIN_PFAND = 0;
    public const MAX_PFAND = 1000;
    public const MIN_VERSANDKLASSE = 0;
    public const MAX_VERSANDKLASSE = 10;
    public const MIN_LIEFERZEIT = 0;
    public const MAX_LIEFERZEIT = 365;

    // Test Data Constants
    public const TEST_BRANDS = ['BMW', 'Mercedes', 'Audi', 'Volkswagen', 'Opel'];
    public const TEST_CONDITIONS = [1, 2, 3, 4, 5];
    public const TEST_SHIPPING_CLASSES = [1, 2, 3, 4, 5];
    public const TEST_DELIVERY_TIMES = [1, 3, 7, 14, 30];

    // CSV Processing
    public const MAX_CSV_SIZE = 10240; // 10MB
    public const SUPPORTED_CSV_MIME_TYPES = [
        'text/csv',
        'text/plain',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    // Performance Testing
    public const PERFORMANCE_THRESHOLD_MS = 1000; // 1 second
    public const BULK_OPERATION_THRESHOLD_MS = 5000; // 5 seconds
    public const CSV_PROCESSING_THRESHOLD_MS = 10000; // 10 seconds

    // Security Testing
    public const SQL_INJECTION_PAYLOADS = [
        "' OR '1'='1",
        "'; DROP TABLE dapartos; --",
        "' UNION SELECT * FROM users --",
        "'; EXEC xp_cmdshell('dir'); --"
    ];

    public const XSS_PAYLOADS = [
        '<script>alert("XSS")</script>',
        'javascript:alert("XSS")',
        '<img src="x" onerror="alert(\'XSS\')">',
        '<svg onload="alert(\'XSS\')">'
    ];

    // Test File Paths
    public const TEST_FILES_PATH = 'tests/Fixtures/Daparto/';
    public const VALID_CSV_PATH = 'tests/Fixtures/Daparto/valid_dapartos.csv';
    public const INVALID_CSV_PATH = 'tests/Fixtures/Daparto/invalid_dapartos.csv';
    public const LARGE_CSV_PATH = 'tests/Fixtures/Daparto/large_dapartos.csv';
    public const MALICIOUS_CSV_PATH = 'tests/Fixtures/Daparto/malicious_dapartos.csv';

    // Database Testing
    public const BULK_INSERT_SIZE = 1000;
    public const TRANSACTION_TIMEOUT = 30; // seconds
    public const MAX_CONCURRENT_REQUESTS = 10;

    // Cache Testing
    public const CACHE_TTL = 3600; // 1 hour
    public const CACHE_PREFIX = 'daparto_test_';

    // Rate Limiting
    public const RATE_LIMIT_REQUESTS = 60;
    public const RATE_LIMIT_WINDOW = 60; // seconds

    /**
     * Get validation rules for testing
     */
    public static function getValidationRules(): array
    {
        return [
            'tiltle' => 'nullable|string|max:' . self::MAX_TITLE_LENGTH,
            'teilemarke_teilenummer' => 'required|string|max:' . self::MAX_TEILEMARKE_LENGTH,
            'preis' => 'required|numeric|min:' . self::MIN_PRICE . '|max:' . self::MAX_PRICE,
            'interne_artikelnummer' => 'required|string|max:' . self::MAX_INTERNE_ARTIKELNUMMER_LENGTH . '|unique:dapartos,interne_artikelnummer',
            'zustand' => 'required|integer|min:' . self::MIN_ZUSTAND . '|max:' . self::MAX_ZUSTAND,
            'pfand' => 'required|integer|min:' . self::MIN_PFAND . '|max:' . self::MAX_PFAND,
            'versandklasse' => 'required|integer|min:' . self::MIN_VERSANDKLASSE . '|max:' . self::MAX_VERSANDKLASSE,
            'lieferzeit' => 'required|integer|min:' . self::MIN_LIEFERZEIT . '|max:' . self::MAX_LIEFERZEIT,
        ];
    }

    /**
     * Get test data for bulk operations
     */
    public static function getBulkTestData(int $count = 10): array
    {
        $data = [];
        for ($i = 1; $i <= $count; $i++) {
            $data[] = [
                'tiltle' => "Test Part {$i}",
                'teilemarke_teilenummer' => 'BMW' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'preis' => rand(1000, 50000) / 100,
                'interne_artikelnummer' => 'INT' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'zustand' => rand(1, 5),
                'pfand' => rand(0, 500),
                'versandklasse' => rand(1, 5),
                'lieferzeit' => rand(1, 30),
            ];
        }
        return $data;
    }

    /**
     * Get performance test scenarios
     */
    public static function getPerformanceTestScenarios(): array
    {
        return [
            'small_dataset' => ['count' => 100, 'threshold' => 500],
            'medium_dataset' => ['count' => 1000, 'threshold' => 1000],
            'large_dataset' => ['count' => 10000, 'threshold' => 5000],
        ];
    }

    /**
     * Get security test scenarios
     */
    public static function getSecurityTestScenarios(): array
    {
        return [
            'sql_injection' => self::SQL_INJECTION_PAYLOADS,
            'xss_attack' => self::XSS_PAYLOADS,
            'path_traversal' => ['../../../etc/passwd', '..\\..\\..\\windows\\system32\\drivers\\etc\\hosts'],
            'command_injection' => ['| cat /etc/passwd', '; ls -la', '&& dir'],
        ];
    }
}

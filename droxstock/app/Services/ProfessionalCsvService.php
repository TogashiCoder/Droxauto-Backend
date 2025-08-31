<?php

namespace App\Services;

use App\Models\Daparto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;

/**
 * Professional CSV Service for Daparto data processing
 * 
 * This service handles CSV file processing with comprehensive validation,
 * error handling, and detailed reporting for business users.
 */
class ProfessionalCsvService
{
    protected array $options;
    protected array $processingStats;
    protected array $validationSummary;
    protected array $errors;
    protected array $performance;
    protected array $fileInfo;
    protected array $processedInterneCodes; // Track processed codes within this session

    public function __construct()
    {
        $this->resetProcessingData();
    }

    /**
     * Process CSV file with comprehensive validation and error handling
     */
    public function processCsvFile(UploadedFile $file, array $options = []): array
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage();

        try {
            $this->options = array_merge($this->getDefaultOptions(), $options);
            $this->resetProcessingData();
            $this->setFileInfo($file);

            Log::info('Starting CSV processing', [
                'file' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'options' => $this->options
            ]);

            // Validate file structure
            $this->validateFileStructure($file);

            // Process CSV data
            $this->processCsvData($file);

            // Calculate final statistics
            $this->calculateFinalStatistics($startTime, $memoryStart);

            // Log success
            Log::info('CSV processing completed successfully', [
                'file' => $file->getClientOriginalName(),
                'stats' => $this->processingStats
            ]);

            return $this->buildSuccessResponse();

        } catch (\Exception $e) {
            Log::error('CSV processing failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->calculateFinalStatistics($startTime, $memoryStart);
            return $this->buildErrorResponse($e);
        }
    }

    /**
     * Get default processing options
     */
    protected function getDefaultOptions(): array
    {
        return [
            'validation_mode' => 'strict',
            'update_existing' => true,
            'skip_duplicates' => false,
            'batch_size' => 1000,
            'rollback_on_error' => true,
            'email_notification' => true,
            'delimiter' => ';',
            'encoding' => 'UTF-8',
        ];
    }

    /**
     * Reset processing data for new file
     */
    protected function resetProcessingData(): void
    {
        $this->processingStats = [
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'updated_rows' => 0,
            'new_rows' => 0,
            'duplicate_rows' => 0,
            'skipped_rows' => 0,
        ];

        $this->validationSummary = [
            'data_quality_score' => 0,
            'validation_errors' => 0,
            'business_rule_violations' => 0,
            'data_inconsistencies' => 0,
        ];

        $this->errors = [];
        $this->performance = [
            'duration' => 0,
            'memory_peak' => 0,
            'memory_usage' => 0,
        ];

        $this->fileInfo = [];
        $this->processedInterneCodes = []; // Reset processed codes tracker
    }

    /**
     * Set file information
     */
    protected function setFileInfo(UploadedFile $file): void
    {
        $this->fileInfo = [
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now(),
        ];
    }

    /**
     * Validate file structure and headers
     */
    protected function validateFileStructure(UploadedFile $file): void
    {
        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) {
            throw new \Exception('Cannot open CSV file for reading');
        }

        // Read headers
        $headers = fgetcsv($handle, 0, $this->options['delimiter']);
        if (!$headers || count($headers) < 3) {
            fclose($handle);
            throw new \Exception('Invalid CSV structure: insufficient columns');
        }

        // Validate required headers
        $requiredHeaders = ['interne_artikelnummer', 'preis', 'zustand'];
        $missingHeaders = array_diff($requiredHeaders, $headers);
        if (!empty($missingHeaders)) {
            fclose($handle);
            throw new \Exception('Missing required columns: ' . implode(', ', $missingHeaders));
        }

        // Count total rows
        $rowCount = 0;
        while (fgetcsv($handle, 0, $this->options['delimiter']) !== false) {
            $rowCount++;
        }
        fclose($handle);

        $this->processingStats['total_rows'] = $rowCount;

        if ($rowCount === 0) {
            throw new \Exception('CSV file contains no data rows');
        }

        Log::info('File structure validated', [
            'headers' => $headers,
            'total_rows' => $rowCount
        ]);
    }

    /**
     * Process CSV data with transaction support
     */
    protected function processCsvData(UploadedFile $file): void
    {
        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) {
            throw new \Exception('Cannot open CSV file for processing');
        }

        // Skip headers
        fgetcsv($handle, 0, $this->options['delimiter']);

        $batch = [];
        $rowNumber = 1;

        try {
            while (($row = fgetcsv($handle, 0, $this->options['delimiter'])) !== false) {
                $rowNumber++;
                $this->processingStats['processed_rows']++;

                // Process row
                $result = $this->processRow($row, $rowNumber);
                
                if ($result['success']) {
                    $batch[] = $result['data'];
                    $this->processingStats['successful_rows']++;
                    
                    if ($result['action'] === 'update') {
                        $this->processingStats['updated_rows']++;
                    } else {
                        $this->processingStats['new_rows']++;
                    }
                } else {
                    $this->processingStats['failed_rows']++;
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'data' => $row,
                        'errors' => $result['errors'],
                        'action' => 'failed'
                    ];
                }

                // Process batch if size reached
                if (count($batch) >= $this->options['batch_size']) {
                    $this->processBatch($batch);
                    $batch = [];
                }
            }

            // Process remaining batch
            if (!empty($batch)) {
                $this->processBatch($batch);
            }

        } finally {
            fclose($handle);
        }

        // Calculate data quality score
        $this->calculateDataQualityScore();
    }

    /**
     * Process individual CSV row
     */
    protected function processRow(array $row, int $rowNumber): array
    {
        try {
            // Map CSV columns to database fields
            $data = $this->mapCsvToDatabase($row);
            
            // Check if we've already processed this interne_artikelnummer in this session
            $interneCodes = $data['interne_artikelnummer'];
            $alreadyProcessedInSession = in_array($interneCodes, $this->processedInterneCodes);
            
            // Check if record exists in database
            $existingRecord = Daparto::where('interne_artikelnummer', $interneCodes)->first();
            
            // Handle duplicates within the current session first
            if ($alreadyProcessedInSession) {
                $this->processingStats['duplicate_rows']++;
                if ($this->options['skip_duplicates']) {
                    $this->processingStats['skipped_rows']++;
                    return [
                        'success' => true,
                        'data' => null,
                        'action' => 'skipped_duplicate_in_session'
                    ];
                }
                return [
                    'success' => false,
                    'errors' => ['Duplicate record found within the same CSV file'],
                    'action' => 'duplicate_found_in_session'
                ];
            }
            
            // Validate data (pass existing record ID for uniqueness validation)
            $validation = $this->validateRowData($data, $rowNumber, $existingRecord);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors'],
                    'action' => 'validation_failed'
                ];
            }
            
            if ($existingRecord) {
                if ($this->options['update_existing']) {
                    // Mark this interne_artikelnummer as processed in this session
                    $this->processedInterneCodes[] = $interneCodes;
                    
                    return [
                        'success' => true,
                        'data' => array_merge($data, ['id' => $existingRecord->id]),
                        'action' => 'update'
                    ];
                } else {
                    $this->processingStats['duplicate_rows']++;
                    if ($this->options['skip_duplicates']) {
                        $this->processingStats['skipped_rows']++;
                        return [
                            'success' => true,
                            'data' => null,
                            'action' => 'skipped_duplicate'
                        ];
                    }
                    return [
                        'success' => false,
                        'errors' => ['Record already exists'],
                        'action' => 'duplicate_found'
                    ];
                }
            } else {
                // Mark this interne_artikelnummer as processed in this session
                $this->processedInterneCodes[] = $interneCodes;
                
                return [
                    'success' => true,
                    'data' => $data,
                    'action' => 'create'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()],
                'action' => 'processing_error'
            ];
        }
    }

    /**
     * Map CSV columns to database fields
     */
    protected function mapCsvToDatabase(array $row): array
    {
        // Expected CSV columns: interne_artikelnummer, preis, zustand, tiltle, teilemarke_teilenummer, pfand, versandklasse, lieferzeit
        return [
            'interne_artikelnummer' => $row[0] ?? '',
            'preis' => $row[1] ?? 0,
            'zustand' => $row[2] ?? 1,
            'tiltle' => $row[3] ?? null,
            'teilemarke_teilenummer' => $row[4] ?? '',
            'pfand' => $row[5] ?? 0,
            'versandklasse' => $row[6] ?? 1,
            'lieferzeit' => $row[7] ?? 1,
        ];
    }

    /**
     * Validate row data against business rules
     */
    protected function validateRowData(array $data, int $rowNumber, $existingRecord = null): array
    {
        $rules = Daparto::validationRules($existingRecord ? $existingRecord->id : null);
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Process batch of records
     */
    protected function processBatch(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        try {
            DB::beginTransaction();

            foreach ($batch as $record) {
                if ($record === null) continue; // Skip skipped records

                if (isset($record['id'])) {
                    // Update existing record
                    Daparto::where('id', $record['id'])->update($record);
                } else {
                    // Create new record
                    Daparto::create($record);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($this->options['rollback_on_error']) {
                throw $e;
            } else {
                Log::error('Batch processing failed', [
                    'batch_size' => count($batch),
                    'error' => $e->getMessage()
                ]);
                
                // Mark all rows in batch as failed
                foreach ($batch as $record) {
                    if ($record !== null) {
                        $this->processingStats['failed_rows']++;
                        $this->errors[] = [
                            'row' => 'batch',
                            'data' => $record,
                            'errors' => [$e->getMessage()],
                            'action' => 'batch_failed'
                        ];
                    }
                }
            }
        }
    }

    /**
     * Calculate data quality score
     */
    protected function calculateDataQualityScore(): void
    {
        $totalRows = $this->processingStats['total_rows'];
        $successfulRows = $this->processingStats['successful_rows'];
        
        if ($totalRows > 0) {
            $this->validationSummary['data_quality_score'] = round(($successfulRows / $totalRows) * 100, 2);
        }

        $this->validationSummary['validation_errors'] = count($this->errors);
    }

    /**
     * Calculate final processing statistics
     */
    protected function calculateFinalStatistics(float $startTime, int $memoryStart): void
    {
        $endTime = microtime(true);
        $memoryEnd = memory_get_usage();
        $memoryPeak = memory_get_peak_usage();

        $this->performance = [
            'duration' => round($endTime - $startTime, 2),
            'memory_peak' => $memoryPeak,
            'memory_usage' => $memoryEnd - $memoryStart,
        ];
    }

    /**
     * Build success response
     */
    protected function buildSuccessResponse(): array
    {
        // Determine if processing was actually successful
        $isSuccessful = $this->processingStats['failed_rows'] === 0 && 
                       $this->processingStats['successful_rows'] > 0;
        
        return [
            'success' => $isSuccessful,
            'message' => $isSuccessful ? 'CSV file processed successfully' : 'CSV file processed with errors',
            'file_info' => $this->fileInfo,
            'processing_stats' => $this->processingStats,
            'validation_summary' => $this->validationSummary,
            'performance' => $this->performance,
            'errors' => $this->errors,
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Build error response
     */
    protected function buildErrorResponse(\Exception $e): array
    {
        return [
            'success' => false,
            'message' => 'CSV processing failed: ' . $e->getMessage(),
            'file_info' => $this->fileInfo,
            'processing_stats' => $this->processingStats,
            'validation_summary' => $this->validationSummary,
            'performance' => $this->performance,
            'errors' => $this->errors,
            'error_details' => [
                'message' => $e->getMessage(),
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
        ];
    }

    /**
     * Generate processing recommendations
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];

        if ($this->validationSummary['data_quality_score'] < 80) {
            $recommendations[] = 'Data quality is below 80%. Review your CSV file for formatting issues.';
        }

        if ($this->processingStats['failed_rows'] > 0) {
            $recommendations[] = 'Some rows failed to process. Check the error details for specific issues.';
        }

        if ($this->processingStats['duplicate_rows'] > 0) {
            $recommendations[] = 'Duplicate records found. Consider using update_existing option for future uploads.';
        }

        if ($this->performance['duration'] > 30) {
            $recommendations[] = 'Processing took longer than 30 seconds. Consider using background processing for large files.';
        }

        return $recommendations;
    }
}

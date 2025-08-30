<?php

namespace App\Jobs;

use App\Services\ProfessionalCsvService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class ProcessCsvUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;
    public $maxExceptions = 3;

    protected $filePath;
    protected $fileName;
    protected $options;
    protected $userId;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $fileName, array $options = [], $userId = null, string $jobId = null)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
        $this->options = $options;
        $this->userId = $userId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting CSV processing job', [
                'file' => $this->fileName,
                'user_id' => $this->userId,
                'options' => $this->options
            ]);

            // Check if file still exists
            if (!Storage::exists($this->filePath)) {
                throw new \Exception('Uploaded file no longer exists');
            }

            // Get file from storage
            $file = Storage::get($this->filePath);

            // Create temporary file for processing
            $tempPath = storage_path('app/temp/' . uniqid('csv_') . '.csv');
            file_put_contents($tempPath, $file);

            // Create file object for service
            $fileObject = new \Illuminate\Http\UploadedFile(
                $tempPath,
                $this->fileName,
                'text/csv',
                null,
                true
            );

            // Process CSV with professional service
            $csvService = new ProfessionalCsvService();
            $results = $csvService->processCsvFile($fileObject, $this->options);

            // Log results
            Log::info('CSV processing job completed', [
                'file' => $this->fileName,
                'results' => $results,
                'user_id' => $this->userId
            ]);

            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            // Store results for user retrieval
            $this->storeJobResults($results);

            // Send email notification if enabled
            if ($this->options['email_notification'] && !empty($this->options['user_email'])) {
                $this->sendEmailNotification($results);
            }
        } catch (\Exception $e) {
            Log::error('CSV processing job failed', [
                'file' => $this->fileName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId
            ]);

            // Clean up on failure
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            // Store error results
            $this->storeJobResults([
                'success' => false,
                'error' => $e->getMessage(),
                'file_name' => $this->fileName
            ]);

            // Send error notification email
            if ($this->options['email_notification'] && !empty($this->options['user_email'])) {
                $this->sendErrorEmailNotification($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Store job results for user retrieval
     */
    private function storeJobResults(array $results): void
    {
        // Use the job ID passed to the constructor
        $jobId = $this->jobId ?? uniqid();

        // Store in cache for 24 hours with proper key
        \Illuminate\Support\Facades\Cache::put(
            "csv_job_{$jobId}",
            [
                'results' => $results,
                'completed_at' => now(),
                'file_name' => $this->fileName
            ],
            86400 // 24 hours
        );

        Log::info('CSV job results stored', [
            'job_id' => $jobId,
            'file' => $this->fileName
        ]);
    }

    /**
     * Send success email notification
     */
    private function sendEmailNotification(array $results): void
    {
        try {
            // Calculate additional metrics for comprehensive reporting
            $totalRows = $results['processing_stats']['total_rows'] ?? 0;
            $validRows = $results['processing_stats']['valid_rows'] ?? 0;
            $processingTime = $results['performance']['duration'] ?? 0;
            $memoryPeak = $results['performance']['memory_peak'] ?? 0;

            $emailData = [
                // Basic file info
                'fileName' => $this->fileName,
                'fileSize' => $results['file_info']['size'] ?? 0,
                'uploadedAt' => $results['file_info']['uploaded_at'] ?? now()->format('Y-m-d H:i:s'),
                'processingStartedAt' => $results['performance']['start_time'] ? date('Y-m-d H:i:s', $results['performance']['start_time']) : now()->format('Y-m-d H:i:s'),
                'completedAt' => now()->format('Y-m-d H:i:s'),

                // Processing results
                'success' => $results['success'],
                'totalRows' => $totalRows,
                'validRows' => $validRows,
                'invalidRows' => $results['processing_stats']['invalid_rows'] ?? 0,
                'inserted' => $results['processing_stats']['inserted'] ?? 0,
                'updated' => $results['processing_stats']['updated'] ?? 0,
                'skipped' => $results['processing_stats']['skipped'] ?? 0,
                'duplicates' => $results['processing_stats']['duplicates'] ?? 0,

                // Performance metrics
                'processingTime' => $processingTime,
                'memoryPeak' => $memoryPeak,

                // Data quality assessment
                'dataQualityScore' => $results['validation_summary']['data_quality_score'] ?? 100,
                'structureValid' => $results['validation_summary']['structure_valid'] ?? true,
                'headersValid' => $results['validation_summary']['headers_valid'] ?? true,
                'dataFormatValid' => $results['validation_summary']['data_format_valid'] ?? true,
                'encodingValid' => $results['validation_summary']['encoding_valid'] ?? true,

                // Validation details
                'requiredFieldsPresent' => $results['validation_summary']['required_fields_present'] ?? true,
                'dataTypeValidation' => $results['validation_summary']['data_type_validation'] ?? true,
                'rangeValidation' => $results['validation_summary']['range_validation'] ?? true,
                'formatValidation' => $results['validation_summary']['format_validation'] ?? true,
                'validationErrors' => count($results['processing_stats']['errors'] ?? []),

                // Business intelligence (if available)
                'totalValue' => $results['business_intelligence']['total_value'] ?? 0,
                'averagePrice' => $results['business_intelligence']['average_price'] ?? 0,
                'uniqueBrands' => $results['business_intelligence']['unique_brands'] ?? 0,
                'uniqueCategories' => $results['business_intelligence']['unique_categories'] ?? 0,
                'inStockCount' => $results['business_intelligence']['in_stock_count'] ?? 0,
                'outOfStockCount' => $results['business_intelligence']['out_of_stock_count'] ?? 0,
                'newConditionCount' => $results['business_intelligence']['new_condition_count'] ?? 0,
                'usedConditionCount' => $results['business_intelligence']['used_condition_count'] ?? 0,
            ];

            Mail::to($this->options['user_email'])->send(new \App\Mail\CsvProcessingComplete($emailData));

            Log::info('CSV processing success email sent', [
                'file' => $this->fileName,
                'email' => $this->options['user_email']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send CSV success email', [
                'file' => $this->fileName,
                'email' => $this->options['user_email'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send error email notification
     */
    private function sendErrorEmailNotification(string $errorMessage): void
    {
        try {
            $emailData = [
                'fileName' => $this->fileName,
                'error' => $errorMessage,
                'failedAt' => now()->format('Y-m-d H:i:s'),
            ];

            Mail::to($this->options['user_email'])->send(new \App\Mail\CsvProcessingFailed($emailData));

            Log::info('CSV processing error email sent', [
                'file' => $this->fileName,
                'email' => $this->options['user_email']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send CSV error email', [
                'file' => $this->fileName,
                'email' => $this->options['user_email'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CSV processing job failed permanently', [
            'file' => $this->fileName,
            'error' => $exception->getMessage(),
            'user_id' => $this->userId
        ]);

        // Clean up uploaded file on permanent failure
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }

        // Send final failure notification
        if ($this->options['email_notification'] && !empty($this->options['user_email'])) {
            $this->sendErrorEmailNotification($exception->getMessage());
        }
    }
}

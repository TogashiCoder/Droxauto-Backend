<?php

namespace App\Http\Resources\Daparto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CsvUploadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'upload_id' => uniqid('csv_'),
            'timestamp' => now()->toISOString(),
            'file_processing' => [
                'file_name' => $this['file_info']['name'] ?? 'Unknown',
                'file_size' => $this['file_info']['size'] ?? 0,
                'file_type' => $this['file_info']['mime_type'] ?? 'Unknown',
                'uploaded_at' => $this['file_info']['uploaded_at'] ?? now(),
            ],
            'processing_results' => [
                'success' => $this['success'] ?? false,
                'total_rows_processed' => $this['processing_stats']['total_rows'] ?? 0,
                'valid_rows' => $this['processing_stats']['valid_rows'] ?? 0,
                'invalid_rows' => $this['processing_stats']['invalid_rows'] ?? 0,
                'rows_inserted' => $this['processing_stats']['inserted'] ?? 0,
                'rows_updated' => $this['processing_stats']['updated'] ?? 0,
                'rows_skipped' => $this['processing_stats']['skipped'] ?? 0,
            ],
            'data_quality' => [
                'overall_score' => ($this['validation_summary']['data_quality_score'] ?? 0) . '%',
                'structure_valid' => $this['validation_summary']['structure_valid'] ?? false,
                'headers_valid' => $this['validation_summary']['headers_valid'] ?? false,
                'validation_mode' => $this['options']['validation_mode'] ?? 'strict',
            ],
            'performance_metrics' => [
                'processing_time' => ($this['performance']['duration'] ?? 0) . ' seconds',
                'memory_peak_usage' => $this->formatBytes($this['performance']['memory_peak'] ?? 0),
                'rows_per_second' => $this->calculateRowsPerSecond(),
                'efficiency_score' => $this->calculateEfficiencyScore(),
            ],
            'error_summary' => $this->formatErrorSummary(),
            'recommendations' => $this->generateRecommendations(),
            'next_steps' => $this->generateNextSteps(),
        ];
    }

    /**
     * Format error summary for better readability
     */
    private function formatErrorSummary(): array
    {
        $errors = $this['processing_stats']['errors'] ?? [];

        if (empty($errors)) {
            return [
                'status' => 'No errors detected',
                'total_errors' => 0,
                'error_categories' => [],
                'critical_errors' => 0,
                'warning_errors' => 0,
            ];
        }

        $errorCategories = [];
        $criticalErrors = 0;
        $warningErrors = 0;

        foreach ($errors as $error) {
            $type = $error['type'] ?? 'unknown';
            if (!isset($errorCategories[$type])) {
                $errorCategories[$type] = 0;
            }
            $errorCategories[$type]++;

            // Categorize error severity
            if (in_array($type, ['structure_error', 'system_error', 'database_error'])) {
                $criticalErrors++;
            } else {
                $warningErrors++;
            }
        }

        return [
            'status' => $criticalErrors > 0 ? 'Critical errors detected' : 'Warnings detected',
            'total_errors' => count($errors),
            'error_categories' => $errorCategories,
            'critical_errors' => $criticalErrors,
            'warning_errors' => $warningErrors,
            'sample_errors' => array_slice($errors, 0, 5), // Show first 5 errors
        ];
    }

    /**
     * Generate recommendations based on processing results
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        $stats = $this['processing_stats'] ?? [];
        $quality = $this['validation_summary']['data_quality_score'] ?? 0;

        if ($quality < 80) {
            $recommendations[] = 'Review your CSV data for consistency and completeness';
            $recommendations[] = 'Check for missing required fields (interne Artikelnummer, Preis, Zustand)';
            $recommendations[] = 'Ensure all numeric values are properly formatted';
        }

        if (($stats['invalid_rows'] ?? 0) > 0) {
            $recommendations[] = 'Fix data validation errors before re-uploading';
            $recommendations[] = 'Use the error details to identify problematic rows';
        }

        if (($stats['updated'] ?? 0) > ($stats['inserted'] ?? 0)) {
            $recommendations[] = 'Many records were updated - verify this is intended';
            $recommendations[] = 'Consider using skip_duplicates option if updates are not desired';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Your CSV data quality is excellent!';
            $recommendations[] = 'Consider increasing batch size for future uploads';
        }

        return $recommendations;
    }

    /**
     * Generate next steps based on processing results
     */
    private function generateNextSteps(): array
    {
        $nextSteps = [];
        $success = $this['success'] ?? false;
        $errorCount = count($this['processing_stats']['errors'] ?? []);

        if ($success && $errorCount === 0) {
            $nextSteps[] = 'Your data has been successfully processed and stored';
            $nextSteps[] = 'You can now use the API to query and manage your parts';
            $nextSteps[] = 'Consider setting up automated CSV uploads for regular updates';
        } elseif ($success && $errorCount > 0) {
            $nextSteps[] = 'Data processed with some warnings - review error details';
            $nextSteps[] = 'Fix data issues and re-upload if necessary';
            $nextSteps[] = 'Monitor data quality for future uploads';
        } else {
            $nextSteps[] = 'Review and fix all validation errors';
            $nextSteps[] = 'Ensure CSV format matches required specifications';
            $nextSteps[] = 'Contact support if issues persist';
        }

        return $nextSteps;
    }

    /**
     * Calculate rows processed per second
     */
    private function calculateRowsPerSecond(): string
    {
        $duration = $this['performance']['duration'] ?? 0;
        $totalRows = $this['processing_stats']['total_rows'] ?? 0;

        if ($duration <= 0 || $totalRows <= 0) {
            return 'N/A';
        }

        $rowsPerSecond = $totalRows / $duration;
        return round($rowsPerSecond, 2) . ' rows/sec';
    }

    /**
     * Calculate efficiency score based on performance metrics
     */
    private function calculateEfficiencyScore(): string
    {
        $duration = $this['performance']['duration'] ?? 0;
        $totalRows = $this['processing_stats']['total_rows'] ?? 0;
        $memoryPeak = $this['performance']['memory_peak'] ?? 0;

        if ($duration <= 0 || $totalRows <= 0) {
            return 'N/A';
        }

        // Simple efficiency calculation (rows per second per MB of memory)
        $efficiency = ($totalRows / $duration) / max(1, $memoryPeak / 1024 / 1024);
        return round($efficiency, 2) . ' rows/sec/MB';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

<?php

namespace App\Http\Requests\Daparto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UploadCsvRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will be handled by Passport later
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:51200', // 50MB max - professional limit
                'mimetypes:text/csv,text/plain',
            ],
            'validation_mode' => 'nullable|in:strict,flexible,skip_errors',
            'update_existing' => 'nullable',
            'skip_duplicates' => 'nullable',
            'batch_size' => 'nullable|integer|min:100|max:10000',
            'email_notification' => 'nullable',
            'user_email' => 'nullable|email',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'CSV file is required for upload.',
            'csv_file.file' => 'The uploaded file is not valid.',
            'csv_file.mimes' => 'Only CSV and TXT files are allowed.',
            'csv_file.max' => 'File size cannot exceed 50MB.',
            'csv_file.mimetypes' => 'Invalid file type. Only CSV files are accepted.',
            'validation_mode.in' => 'Validation mode must be strict, flexible, or skip_errors.',
            'batch_size.min' => 'Batch size must be at least 100 rows.',
            'batch_size.max' => 'Batch size cannot exceed 10,000 rows.',
            'user_email.email' => 'Please provide a valid email address for notifications.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateFileContent($validator);
            $this->validateBusinessRules($validator);
            $this->convertBooleanFields($validator);
        });
    }

    /**
     * Validate file content and structure.
     */
    private function validateFileContent(Validator $validator): void
    {
        $file = $this->file('csv_file');

        if (!$file || !$file->isValid()) {
            return;
        }

        // Check file size in bytes
        if ($file->getSize() === 0) {
            $validator->errors()->add('csv_file', 'The uploaded file is empty.');
            return;
        }

        // Check if file is readable
        if (!is_readable($file->getPathname())) {
            $validator->errors()->add('csv_file', 'The uploaded file cannot be read.');
            return;
        }

        // Validate CSV structure
        $this->validateCsvStructure($validator, $file);
    }

    /**
     * Validate CSV structure and headers.
     */
    private function validateCsvStructure(Validator $validator, $file): void
    {
        try {
            $handle = fopen($file->getPathname(), 'r');

            if (!$handle) {
                $validator->errors()->add('csv_file', 'Cannot open file for validation.');
                return;
            }

            // Read first few lines to validate structure
            $headers = fgetcsv($handle, 0, ';');
            $firstRow = fgetcsv($handle, 0, ';');

            fclose($handle);

            if (!$headers || count($headers) < 3) {
                $validator->errors()->add('csv_file', 'CSV file must have at least 3 columns.');
                return;
            }

            // Check for required headers
            $requiredHeaders = ['interne Artikelnummer', 'Preis', 'Zustand'];
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (!empty($missingHeaders)) {
                $validator->errors()->add('csv_file', 'Missing required columns: ' . implode(', ', $missingHeaders));
                return;
            }

            // Validate first data row
            if ($firstRow && count($firstRow) !== count($headers)) {
                $validator->errors()->add('csv_file', 'CSV structure is inconsistent. Headers and data rows have different column counts.');
                return;
            }
        } catch (\Exception $e) {
            $validator->errors()->add('csv_file', 'Error validating CSV structure: ' . $e->getMessage());
        }
    }

    /**
     * Validate business rules and constraints.
     */
    private function validateBusinessRules(Validator $validator): void
    {
        $validationMode = $this->input('validation_mode', 'strict');
        $batchSize = $this->input('batch_size', 1000);

        // Business rule validations
        if ($batchSize > 5000 && $validationMode === 'strict') {
            $validator->errors()->add('batch_size', 'Large batch sizes require flexible validation mode for better performance.');
        }
    }

    /**
     * Convert string boolean values to actual booleans
     */
    private function convertBooleanFields(Validator $validator): void
    {
        $booleanFields = ['email_notification', 'update_existing', 'skip_duplicates'];

        foreach ($booleanFields as $field) {
            $value = $this->input($field);

            if ($value !== null) {
                // Convert any truthy/falsy value to boolean
                $stringValue = strtolower(trim((string) $value));

                if (in_array($stringValue, ['true', '1', 'yes', 'on', 'enabled'])) {
                    $this->merge([$field => true]);
                } elseif (in_array($stringValue, ['false', '0', 'no', 'off', 'disabled', ''])) {
                    $this->merge([$field => false]);
                } else {
                    // Default to true for any other value
                    $this->merge([$field => true]);
                }
            }
        }
    }
}

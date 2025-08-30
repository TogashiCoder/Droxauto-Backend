<?php

namespace App\Services;

use App\Models\Daparto;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DapartoService
{
    /**
     * Get paginated dapartos with optional filters
     */
    public function getPaginatedDapartos(array $filters = []): LengthAwarePaginator
    {
        $query = Daparto::query();

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply brand filter
        if (!empty($filters['brand'])) {
            $query->byBrand($filters['brand']);
        }

        // Apply price range filter
        if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
            $query->byPriceRange($filters['min_price'], $filters['max_price']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new daparto
     */
    public function createDaparto(array $data): Daparto
    {
        return Daparto::create($data);
    }

    /**
     * Update a daparto with comprehensive error handling and validation
     *
     * @param Daparto $daparto
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function updateDaparto(Daparto $daparto, array $data): array
    {
        try {
            // Store original data for comparison
            $originalData = $daparto->toArray();
            
            // Check if there are any changes
            $hasChanges = false;
            $changedFields = [];
            
            foreach ($data as $field => $value) {
                if (array_key_exists($field, $originalData) && $originalData[$field] != $value) {
                    $hasChanges = true;
                    $changedFields[$field] = [
                        'old' => $originalData[$field],
                        'new' => $value
                    ];
                }
            }
            
            // If no changes, return early with appropriate message
            if (!$hasChanges) {
                return [
                    'success' => true,
                    'message' => 'No changes detected',
                    'data' => new \App\Http\Resources\DapartoResource($daparto),
                    'changes' => [],
                    'unchanged' => true
                ];
            }
            
            // Validate business rules before update
            $this->validateBusinessRules($daparto, $data);
            
            // Perform the update
            $daparto->update($data);
            
            // Refresh the model to get updated data
            $updatedDaparto = $daparto->fresh();
            
            // Log the update for audit purposes
            $this->logUpdate($daparto, $originalData, $data);
            
            return [
                'success' => true,
                'message' => 'Daparto updated successfully',
                'data' => new \App\Http\Resources\DapartoResource($updatedDaparto),
                'changes' => $changedFields,
                'unchanged' => false
            ];
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-specific errors
            throw new \Exception('Database update failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Handle other errors
            throw new \Exception('Update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate business rules before update
     *
     * @param Daparto $daparto
     * @param array $data
     * @throws \Exception
     */
    private function validateBusinessRules(Daparto $daparto, array $data): void
    {
        // Check if interne_artikelnummer is being changed (should not be allowed)
        if (isset($data['interne_artikelnummer']) && $data['interne_artikelnummer'] !== $daparto->interne_artikelnummer) {
            throw new \Exception('Internal article number cannot be changed once created');
        }
        
        // Validate price changes (business rule: price cannot be negative)
        if (isset($data['preis']) && $data['preis'] < 0) {
            throw new \Exception('Price cannot be negative');
        }
        
        // Validate condition changes (business rule: condition must be between 0-5)
        if (isset($data['zustand']) && ($data['zustand'] < 0 || $data['zustand'] > 5)) {
            throw new \Exception('Condition must be between 0 and 5');
        }
        
        // Validate deposit changes (business rule: deposit cannot be negative)
        if (isset($data['pfand']) && $data['pfand'] < 0) {
            throw new \Exception('Deposit cannot be negative');
        }
        
        // Validate shipping class changes (business rule: shipping class must be between 1-5)
        if (isset($data['versandklasse']) && ($data['versandklasse'] < 1 || $data['versandklasse'] > 5)) {
            throw new \Exception('Shipping class must be between 1 and 5');
        }
        
        // Validate delivery time changes (business rule: delivery time must be positive)
        if (isset($data['lieferzeit']) && $data['lieferzeit'] < 1) {
            throw new \Exception('Delivery time must be at least 1 day');
        }
    }
    
    /**
     * Log update for audit purposes
     *
     * @param Daparto $daparto
     * @param array $originalData
     * @param array $newData
     */
    private function logUpdate(Daparto $daparto, array $originalData, array $newData): void
    {
        // In a production environment, you would log this to a proper logging system
        // For now, we'll use Laravel's built-in logging
        \Illuminate\Support\Facades\Log::info('Daparto updated', [
            'daparto_id' => $daparto->id,
            'interne_artikelnummer' => $daparto->interne_artikelnummer,
            'changes' => array_diff_assoc($newData, $originalData),
            'updated_at' => now(),
            'user_id' => 'system' // Will be 'system' when no auth
        ]);
    }

    /**
     * Delete a daparto (soft delete)
     */
    public function deleteDaparto(Daparto $daparto): bool
    {
        return $daparto->delete();
    }

    /**
     * Restore a soft-deleted daparto
     */
    public function restoreDaparto(int $dapartoId): bool
    {
        $daparto = Daparto::withTrashed()->findOrFail($dapartoId);
        return $daparto->restore();
    }

    /**
     * Get daparto statistics
     */
    public function getDapartoStats(): array
    {
        return [
            'total_parts' => Daparto::count(),
            'total_brands' => Daparto::distinct('teilemarke_teilenummer')->count(),
            'average_price' => Daparto::avg('preis'),
            'total_value' => Daparto::sum('preis'),
            'deleted_parts' => Daparto::onlyTrashed()->count(),
        ];
    }

    /**
     * Get daparto by interne artikelnummer
     */
    public function getDapartoByNumber(string $interneArtikelnummer): ?Daparto
    {
        return Daparto::where('interne_artikelnummer', $interneArtikelnummer)->first();
    }

    /**
     * Process CSV file and return summary
     */
    public function processCsvFile($file): array
    {
        $results = [
            'total_rows' => 0,
            'inserted' => 0,
            'updated' => 0,
            'errors' => [],
            'file_name' => $file->getClientOriginalName(),
        ];

        try {
            $handle = fopen($file->getPathname(), 'r');
            $headers = fgetcsv($handle, 0, ';');

            // Skip header row
            $rowNumber = 1;

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;
                $results['total_rows']++;

                try {
                    $data = $this->mapCsvRowToData($headers, $row);

                    if ($data) {
                        $existing = Daparto::where('interne_artikelnummer', $data['interne_artikelnummer'])->first();

                        if ($existing) {
                            $existing->update($data);
                            $results['updated']++;
                        } else {
                            Daparto::create($data);
                            $results['inserted']++;
                        }
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                        'data' => $row
                    ];
                }
            }

            fclose($handle);
        } catch (\Exception $e) {
            $results['errors'][] = [
                'row' => 0,
                'error' => 'File processing error: ' . $e->getMessage(),
                'data' => []
            ];
        }

        return $results;
    }

    /**
     * Map CSV row to database fields
     */
    private function mapCsvRowToData(array $headers, array $row): ?array
    {
        if (count($headers) !== count($row)) {
            return null;
        }

        $data = array_combine($headers, $row);

        // Map CSV columns to database fields
        return [
            'tiltle' => $data['tiltle'] ?? null,
            'teilemarke_teilenummer' => $data['Teilemarke,  Teilenummer'] ?? null,
            'preis' => $this->parsePrice($data['Preis'] ?? '0'),
            'interne_artikelnummer' => $data['interne Artikelnummer'] ?? null,
            'zustand' => (int)($data['Zustand'] ?? 0),
            'pfand' => (int)($data['Pfand'] ?? 0),
            'versandklasse' => (int)($data['Versandklasse'] ?? 0),
            'lieferzeit' => (int)($data['Lieferzeit'] ?? 0),
        ];
    }

    /**
     * Parse price from German format (comma as decimal separator)
     */
    private function parsePrice(string $price): float
    {
        // Remove any non-numeric characters except comma and dot
        $cleanPrice = preg_replace('/[^0-9,.]/', '', $price);

        // Replace comma with dot for proper decimal parsing
        $cleanPrice = str_replace(',', '.', $cleanPrice);

        return (float) $cleanPrice;
    }
}

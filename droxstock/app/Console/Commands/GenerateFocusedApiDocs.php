<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateFocusedApiDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-focused-api-docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating focused Daparto API documentation...');

        // First generate the full API documentation
        $this->call('scramble:export');

        // Read the generated api.json file
        $apiPath = base_path('api.json');
        if (!file_exists($apiPath)) {
            $this->error('API documentation not found. Please run scramble:export first.');
            return 1;
        }

        $apiContent = json_decode(file_get_contents($apiPath), true);

        // Filter to only include the 6 focused endpoints with specific methods
        // Note: Removing "Store a newly created daparto" (POST /v1/dapartos)
        // and "Register a new user (requires admin approval)" (/v1/register/user)
        $allowedEndpoints = [
            '/v1/auth/login' => ['post'],
            '/v1/auth/register' => ['post'], // Self-registration (no admin approval)
            '/v1/auth/refresh' => ['post'],
            '/v1/auth/logout' => ['post'],
            '/v1/auth/me' => ['get'],
            '/v1/dapartos' => ['get'], // Only GET, not POST (Store)
            '/v1/dapartos-stats' => ['get'],
            '/v1/dapartos-by-number/{interneArtikelnummer}' => ['get']
        ];

        $filteredPaths = [];
        foreach ($apiContent['paths'] as $path => $pathItem) {
            if (array_key_exists($path, $allowedEndpoints)) {
                $allowedMethods = $allowedEndpoints[$path];
                $filteredPathItem = [];

                foreach ($pathItem as $method => $methodData) {
                    if (in_array(strtolower($method), $allowedMethods)) {
                        $filteredPathItem[$method] = $methodData;
                    }
                }

                if (!empty($filteredPathItem)) {
                    $filteredPaths[$path] = $filteredPathItem;
                }
            }
        }

        // Update the API content
        $apiContent['paths'] = $filteredPaths;
        $apiContent['info']['title'] = 'DroxStock Daparto Focused API';
        $apiContent['info']['description'] = 'Essential APIs for DroxStock Daparto system - Authentication and Core Inventory Management. This documentation includes only the core APIs needed for basic system operation (excluding admin-only functions like creating new dapartos or admin-approval registration).';

        // Filter schemas to only include ones referenced by the focused APIs
        $referencedSchemas = $this->getReferencedSchemas($filteredPaths);
        if (isset($apiContent['components']['schemas'])) {
            $filteredSchemas = [];
            foreach ($apiContent['components']['schemas'] as $schemaName => $schemaData) {
                if (in_array($schemaName, $referencedSchemas)) {
                    $filteredSchemas[$schemaName] = $schemaData;
                }
            }
            $apiContent['components']['schemas'] = $filteredSchemas;
        }

        // Export to focused JSON
        $outputPath = base_path('api-focused.json');
        $json = json_encode($apiContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputPath, $json);

        // Create a separate Scramble configuration for focused APIs
        $this->createFocusedScrambleConfig();

        $this->info("Focused API documentation generated successfully!");
        $this->info("Output file: {$outputPath}");
        $this->info("Total endpoints included: " . count($filteredPaths));

        foreach ($filteredPaths as $path => $pathItem) {
            $this->line("  - {$path}");
        }

        $this->info("\n🎯 To view focused docs with Scramble UI:");
        $this->info("   Visit: http://droxstock.test/docs/daparto-focused-scramble");
        $this->info("\n📚 To view full docs with Scramble UI:");
        $this->info("   Visit: http://droxstock.test/docs/api");
    }

    private function createFocusedScrambleConfig()
    {
        $configContent = '<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    "api_path" => "api",
    "api_domain" => null,
    "export_path" => "api-focused.json",

    "info" => [
        "version" => env("API_VERSION", "1.0.0"),
        "description" => "Essential APIs for DroxStock Daparto system - Authentication and Core Inventory Management. This documentation includes only the 8 core APIs needed for basic system operation.",
    ],

    "ui" => [
        "title" => "DroxStock Daparto Focused API",
        "theme" => "light",
        "hide_try_it" => false,
        "hide_schemas" => false,
        "logo" => "",
        "try_it_credentials_policy" => "include",
        "layout" => "responsive",
    ],

    "servers" => null,
    "enum_descriptions_strategy" => "description",

    "extensions" => [
        \App\Scramble\SecurityExtension::class,
    ],

    "middleware" => [
        "web",
    ],
];';

        $configPath = config_path('scramble-focused.php');
        file_put_contents($configPath, $configContent);

        $this->info("Focused Scramble configuration created: {$configPath}");
    }

    /**
     * Extract all schema references from the filtered API paths
     */
    private function getReferencedSchemas($paths)
    {
        $referencedSchemas = [];

        foreach ($paths as $path => $pathData) {
            foreach ($pathData as $method => $methodData) {
                // Check request body schemas
                if (isset($methodData['requestBody']['content'])) {
                    foreach ($methodData['requestBody']['content'] as $contentType => $contentData) {
                        if (isset($contentData['schema']['$ref'])) {
                            $schemaRef = $contentData['schema']['$ref'];
                            $schemaName = str_replace('#/components/schemas/', '', $schemaRef);
                            $referencedSchemas[] = $schemaName;
                        }
                    }
                }

                // Check response schemas
                if (isset($methodData['responses'])) {
                    foreach ($methodData['responses'] as $statusCode => $responseData) {
                        if (isset($responseData['content'])) {
                            foreach ($responseData['content'] as $contentType => $contentData) {
                                if (isset($contentData['schema'])) {
                                    $this->extractSchemaReferences($contentData['schema'], $referencedSchemas);
                                }
                            }
                        }
                    }
                }
            }
        }

        return array_unique($referencedSchemas);
    }

    /**
     * Recursively extract schema references from nested schema structures
     */
    private function extractSchemaReferences($schema, &$referencedSchemas)
    {
        if (isset($schema['$ref'])) {
            $schemaName = str_replace('#/components/schemas/', '', $schema['$ref']);
            $referencedSchemas[] = $schemaName;
        }

        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propertyData) {
                $this->extractSchemaReferences($propertyData, $referencedSchemas);
            }
        }

        if (isset($schema['items'])) {
            $this->extractSchemaReferences($schema['items'], $referencedSchemas);
        }

        if (isset($schema['additionalProperties']) && is_array($schema['additionalProperties'])) {
            $this->extractSchemaReferences($schema['additionalProperties'], $referencedSchemas);
        }
    }
}

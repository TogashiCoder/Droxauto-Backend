<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Focused Daparto API Documentation with Real Scramble UI (No Custom Blade)
Route::get('/docs/daparto-focused-scramble', function () {
    // Load the focused API specification directly
    $focusedApiPath = base_path('api-focused.json');
    if (!file_exists($focusedApiPath)) {
        return response()->json(['error' => 'Focused API file not found'], 404);
    }

    // Load the focused API specification
    $focusedSpec = json_decode(file_get_contents($focusedApiPath), true);

    // Get the main Scramble config for UI settings
    $mainConfig = \Dedoc\Scramble\Scramble::getGeneratorConfig('default');

    // Create a focused config with custom UI settings
    $focusedConfig = new \Dedoc\Scramble\GeneratorConfig();
    $focusedConfig->useConfig([
        'info' => [
            'title' => 'DroxStock Daparto Focused API',
            'description' => 'Essential APIs for DroxStock Daparto system - Authentication and Core Inventory Management. This documentation includes only the 8 core APIs needed for basic system operation.',
        ],
        'ui' => [
            'title' => 'DroxStock Daparto Focused API',
            'theme' => 'light',
            'hide_try_it' => false,
            'hide_schemas' => false,
            'logo' => '',
            'try_it_credentials_policy' => 'include',
            'layout' => 'responsive',
        ],
    ]);

    // Use Scramble's native UI view with the focused spec
    return view('scramble::docs', [
        'spec' => $focusedSpec,
        'config' => $focusedConfig,
    ]);
});

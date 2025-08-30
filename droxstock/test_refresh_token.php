<?php

/**
 * Test Script for Refresh Token Functionality
 * This script demonstrates how to use the refresh token endpoint
 */

// Configuration
$baseUrl = 'http://127.0.0.1:8000';
$refreshToken = 'cead0de0-d973-4bbf-8f9f-949edb2ebec3';

echo "🔄 Testing Refresh Token Functionality\n";
echo "=====================================\n\n";

// Test 1: Refresh Token Request
echo "📤 Sending Refresh Token Request...\n";
echo "URL: {$baseUrl}/api/v1/auth/refresh\n";
echo "Refresh Token: {$refreshToken}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/auth/refresh');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['refresh_token' => $refreshToken]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📥 Response Status: HTTP {$httpCode}\n";
echo "📥 Response Body:\n";

if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        if ($data['success']) {
            echo "✅ SUCCESS: Token refreshed successfully!\n";
            echo "🆕 New Access Token: " . substr($data['data']['access_token'], 0, 50) . "...\n";
            echo "🆕 New Refresh Token: " . $data['data']['refresh_token'] . "\n";
            echo "⏰ Expires In: " . $data['data']['expires_in'] . " seconds\n";
        } else {
            echo "❌ FAILED: " . $data['message'] . "\n";
            if (isset($data['error'])) {
                echo "🔍 Error Type: " . $data['error'] . "\n";
            }
        }
    } else {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . $response . "\n";
    }
} else {
    echo "❌ No response received\n";
}

echo "\n";
echo "📋 How to Use in Postman:\n";
echo "1. Import the Droxstock_API_Collection.json\n";
echo "2. Import the Droxstock_API_Environment.json\n";
echo "3. Use the '🔄 Refresh Token' request\n";
echo "4. The new tokens will be automatically saved!\n";
echo "\n";
echo "🎯 Benefits of Refresh Tokens:\n";
echo "✅ No need to login every 15 days\n";
echo "✅ Seamless user experience\n";
echo "✅ Automatic token renewal\n";
echo "✅ Secure token rotation\n";

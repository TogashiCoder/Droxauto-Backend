<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DroxStock Daparto Focused API Documentation</title>

    <!-- Scramble UI CSS -->
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/web-components.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .header p {
            margin: 1rem 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .api-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .api-info h2 {
            color: #333;
            margin-top: 0;
        }

        .endpoints-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .endpoint-item {
            border-bottom: 1px solid #eee;
            padding: 1rem;
        }

        .endpoint-item:last-child {
            border-bottom: none;
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .method {
            background: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-right: 1rem;
            min-width: 60px;
            text-align: center;
        }

        .method.post {
            background: #28a745;
        }

        .method.get {
            background: #17a2b8;
        }

        .endpoint-path {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 1rem;
            color: #333;
        }

        .endpoint-summary {
            color: #666;
            margin-left: 76px;
        }

        .endpoint-tags {
            margin-left: 76px;
            margin-top: 0.5rem;
        }

        .tag {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }

        .footer {
            text-align: center;
            padding: 2rem;
            color: #666;
            border-top: 1px solid #eee;
            margin-top: 2rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🚀 DroxStock Daparto Focused API</h1>
        <p>Essential APIs for DroxStock Daparto system - Authentication and Core Inventory Management</p>
    </div>

    <div class="container">
        <div class="api-info">
            <h2>📋 API Overview</h2>
            <p>This documentation includes only the <strong>8 core APIs</strong> needed for basic system operation. For
                complete API reference, please refer to the full OpenAPI specification.</p>
            <p><strong>Base URL:</strong> <code>http://droxstock.test/api/v1</code></p>
            <p><strong>OpenAPI Version:</strong> {{ $apiContent['openapi'] ?? '3.1.0' }}</p>
        </div>

        <div class="endpoints-list">
            <h2 style="padding: 1rem; margin: 0; background: #f8f9fa; border-bottom: 1px solid #eee;">🔐 Available
                Endpoints</h2>

            @foreach ($apiContent['paths'] as $path => $pathItem)
                @foreach ($pathItem as $method => $operation)
                    <div class="endpoint-item">
                        <div class="endpoint-header">
                            <span class="method {{ strtoupper($method) }}">{{ strtoupper($method) }}</span>
                            <span class="endpoint-path">{{ $path }}</span>
                        </div>
                        <div class="endpoint-summary">
                            {{ $operation['summary'] ?? 'No summary available' }}
                        </div>
                        @if (isset($operation['tags']) && count($operation['tags']) > 0)
                            <div class="endpoint-tags">
                                @foreach ($operation['tags'] as $tag)
                                    <span class="tag">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>

        <div class="api-info">
            <h2>🔐 Authentication</h2>
            <p>Protected endpoints require <strong>Bearer Token</strong> authentication. Include your JWT token in the
                Authorization header:</p>
            <code>Authorization: Bearer {your_access_token}</code>

            <h3 style="margin-top: 1.5rem;">Protected Endpoints:</h3>
            <ul>
                <li><code>POST /v1/auth/logout</code> - Logout user (revoke token)</li>
                <li><code>GET /v1/auth/me</code> - Get authenticated user info</li>
                <li><code>GET /v1/dapartos</code> - Display a listing of dapartos</li>
                <li><code>GET /v1/dapartos-stats</code> - Get daparto statistics</li>
                <li><code>GET /v1/dapartos-by-number/{interneArtikelnummer}</code> - Get daparto by article number</li>
            </ul>

            <h3 style="margin-top: 1.5rem;">Public Endpoints:</h3>
            <ul>
                <li><code>POST /v1/auth/login</code> - Login user and create token</li>
                <li><code>POST /v1/auth/refresh</code> - Refresh user token</li>
                <li><code>POST /v1/register/user</code> - Register a new user (requires admin approval)</li>
            </ul>
        </div>

        <div class="api-info">
            <h2>📖 Getting Started</h2>
            <ol>
                <li><strong>Register a new user</strong> using <code>POST /v1/register/user</code></li>
                <li><strong>Wait for admin approval</strong> (you'll receive an email)</li>
                <li><strong>Login</strong> using <code>POST /v1/auth/login</code> to get access token</li>
                <li><strong>Use the access token</strong> in the Authorization header for protected endpoints</li>
                <li><strong>Refresh token</strong> when needed using <code>POST /v1/auth/refresh</code></li>
            </ol>
        </div>
    </div>

    <div class="footer">
        <p>Generated by DroxStock Scramble API Documentation</p>
        <p>For complete API reference, visit: <a href="/docs/api">Full API Documentation</a></p>
    </div>
</body>

</html>

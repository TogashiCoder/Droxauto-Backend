<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DroxStock Daparto Focused API - Scramble UI</title>

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
            padding: 1.5rem;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 300;
        }

        .header p {
            margin: 0.5rem 0 0 0;
            font-size: 1rem;
            opacity: 0.9;
        }

        .scramble-container {
            height: calc(100vh - 120px);
            width: 100%;
        }

        .info-bar {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .info-bar strong {
            color: #495057;
        }

        .endpoints-summary {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 1rem;
            padding: 1rem;
        }

        .endpoints-summary h3 {
            margin-top: 0;
            color: #333;
        }

        .endpoint-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .endpoint-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem;
        }

        .endpoint-method {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .endpoint-method.post {
            background: #28a745;
        }

        .endpoint-method.get {
            background: #17a2b8;
        }

        .endpoint-path {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .endpoint-summary {
            color: #666;
            font-size: 0.9rem;
        }

        .endpoint-tags {
            margin-top: 0.5rem;
        }

        .tag {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.7rem;
            margin-right: 0.3rem;
            margin-bottom: 0.3rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🚀 DroxStock Daparto Focused API</h1>
        <p>Essential APIs for DroxStock Daparto system - Authentication and Core Inventory Management</p>
    </div>

    <div class="info-bar">
        <strong>📋 Focused API Documentation</strong> - Showing only the 8 core APIs needed for basic system operation
    </div>

    <div class="endpoints-summary">
        <h3>🔐 Available Endpoints ({{ count($apiContent['paths']) }} APIs)</h3>
        <div class="endpoint-list">
            @foreach ($apiContent['paths'] as $path => $pathItem)
                @foreach ($pathItem as $method => $operation)
                    <div class="endpoint-card">
                        <div class="endpoint-method {{ strtolower($method) }}">{{ strtoupper($method) }}</div>
                        <div class="endpoint-path">{{ $path }}</div>
                        <div class="endpoint-summary">{{ $operation['summary'] ?? 'No summary available' }}</div>
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
    </div>

    <!-- Scramble UI Container -->
    <div class="scramble-container">
        <elements-api apiDescriptionUrl="/docs/daparto-focused-json" router="hash" layout="sidebar">
        </elements-api>
    </div>

    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <script>
        // Initialize Scramble UI
        document.addEventListener('DOMContentLoaded', function() {
            // The elements-api component will automatically load the API specification
            console.log('DroxStock Daparto Focused API Documentation loaded');
        });
    </script>
</body>

</html>

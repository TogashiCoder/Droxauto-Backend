<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Processing Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .status-success {
            color: #28a745;
            font-weight: bold;
            font-size: 18px;
        }
        .status-failed {
            color: #dc3545;
            font-weight: bold;
            font-size: 18px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #007bff;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .error-section {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .error-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .error-item {
            background-color: white;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 3px solid #ffc107;
        }
        .performance-section {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .recommendations {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .recommendation-item {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        .quality-score {
            font-size: 36px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .quality-excellent { color: #28a745; }
        .quality-good { color: #17a2b8; }
        .quality-average { color: #ffc107; }
        .quality-poor { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 CSV Processing Report</h1>
            <p>File: <strong>{{ $fileName }}</strong></p>
            <p>Processed by: <strong>{{ $userName }}</strong></p>
            <p>Date: <strong>{{ now()->format('F j, Y \a\t g:i A') }}</strong></p>
            
            @if($processingResults['success'])
                <div class="status-success">✅ Processing Completed Successfully</div>
            @else
                <div class="status-failed">❌ Processing Failed</div>
            @endif
        </div>

        @if(isset($processingResults['validation_summary']['data_quality_score']))
            <div class="quality-score 
                @if($processingResults['validation_summary']['data_quality_score'] >= 95) quality-excellent
                @elseif($processingResults['validation_summary']['data_quality_score'] >= 80) quality-good
                @elseif($processingResults['validation_summary']['data_quality_score'] >= 60) quality-average
                @else quality-poor
                @endif">
                {{ $processingResults['validation_summary']['data_quality_score'] }}%
            </div>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">Data Quality Score</p>
        @endif

        <div class="stats-grid">
            @if(isset($processingResults['processing_stats']['total_rows']))
                <div class="stat-card">
                    <div class="stat-number">{{ $processingResults['processing_stats']['total_rows'] }}</div>
                    <div class="stat-label">Total Rows</div>
                </div>
            @endif

            @if(isset($processingResults['processing_stats']['successful_rows']))
                <div class="stat-card">
                    <div class="stat-number">{{ $processingResults['processing_stats']['successful_rows'] }}</div>
                    <div class="stat-label">Successful Rows</div>
                </div>
            @endif

            @if(isset($processingResults['processing_stats']['new_rows']))
                <div class="stat-card">
                    <div class="stat-number">{{ $processingResults['processing_stats']['new_rows'] }}</div>
                    <div class="stat-label">New Records</div>
                </div>
            @endif

            @if(isset($processingResults['processing_stats']['updated_rows']))
                <div class="stat-card">
                    <div class="stat-number">{{ $processingResults['processing_stats']['updated_rows'] }}</div>
                    <div class="stat-label">Updated Records</div>
                </div>
            @endif

            @if(isset($processingResults['processing_stats']['failed_rows']))
                <div class="stat-card">
                    <div class="stat-number">{{ $processingResults['processing_stats']['failed_rows'] }}</div>
                    <div class="stat-label">Failed Rows</div>
                </div>
            @endif

            @if(isset($processingResults['processing_stats']['duplicate_rows']))
                <div class="stat-card">
                    <div class="stat-number">{{ $processingResults['processing_stats']['duplicate_rows'] }}</div>
                    <div class="stat-label">Duplicate Rows</div>
                </div>
            @endif
        </div>

        @if(isset($processingResults['performance']))
            <div class="performance-section">
                <h3>🚀 Performance Metrics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">{{ $processingResults['performance']['duration'] }}s</div>
                        <div class="stat-label">Processing Time</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($processingResults['performance']['memory_peak'] / 1024 / 1024, 2) }} MB</div>
                        <div class="stat-label">Peak Memory</div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($processingResults['errors']) && count($processingResults['errors']) > 0)
            <div class="error-section">
                <h3 class="error-title">⚠️ Processing Errors</h3>
                <p><strong>{{ count($processingResults['errors']) }}</strong> errors were encountered during processing.</p>
                
                @foreach(array_slice($processingResults['errors'], 0, 5) as $error)
                    <div class="error-item">
                        <strong>Row {{ $error['row'] }}:</strong> {{ implode(', ', $error['errors']) }}
                    </div>
                @endforeach

                @if(count($processingResults['errors']) > 5)
                    <p><em>... and {{ count($processingResults['errors']) - 5 }} more errors. Check the system logs for complete details.</em></p>
                @endif
            </div>
        @endif

        @if(isset($processingResults['recommendations']) && count($processingResults['recommendations']) > 0)
            <div class="recommendations">
                <h3>💡 Recommendations</h3>
                @foreach($processingResults['recommendations'] as $recommendation)
                    <div class="recommendation-item">
                        {{ $recommendation }}
                    </div>
                @endforeach
            </div>
        @endif

        @if(isset($processingResults['file_info']))
            <div class="performance-section">
                <h3>📁 File Information</h3>
                <p><strong>File Name:</strong> {{ $processingResults['file_info']['name'] }}</p>
                <p><strong>File Size:</strong> {{ number_format($processingResults['file_info']['size'] / 1024 / 1024, 2) }} MB</p>
                <p><strong>Upload Time:</strong> {{ \Carbon\Carbon::parse($processingResults['file_info']['uploaded_at'])->format('F j, Y \a\t g:i A') }}</p>
            </div>
        @endif

        <div class="footer">
            <p>This report was automatically generated by the Daparto CSV Processing System.</p>
            <p>If you have any questions, please contact your system administrator.</p>
        </div>
    </div>
</body>
</html>

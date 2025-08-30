<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>CSV Processing Complete - Detailed Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #28a745;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }

        .content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }

        .stats {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }

        .data-quality {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
        }

        .performance {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }

        .file-info {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #6f42c1;
        }

        .summary {
            background: #e8f5e8;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border: 2px solid #28a745;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }

        .metric {
            display: inline-block;
            width: 48%;
            margin: 5px 0;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 3px;
        }

        .metric strong {
            color: #28a745;
        }

        .quality-score {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            padding: 20px;
            background: #e8f5e8;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            line-height: 40px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>✅ CSV Processing Complete - Detailed Report</h1>
            <p>Your data has been successfully imported into the system</p>
        </div>

        <div class="content">
            <p>Hello,</p>

            <p>Your CSV file <strong>{{ $fileName }}</strong> has been processed successfully! Here's your
                comprehensive processing report:</p>

            <!-- File Information -->
            <div class="file-info">
                <h3>📁 File Information</h3>
                <div class="metric"><strong>File Name:</strong> {{ $fileName }}</div>
                <div class="metric"><strong>File Size:</strong> {{ number_format($fileSize / 1024 / 1024, 2) }} MB</div>
                <div class="metric"><strong>Upload Time:</strong> {{ $uploadedAt }}</div>
                <div class="metric"><strong>Processing Started:</strong> {{ $processingStartedAt }}</div>
                <div class="metric"><strong>Completed At:</strong> {{ $completedAt }}</div>
            </div>

            <!-- Data Quality Score -->
            <div class="data-quality">
                <h3>📊 Data Quality Assessment</h3>
                <div class="quality-score">{{ $dataQualityScore }}%</div>
                <p style="text-align: center; margin-top: 10px;"><strong>Overall Data Quality Score</strong></p>

                <div style="margin-top: 20px;">
                    <div class="metric"><strong>Structure Valid:</strong> {{ $structureValid ? '✅ Yes' : '❌ No' }}</div>
                    <div class="metric"><strong>Headers Valid:</strong> {{ $headersValid ? '✅ Yes' : '❌ No' }}</div>
                    <div class="metric"><strong>Data Format:</strong>
                        {{ $dataFormatValid ? '✅ Valid' : '❌ Issues Found' }}</div>
                    <div class="metric"><strong>Encoding:</strong>
                        {{ $encodingValid ? '✅ UTF-8' : '❌ Encoding Issues' }}</div>
                </div>
            </div>

            <!-- Processing Statistics -->
            <div class="stats">
                <h3>📈 Processing Statistics</h3>
                <div class="metric"><strong>Total Rows:</strong> {{ number_format($totalRows) }}</div>
                <div class="metric"><strong>Valid Rows:</strong> {{ number_format($validRows) }}</div>
                <div class="metric"><strong>Invalid Rows:</strong> {{ number_format($invalidRows) }}</div>
                <div class="metric"><strong>Success Rate:</strong>
                    {{ number_format(($validRows / $totalRows) * 100, 1) }}%</div>

                <h4 style="margin-top: 20px; color: #28a745;">Database Operations</h4>
                <div class="metric"><strong>New Records Inserted:</strong> {{ number_format($inserted) }}</div>
                <div class="metric"><strong>Existing Records Updated:</strong> {{ number_format($updated) }}</div>
                <div class="metric"><strong>Records Skipped:</strong> {{ number_format($skipped) }}</div>
                <div class="metric"><strong>Duplicate Records:</strong> {{ number_format($duplicates) }}</div>
            </div>

            <!-- Performance Metrics -->
            <div class="performance">
                <h3>⚡ Performance Metrics</h3>
                <div class="metric"><strong>Total Processing Time:</strong> {{ number_format($processingTime, 2) }}
                    seconds</div>
                <div class="metric"><strong>Average Time per Row:</strong>
                    {{ number_format(($processingTime / $totalRows) * 1000, 2) }} ms</div>
                <div class="metric"><strong>Memory Peak Usage:</strong>
                    {{ number_format($memoryPeak / 1024 / 1024, 2) }} MB</div>
                <div class="metric"><strong>Processing Speed:</strong>
                    {{ number_format($totalRows / $processingTime, 0) }} rows/second</div>
            </div>

            <!-- Data Validation Details -->
            <div class="stats">
                <h3>🔍 Data Validation Details</h3>
                <div class="metric"><strong>Required Fields Present:</strong>
                    {{ $requiredFieldsPresent ? '✅ Yes' : '❌ No' }}</div>
                <div class="metric"><strong>Data Type Validation:</strong>
                    {{ $dataTypeValidation ? '✅ Passed' : '❌ Failed' }}</div>
                <div class="metric"><strong>Range Validation:</strong> {{ $rangeValidation ? '✅ Passed' : '❌ Failed' }}
                </div>
                <div class="metric"><strong>Format Validation:</strong>
                    {{ $formatValidation ? '✅ Passed' : '❌ Failed' }}</div>

                @if ($validationErrors > 0)
                    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 3px;">
                        <strong>⚠️ Validation Issues Found:</strong> {{ $validationErrors }} issues detected
                    </div>
                @endif
            </div>

            <!-- Business Intelligence -->
            <div class="data-quality">
                <h3>💼 Business Intelligence</h3>
                <div class="metric"><strong>Total Value:</strong> €{{ number_format($totalValue, 2) }}</div>
                <div class="metric"><strong>Average Price:</strong> €{{ number_format($averagePrice, 2) }}</div>
                <div class="metric"><strong>Unique Brands:</strong> {{ $uniqueBrands }}</div>
                <div class="metric"><strong>Unique Categories:</strong> {{ $uniqueCategories }}</div>
                <div class="metric"><strong>Stock Status:</strong> {{ $inStockCount }} in stock,
                    {{ $outOfStockCount }} out of stock</div>
                <div class="metric"><strong>Condition Distribution:</strong> New: {{ $newConditionCount }}, Used:
                    {{ $usedConditionCount }}</div>
            </div>

            <!-- Summary & Recommendations -->
            <div class="summary">
                <h3>📋 Executive Summary</h3>
                <p><strong>Overall Status:</strong> ✅ <span style="color: #28a745;">SUCCESS</span></p>
                <p><strong>Data Quality:</strong>
                    {{ $dataQualityScore >= 90 ? 'Excellent' : ($dataQualityScore >= 80 ? 'Good' : ($dataQualityScore >= 70 ? 'Fair' : 'Needs Improvement')) }}
                </p>
                <p><strong>Processing Efficiency:</strong>
                    {{ $processingTime < 30 ? 'Fast' : ($processingTime < 60 ? 'Normal' : 'Slow') }}
                    ({{ number_format($processingTime, 1) }}s)</p>

                @if ($invalidRows > 0)
                    <p style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 3px;">
                        <strong>⚠️ Note:</strong> {{ $invalidRows }} rows had issues but were processed. Review the
                        data for accuracy.
                    </p>
                @endif

                <h4 style="margin-top: 15px;">Recommendations:</h4>
                <ul>
                    @if ($dataQualityScore < 90)
                        <li>Review data quality issues to improve accuracy</li>
                    @endif
                    @if ($duplicates > 0)
                        <li>Consider implementing duplicate detection rules</li>
                    @endif
                    @if ($processingTime > 60)
                        <li>Consider optimizing CSV structure for faster processing</li>
                    @endif
                    @if ($invalidRows > 0)
                        <li>Review validation rules and data format requirements</li>
                    @endif
                </ul>
            </div>

            <p style="margin-top: 20px;"><strong>Next Steps:</strong> Your data is now available in the system. You can
                access it through the API endpoints or admin interface.</p>
        </div>

        <div class="footer">
            <p>This is an automated detailed report from your CSV processing system.</p>
            <p>Generated on {{ $completedAt }} | Processing completed successfully</p>
        </div>
    </div>
</body>

</html>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>CSV Processing Failed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #dc3545;
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

        .error {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>❌ CSV Processing Failed</h1>
        </div>

        <div class="content">
            <p>Hello,</p>

            <p>Unfortunately, there was an error processing your CSV file <strong>{{ $fileName }}</strong>.</p>

            <div class="error">
                <h3>Error Details:</h3>
                <p><strong>Error:</strong> {{ $error }}</p>
                <p><strong>Failed at:</strong> {{ $failedAt }}</p>
            </div>

            <p>Please check the following:</p>
            <ul>
                <li>Ensure your CSV file is properly formatted</li>
                <li>Check that all required columns are present</li>
                <li>Verify that the data types are correct</li>
                <li>Make sure the file is not corrupted</li>
            </ul>

            <p>If the problem persists, please contact support or try uploading the file again.</p>
        </div>

        <div class="footer">
            <p>This is an automated notification from your CSV processing system.</p>
        </div>
    </div>
</body>

</html>

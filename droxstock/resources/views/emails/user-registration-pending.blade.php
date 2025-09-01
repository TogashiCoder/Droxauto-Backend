<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Registration Pending</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px; }
        .content { padding: 20px; background: #fff; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        .status { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Account Registration Pending</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>

            <p>Thank you for registering with our system! Your account has been created successfully and is currently pending admin approval.</p>

            <div class="status">
                <strong>Status:</strong> Pending Approval<br>
                <strong>Registration Date:</strong> {{ $user->registration_date ? $user->registration_date->format('F j, Y \a\t g:i A') : 'N/A' }}
            </div>

            <p>What happens next?</p>
            <ul>
                <li>Our administrators will review your registration</li>
                <li>You will receive an email once your account is approved or rejected</li>
                <li>Once approved, you can log in and access the system</li>
            </ul>

            <div class="info">
                <strong>Note:</strong> This process typically takes 24-48 hours during business days.
            </div>

            <p>If you have any questions or need to provide additional information, please contact us at <strong>{{ $adminEmail }}</strong>.</p>

            <p>Thank you for your patience!</p>

            <p>Best regards,<br>
            The System Administration Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>

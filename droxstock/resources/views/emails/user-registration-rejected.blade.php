<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Registration Rejected</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8d7da; color: #721c24; padding: 20px; text-align: center; border-radius: 5px; }
        .content { padding: 20px; background: #fff; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        .status { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 20px 0; }
        .reason { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 20px 0; }
        .contact { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Account Registration Rejected</h1>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            
            <p>We regret to inform you that your account registration has been rejected by our administrators.</p>
            
            <div class="status">
                <strong>Status:</strong> ❌ Rejected<br>
                <strong>Rejection Date:</strong> {{ now()->format('F j, Y \a\t g:i A') }}
            </div>
            
            @if($rejectionReason)
            <div class="reason">
                <strong>Reason for Rejection:</strong><br>
                {{ $rejectionReason }}
            </div>
            @endif
            
            <p>What this means:</p>
            <ul>
                <li>Your account has not been activated</li>
                <li>You cannot access the system at this time</li>
                <li>Your registration data has been removed</li>
            </ul>
            
            <p>If you believe this decision was made in error, or if you would like to provide additional information, please contact us.</p>
            
            <div class="contact">
                <strong>Contact Information:</strong><br>
                Email: <strong>{{ $contactEmail }}</strong><br>
                Please include your name and the reason for your inquiry.
            </div>
            
            <p>We appreciate your interest in our system and hope to serve you in the future.</p>
            
            <p>Best regards,<br>
            The System Administration Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>

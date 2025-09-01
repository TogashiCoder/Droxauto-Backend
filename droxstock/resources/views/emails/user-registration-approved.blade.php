<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approved - Welcome!</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #d4edda; color: #155724; padding: 20px; text-align: center; border-radius: 5px; }
        .content { padding: 20px; background: #fff; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        .status { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 20px 0; }
        .cta { background: #007bff; color: #fff; padding: 15px; text-align: center; border-radius: 5px; margin: 20px 0; }
        .cta a { color: #fff; text-decoration: none; font-weight: bold; }
        .notes { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Account Approved!</h1>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            
            <p>Great news! Your account has been approved by our administrators. You can now access the system and start using all the available features.</p>
            
            <div class="status">
                <strong>Status:</strong> ✅ Approved<br>
                <strong>Approved Date:</strong> {{ now()->format('F j, Y \a\t g:i A') }}
            </div>
            
            @if($adminNotes)
            <div class="notes">
                <strong>Admin Notes:</strong><br>
                {{ $adminNotes }}
            </div>
            @endif
            
            <p>What you can do now:</p>
            <ul>
                <li>Log in to your account</li>
                <li>Access the system features based on your assigned permissions</li>
                <li>Update your profile information</li>
                <li>Contact support if you need help</li>
            </ul>
            
            <div class="cta">
                <a href="{{ $loginUrl }}">🚀 Login to Your Account</a>
            </div>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Welcome aboard!</p>
            
            <p>Best regards,<br>
            The System Administration Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>

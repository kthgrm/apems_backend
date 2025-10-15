<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to APEMS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }

        .credentials {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .credential-item {
            margin: 10px 0;
        }

        .credential-label {
            font-weight: bold;
            color: #667eea;
        }

        .credential-value {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
        }

        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Welcome to APEMS</h1>
        <p>Academic Programs and Extension Management System</p>
    </div>

    <div class="content">
        <p>Hello <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>

        <p>Your account has been successfully created in the Academic Programs and Extension Management System (APEMS).
            You can now access the system using the credentials below.</p>

        <div class="credentials">
            <div class="credential-item">
                <div class="credential-label">Email:</div>
                <div class="credential-value">{{ $user->email }}</div>
            </div>

            <div class="credential-item">
                <div class="credential-label">Temporary Password:</div>
                <div class="credential-value">{{ $temporaryPassword }}</div>
            </div>

            <div class="credential-item">
                <div class="credential-label">College:</div>
                <div class="credential-value">{{ $user->college->name }}</div>
            </div>

            <div class="credential-item">
                <div class="credential-label">Role:</div>
                <div class="credential-value">{{ ucfirst($user->role) }}</div>
            </div>
        </div>

        <div class="warning">
            <strong>⚠️ Important Security Notice</strong>
            <p style="margin: 10px 0 0 0;">
                For security reasons, you will be required to change your password upon your first login.
                Please keep this temporary password secure and do not share it with anyone.
            </p>
        </div>

        <center>
            <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/login" class="button">
                Log In to APEMS
            </a>
        </center>

        <p style="margin-top: 30px;">If you have any questions or need assistance, please contact your system
            administrator.</p>

        <p>Best regards,<br>
            <strong>APEMS Team</strong>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} APEMS. All rights reserved.</p>
    </div>
</body>

</html>

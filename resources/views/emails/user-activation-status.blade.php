<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation Status</title>
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
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .footer {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Account Activation Status</h1>
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>Your account has been <strong>{{ $status }}</strong>.</p>
            <p>If you have any questions or concerns, please don't hesitate to contact our support team.</p>
            <p>Thank you for using our platform!</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} LMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

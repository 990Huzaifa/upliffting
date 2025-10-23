<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <p>{{$data['message']}}</p><br>
    <p>{{$data['otp']}}</p>
</body>
</html>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - OTP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 4px solid #0ea5e9;
        }

        .logo {
            width: 50px;
            height: 50px;
            background-color: #0ea5e9;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 24px;
        }

        .header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .header p {
            color: #e0e7ff;
            font-size: 14px;
            font-weight: 500;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .message p {
            margin-bottom: 15px;
        }

        /* <CHANGE> OTP code section - prominent display */
        .otp-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 35px;
            border-radius: 8px;
            margin: 35px 0;
            text-align: center;
            border: 2px solid #0ea5e9;
        }

        .otp-label {
            font-size: 12px;
            color: #0c4a6e;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .otp-code {
            font-family: 'Courier New', monospace;
            font-size: 36px;
            font-weight: 900;
            color: #1e3a8a;
            letter-spacing: 8px;
            word-break: break-all;
            margin-bottom: 15px;
        }

        .otp-expiry {
            font-size: 13px;
            color: #0c4a6e;
            font-weight: 500;
        }

        .highlight {
            background-color: #fef3c7;
            padding: 15px;
            border-left: 4px solid #f59e0b;
            border-radius: 4px;
            margin: 25px 0;
            font-size: 14px;
            color: #92400e;
        }

        .instructions {
            background-color: #f3f4f6;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
            font-size: 14px;
            color: #374151;
            line-height: 1.8;
        }

        .instructions ol {
            margin-left: 20px;
            margin-top: 10px;
        }

        .instructions li {
            margin-bottom: 10px;
        }

        .security-note {
            background-color: #eff6ff;
            border-left: 4px solid #0284c7;
            padding: 15px;
            border-radius: 4px;
            margin: 25px 0;
            font-size: 13px;
            color: #0c4a6e;
        }

        .security-note strong {
            display: block;
            margin-bottom: 5px;
            color: #075985;
        }

        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer-text {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .footer-links {
            font-size: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-links a {
            color: #0ea5e9;
            text-decoration: none;
            margin: 0 10px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .email-container {
                width: 100%;
            }

            .content {
                padding: 25px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
            }

            .otp-section {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo"><img src="{{ config('app.url') }}/assets/images/logo.png" width="100" alt=""></div>
            <h1>{{ $data['subject'] }}</h1>
            <p>Enter your one-time password to continue</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Hi {{$data['name']}},</p>

            <div class="message">
                <p>{{$data['message']}}</p>
            </div>

            <!-- <CHANGE> Prominent OTP display section -->
            <div class="otp-section">
                <div class="otp-label">Your One-Time Password</div>
                <div class="otp-code">{{$data['otp']}}</div>
                <div class="otp-expiry">⏱️ Expires in 10 minutes</div>
            </div>

            <div class="highlight">
                <strong>⚠️ Important:</strong> This code is valid for only 10 minutes. If it expires, you can request a new one.
            </div>

            <!-- <CHANGE> Instructions for entering OTP -->
            <div class="instructions">
                <strong>How to use your OTP:</strong>
                <ol>
                    <li>Go back to the verification page</li>
                    <li>Enter the 4-character code above</li>
                    <li>Click "Verify" to complete your email verification</li>
                </ol>
            </div>

            <div class="security-note">
                <strong>🛡️ Security Reminder:</strong>
                Never share this code with anyone. We will never ask for your OTP via email or phone. If you didn't request this verification, please ignore this email.
            </div>

            <div class="message">
                <p>If you have any questions or didn't request this verification, please contact our support team immediately.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                © {{ date('Y') }} Upliffting. All rights reserved.<br>
                This is an automated message, please do not reply to this email.
            </p>
            <div class="footer-links">
                <a href="https://example.com/help">Help Center</a>
                <a href="https://example.com/privacy">Privacy Policy</a>
                <a href="https://example.com/terms">Terms of Service</a>
            </div>
        </div>
    </div>
</body>
</html>
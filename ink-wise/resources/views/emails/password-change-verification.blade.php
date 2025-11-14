<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Change Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;">
    <div style="background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #e85a4f; padding-bottom: 20px;">
            <h1 style="color: #1565c0; margin: 0; font-size: 24px;">InkWise</h1>
            <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">Secure Account Verification</p>
        </div>

        <!-- Security Alert -->
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h2 style="color: #856404; margin-top: 0; font-size: 18px;">⚠️ Security Alert</h2>
            <p style="margin-bottom: 0; color: #856404;">
                Hi {{ substr($customer->first_name, 0, 1) }}***{{ substr($customer->last_name, 0, 1) }}***,
            </p>
        </div>

        <!-- Main Content -->
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
            <p style="margin-bottom: 15px; font-weight: bold;">Someone is attempting to change the password on your account.</p>

            <table style="width: 100%; border-collapse: collapse; background-color: #fff; border-radius: 4px; overflow: hidden;">
                <tr style="background-color: #e85a4f; color: white;">
                    <th colspan="2" style="padding: 10px; text-align: left; font-weight: bold;">Attempt Details</th>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; font-weight: bold; width: 30%;">User:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">{{ substr($customer->first_name, 0, 1) }}***{{ substr($customer->last_name, 0, 1) }}***</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; font-weight: bold;">When:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">{{ $attempt->created_at->format('d M Y, H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Device:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">{{ $attempt->attempt_details['device'] ?? 'Web Browser' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: bold;">Location:</td>
                    <td style="padding: 10px;">{{ $attempt->attempt_details['location'] ?? 'Unknown' }}</td>
                </tr>
            </table>
        </div>

        <!-- Action Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('password.change.verify', $attempt->token) }}"
               style="background-color: #1565c0; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                Confirm Password Change
            </a>
        </div>

        <!-- Warning -->
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <p style="margin: 0; color: #721c24; font-weight: bold;">⚠️ Beware of scams. Do NOT proceed if you:</p>
            <ul style="margin: 10px 0 0 20px; color: #721c24;">
                <li>Receive calls claiming to be from InkWise</li>
                <li>Are told you won a prize or lottery</li>
            </ul>
        </div>

        <!-- Footer Message -->
        <div style="background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 6px; text-align: center;">
            <p style="margin: 0; color: #0c5460;">
                If this is you, please click the button above to confirm.<br>
                If you do not recognize this request, your account may be at risk — please ignore this message and contact us immediately.
            </p>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p style="margin: 0; color: #6c757d; font-size: 14px;">
                Cheers,<br>
                <strong style="color: #1565c0;">InkWise Team</strong>
            </p>
        </div>
    </div>
</body>
</html>
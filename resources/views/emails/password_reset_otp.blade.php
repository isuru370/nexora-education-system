<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
</head>

<body style="margin: 0; padding: 0; background: #eef1f6; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%;">

    {{-- OUTER WRAPPER --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background: #eef1f6; padding: 40px 20px;">
        <tr>
            <td align="center">

                {{-- INNER CARD --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width: 560px; background: #ffffff; border-radius: 12px; border: 1px solid #d1d8e3; overflow: hidden;">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background: #0f172a; padding: 32px 40px; text-align: center;">

                            {{-- LOGO --}}
                            <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; text-align: center; background: #1d4ed8; border-radius: 12px; color: #ffffff; font-size: 24px; font-weight: 800; margin-bottom: 16px;">
                                N
                            </div>

                            {{-- BRAND --}}
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -.2px;">
                                {{ config('app.name', 'Nexora Education') }}
                            </h1>

                            <p style="margin: 4px 0 0; color: rgba(255, 255, 255, .7); font-size: 13px;">
                                Modern Student Management System
                            </p>
                        </td>
                    </tr>

                    {{-- BODY --}}
                    <tr>
                        <td style="padding: 32px 40px;">

                            {{-- TITLE --}}
                            <h2 style="margin: 0 0 16px; color: #0f172a; font-size: 20px; font-weight: 700; line-height: 1.3;">
                                Password Reset Request
                            </h2>

                            {{-- GREETING --}}
                            <p style="margin: 0 0 12px; color: #334155; font-size: 14px; line-height: 1.6;">
                                Hello <strong>{{ $userName ?? 'User' }}</strong>,
                            </p>

                            <p style="margin: 0 0 20px; color: #475569; font-size: 14px; line-height: 1.6;">
                                We received a request to reset your password. Use the OTP code below to verify your identity and set a new password.
                            </p>

                            {{-- OTP BOX --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="margin: 24px 0;">
                                <tr>
                                    <td align="center" style="background: #dbe6ff; border: 1px dashed #1d4ed8; border-radius: 10px; padding: 24px 16px;">

                                        <div style="font-size: 11px; font-weight: 700; color: #1d4ed8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 10px;">
                                            Your 6-Digit OTP
                                        </div>

                                        <div style="font-family: 'Menlo', 'Courier New', monospace; font-size: 36px; font-weight: 800; color: #1d4ed8; letter-spacing: 10px; line-height: 1.2;">
                                            {{ $otp }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            {{-- EXPIRY --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="margin-bottom: 20px;">
                                <tr>
                                    <td style="background: #fef3c7; border: 1px solid rgba(180, 83, 9, .2); border-left: 4px solid #b45309; border-radius: 8px; padding: 12px 16px;">
                                        <p style="margin: 0; color: #92400e; font-size: 13px; line-height: 1.5;">
                                            <strong>⏱ Valid for {{ config('app.otp_expiry_minutes', 10) }} minutes.</strong>
                                            After this, the code will expire and you'll need to request a new one.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- IGNORE --}}
                            <p style="margin: 0 0 24px; color: #475569; font-size: 14px; line-height: 1.6;">
                                If you did not request a password reset, no action is required. Your password will remain unchanged.
                            </p>

                            {{-- DIVIDER --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="margin: 20px 0;">
                                <tr>
                                    <td style="border-top: 1px solid #e5e9f0;"></td>
                                </tr>
                            </table>

                            {{-- SIGN OFF --}}
                            <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.6;">
                                Thank you,<br>
                                <strong style="color: #0f172a;">{{ config('app.name', 'Nexora Education') }} Team</strong>
                            </p>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background: #fafbfd; padding: 20px 40px; border-top: 1px solid #e5e9f0; text-align: center;">
                            <p style="margin: 0; color: #6b7590; font-size: 11px; line-height: 1.5;">
                                This is an automated message. Please do not reply to this email.
                            </p>
                            <p style="margin: 4px 0 0; color: #94a3b8; font-size: 11px;">
                                © {{ date('Y') }} {{ config('app.name', 'Nexora Education') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6; font-size: 14px;">
    <p>Dear {{ $slip['teacher_name'] }},</p>

    <p>
        Your salary for <strong>{{ $monthShort }}</strong>
        @if (!empty($slip['branch_name']))
            ({{ $slip['branch_name'] }} branch)
        @endif
        has been paid.
    </p>

    <table style="font-size: 14px; color: #334155; margin-bottom: 16px;">
        <tr>
            <td style="padding-right: 16px;">Paid amount</td>
            <td><strong>LKR {{ number_format((float) $slip['net_salary'], 2) }}</strong></td>
        </tr>
        <tr>
            <td style="padding-right: 16px;">Paid on</td>
            <td>{{ $slip['paid_at'] ?? '-' }}</td>
        </tr>
    </table>

    <p style="margin-bottom: 4px;">Attached:</p>
    <ul style="margin-top: 0;">
        <li>Salary slip</li>
        <li>Student payments (by collection date)</li>
    </ul>

    <p>Thank you,<br>{{ $institute }}</p>

    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 8px;">
    <p style="font-size: 12px; color: #94a3b8; margin: 0;">{{ $poweredBy }}</p>
</body>
</html>

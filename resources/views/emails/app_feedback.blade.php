<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <h2 style="margin: 0 0 4px;">{{ $typeLabel }} &middot; {{ $institute }}</h2>
    <p style="margin: 0 0 16px; color: #64748b;">
        From {{ $feedback->user->name }} ({{ optional($feedback->user->userType)->code }})
        &middot; {{ $feedback->created_at->timezone('Asia/Colombo')->format('d M Y, h:i A') }}
    </p>

    <div style="white-space: pre-wrap; background: #f1f5f9; border-radius: 8px; padding: 14px; margin-bottom: 16px;">{{ $feedback->message }}</div>

    <table style="font-size: 14px; color: #334155;">
        <tr><td style="padding-right: 12px;">App version</td><td>{{ $feedback->app_version ?: '-' }}</td></tr>
        <tr><td style="padding-right: 12px;">Device</td><td>{{ $feedback->device ?: '-' }}</td></tr>
        <tr><td style="padding-right: 12px;">Platform</td><td>{{ $feedback->platform ?: '-' }}</td></tr>
        <tr><td style="padding-right: 12px;">Institute site</td><td>{{ config('app.url') }}</td></tr>
    </table>

    <p style="margin-top: 16px;"><a href="{{ $url }}">Open App Feedback</a> (Super Admin){{ $feedback->screenshot_path ? ' - screenshot attached' : '' }}</p>
</body>
</html>

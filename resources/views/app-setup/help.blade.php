<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $institute['name'] }} - Staff App</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .card {
            max-width: 420px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            padding: 28px 24px;
            box-shadow: 0 6px 24px rgba(15, 23, 42, .08);
            text-align: center;
        }

        img {
            max-height: 64px;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 20px;
            margin: 4px 0 16px;
        }

        ol {
            text-align: left;
            line-height: 1.7;
            padding-left: 22px;
        }

        .note {
            font-size: 13px;
            color: #64748b;
            margin-top: 16px;
        }
    </style>
</head>

<body>
    <div class="card">
        @if ($institute['logo_url'])
            <img src="{{ $institute['logo_url'] }}" alt="">
        @endif
        <h1>{{ $institute['name'] }}</h1>
        <p>This QR code is for the <strong>Nexora EDU</strong> staff app.</p>
        <ol>
            <li>Install <strong>Nexora EDU</strong> from the Play Store.</li>
            <li>Open the app and tap <strong>Scan institute QR</strong>.</li>
            <li>Scan the same QR code again from inside the app.</li>
            <li>Log in with your own email and password.</li>
        </ol>
        <div class="note">Scanning with the phone camera does not set up the app.</div>
    </div>
</body>

</html>

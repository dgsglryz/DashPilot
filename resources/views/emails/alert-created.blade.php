<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Created</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 3px solid {{ $alert->severity === 'critical' ? '#dc2626' : '#f59e0b' }};
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .severity-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
            background-color: {{ $alert->severity === 'critical' ? '#fee2e2' : '#fef3c7' }};
            color: {{ $alert->severity === 'critical' ? '#991b1b' : '#92400e' }};
        }
        .site-name {
            font-size: 24px;
            font-weight: 600;
            color: #111;
            margin: 10px 0;
        }
        .alert-details {
            background: #f9fafb;
            border-left: 4px solid {{ $alert->severity === 'critical' ? '#dc2626' : '#f59e0b' }};
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-type {
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .alert-message {
            font-size: 16px;
            color: #111;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 500;
        }
        .button:hover {
            background: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="severity-badge">{{ $severityLabel }}</span>
            <h1 class="site-name">{{ $site->name ?? 'Unknown Site' }}</h1>
            <p style="color: #6b7280; margin: 0;">{{ $site->url ?? 'N/A' }}</p>
        </div>

        <div class="alert-details">
            <div class="alert-type">{{ $alert->type ?? 'General Alert' }}</div>
            <div class="alert-message">{{ $alert->message }}</div>
        </div>

        <p style="color: #374151;">
            A new alert has been created for this site. Please review the details and take appropriate action.
        </p>

        <a href="{{ config('app.url') }}/sites/{{ $site->id ?? '' }}" class="button">
            View Site Details
        </a>

        <div class="footer">
            <p>This is an automated notification from DashPilot.</p>
            <p>You can manage your notification preferences in your <a href="{{ config('app.url') }}/settings" style="color: #2563eb;">settings</a>.</p>
        </div>
    </div>
</body>
</html>


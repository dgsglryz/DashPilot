<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Digest</title>
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
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 28px;
            font-weight: 600;
            color: #111;
            margin: 0;
        }
        .date {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        .stat-card {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #111;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        .section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #111;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        .alert-item {
            background: #f9fafb;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #f59e0b;
        }
        .alert-item.critical {
            border-left-color: #dc2626;
        }
        .alert-item.resolved {
            border-left-color: #10b981;
        }
        .alert-site {
            font-weight: 600;
            color: #111;
            margin-bottom: 5px;
        }
        .alert-message {
            font-size: 14px;
            color: #374151;
        }
        .alert-time {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
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
            <h1 class="title">DashPilot Daily Digest</h1>
            <p class="date">{{ $date }}</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['totalAlerts'] }}</div>
                <div class="stat-label">Total Alerts</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #dc2626;">{{ $stats['criticalAlerts'] }}</div>
                <div class="stat-label">Critical</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #10b981;">{{ $stats['resolvedAlerts'] }}</div>
                <div class="stat-label">Resolved</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['sitesWithIssues'] }}</div>
                <div class="stat-label">Sites with Issues</div>
            </div>
        </div>

        @if($alerts->count() > 0)
        <div class="section">
            <h2 class="section-title">Recent Alerts</h2>
            @foreach($alerts->take(10) as $alert)
            <div class="alert-item {{ $alert->is_resolved ? 'resolved' : ($alert->severity === 'critical' ? 'critical' : '') }}">
                <div class="alert-site">{{ $alert->site?->name ?? 'Unknown Site' }}</div>
                <div class="alert-message">{{ $alert->message }}</div>
                <div class="alert-time">
                    {{ $alert->created_at?->diffForHumans() }}
                    @if($alert->is_resolved)
                        • Resolved
                    @else
                        • {{ ucfirst($alert->severity) }}
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <a href="{{ config('app.url') }}/dashboard" class="button">
            View Dashboard
        </a>

        <div class="footer">
            <p>This is an automated daily digest from DashPilot.</p>
            <p>You can manage your notification preferences in your <a href="{{ config('app.url') }}/settings" style="color: #2563eb;">settings</a>.</p>
        </div>
    </div>
</body>
</html>


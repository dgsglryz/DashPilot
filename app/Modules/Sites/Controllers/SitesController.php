<?php
declare(strict_types=1);

namespace App\Modules\Sites\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SitesController exposes the list and detail views for monitored sites.
 */
class SitesController extends Controller
{
    /**
     * Display the sites list with stats summary.
     */
    public function index(Request $request): Response
    {
        $sites = Site::query()
            ->with('client:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Site $site) => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'status' => $site->status,
                'platform' => $site->type,
                'industry' => $site->industry,
                'region' => $site->region,
                'thumbnail' => $site->thumbnail_url ?? 'https://picsum.photos/seed/'.$site->id.'/640/360',
                'uptime' => (float) ($site->uptime_percentage ?? 0),
                'responseTime' => (int) ($site->avg_load_time ? $site->avg_load_time * 1000 : 0),
                'lastChecked' => $site->last_checked_at?->toIso8601String(),
                'client' => [
                    'id' => $site->client_id,
                    'name' => $site->client?->name,
                ],
            ]);

        $stats = [
            'total' => $sites->count(),
            'healthy' => $sites->where('status', 'healthy')->count(),
            'warning' => $sites->where('status', 'warning')->count(),
            'critical' => $sites->where('status', 'critical')->count(),
        ];

        return Inertia::render('Sites/Pages/Index', [
            'sites' => $sites,
            'stats' => $stats,
            'filters' => [
                'query' => $request->string('query')->toString(),
                'status' => $request->string('status')->toString(),
                'platform' => $request->string('platform')->toString(),
            ],
        ]);
    }

    /**
     * Display a single site detail view.
     */
    public function show(Site $site): Response
    {
        $site->load(['client:id,name,email,company', 'alerts' => function ($query) {
            $query->latest()->limit(5);
        }]);

        $recentChecks = SiteCheck::query()
            ->where('site_id', $site->id)
            ->orderByDesc('checked_at')
            ->limit(14)
            ->get(['check_type', 'status', 'response_time', 'checked_at'])
            ->map(fn (SiteCheck $check) => [
                'type' => $check->check_type,
                'status' => $check->status,
                'responseTime' => $check->response_time,
                'checkedAt' => $check->checked_at?->toIso8601String(),
            ]);

        $recentTasks = Task::query()
            ->where('site_id', $site->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'due_date'])
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'dueDate' => $task->due_date?->toDateString(),
            ]);

        $activity = ActivityLog::query()
            ->where('site_id', $site->id)
            ->latest()
            ->limit(6)
            ->get(['action', 'description', 'created_at'])
            ->map(fn (ActivityLog $log) => [
                'action' => $log->action,
                'description' => $log->description,
                'timestamp' => $log->created_at?->toIso8601String(),
            ]);

        $reports = Report::query()
            ->where('site_id', $site->id)
            ->latest('report_month')
            ->limit(6)
            ->get(['report_month', 'uptime_percentage', 'avg_load_time', 'incidents_count'])
            ->map(fn (Report $report) => [
                'month' => $report->report_month?->format('M Y'),
                'uptime' => $report->uptime_percentage,
                'response' => $report->avg_load_time,
                'incidents' => $report->incidents_count,
            ]);

        return Inertia::render('Sites/Pages/Show', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'status' => $site->status,
                'platform' => $site->type,
                'industry' => $site->industry,
                'region' => $site->region,
                'thumbnail' => $site->thumbnail_url ?? 'https://picsum.photos/seed/'.$site->id.'/640/360',
                'uptime' => (float) ($site->uptime_percentage ?? 0),
                'response' => (float) ($site->avg_load_time ?? 0),
                'healthScore' => (int) ($site->health_score ?? 0),
                'lastChecked' => $site->last_checked_at?->toIso8601String(),
                'client' => [
                    'id' => $site->client_id,
                    'name' => $site->client?->name,
                    'email' => $site->client?->email,
                    'company' => $site->client?->company,
                ],
            ],
            'alerts' => $site->alerts->map(fn (Alert $alert) => [
                'id' => $alert->id,
                'title' => $alert->title,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'status' => $alert->status,
                'message' => $alert->message,
                'timestamp' => $alert->created_at?->toIso8601String(),
            ]),
            'checks' => $recentChecks,
            'tasks' => $recentTasks,
            'activity' => $activity,
            'reports' => $reports,
        ]);
    }
}


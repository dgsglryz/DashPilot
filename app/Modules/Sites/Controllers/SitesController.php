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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        $query = Site::query()->with('client:id,name');

        if ($request->filled('platform') && $request->string('platform')->toString() !== 'all') {
            $query->where('type', $request->string('platform')->toString());
        }

        if ($request->filled('status') && $request->string('status')->toString() !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('query')) {
            $search = $request->string('query')->toString();
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $sites = $query
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
                'thumbnail' => $site->thumbnail_url ?? $this->fallbackThumbnail($site->id),
                'logo' => $site->logo_url ?? $this->fallbackLogo($site->name),
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
                'status' => $request->string('status')->toString() ?: 'all',
                'platform' => $request->string('platform')->toString() ?: 'all',
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

        $seoInsights = $this->buildSeoInsights($site);

        return Inertia::render('Sites/Pages/Show', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'status' => $site->status,
                'platform' => $site->type,
                'industry' => $site->industry,
                'region' => $site->region,
                'thumbnail' => $site->thumbnail_url ?? $this->fallbackThumbnail($site->id),
                'logo' => $site->logo_url ?? $this->fallbackLogo($site->name),
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
                'seoScore' => $seoInsights['score'],
                'seoMetrics' => $seoInsights['metrics'],
                'seoIssues' => $seoInsights['issues'],
            ],
            'chart' => $this->buildPerformanceSeries($recentChecks),
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

    /**
     * Build chart-ready data for the Vue chart component.
     *
     * @param Collection<int, array<string, mixed>> $checks
     */
    private function buildPerformanceSeries(Collection $checks): array
    {
        if ($checks->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $ordered = $checks->sortBy('checkedAt');

        $labels = $ordered->map(fn (array $check): string => Carbon::parse($check['checkedAt'])->format('M j · H:i'))->all();
        $uptimeSeries = $ordered->map(fn (array $check): float => $this->uptimePoint($check['status']))->all();
        $responseSeries = $ordered->map(fn (array $check): int => (int) ($check['responseTime'] ?? 0))->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Uptime %',
                    'data' => $uptimeSeries,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Response (ms)',
                    'data' => $responseSeries,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    private function uptimePoint(?string $status): float
    {
        return match ($status) {
            'pass', 'healthy' => 99.6,
            'warning' => 96.2,
            default => 92.4,
        };
    }

    private function buildSeoInsights(Site $site): array
    {
        $baseScore = (int) ($site->health_score ?? 78);
        $score = max(55, min(100, $baseScore + random_int(-5, 7)));

        $metrics = [
            ['name' => 'Meta tags', 'score' => max(45, min(100, $score + random_int(-5, 5)))],
            ['name' => 'H1 structure', 'score' => max(45, min(100, $score + random_int(-10, 3)))],
            ['name' => 'Page speed', 'score' => max(45, min(100, $score + random_int(-8, 6)))],
            ['name' => 'Image alts', 'score' => max(45, min(100, $score + random_int(-12, 4)))],
        ];

        $issues = $score >= 85
            ? []
            : [
                [
                    'id' => 'meta-length',
                    'title' => 'Meta description is short',
                    'description' => 'Add a 150-160 character description for better SERP snippets.',
                ],
                [
                    'id' => 'image-alt',
                    'title' => '8 images missing alt text',
                    'description' => 'Add descriptive alternate text for all hero and gallery images.',
                ],
            ];

        return [
            'score' => $score,
            'metrics' => $metrics,
            'issues' => $issues,
        ];
    }

    private function fallbackThumbnail(int $siteId): string
    {
        return "https://picsum.photos/seed/site-{$siteId}/640/360";
    }

    private function fallbackLogo(string $name): string
    {
        $seed = Str::slug($name);

        return "https://api.dicebear.com/7.x/initials/svg?seed={$seed}&backgroundColor=111827,1c1f2b&fontSize=60";
    }
}


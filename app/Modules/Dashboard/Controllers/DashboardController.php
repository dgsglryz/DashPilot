<?php
declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DashboardController assembles the data blocks required by the Inertia dashboard.
 */
class DashboardController extends Controller
{
    /**
     * Display the main operations dashboard.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        $totalSites = Site::count();
        $healthySites = Site::where('status', 'healthy')->count();
        $criticalAlerts = Alert::where('is_resolved', false)->count();
        $averageSeo = (int) round(Site::avg('health_score') ?: 82);
        $avgUptime = (float) (Report::avg('uptime_percentage') ?? 99.2);
        $activitiesToday = ActivityLog::whereDate('created_at', Carbon::today())->count();

        return Inertia::render('Dashboard/Pages/Index', [
            'stats' => [
                'totalSites' => $totalSites,
                'activeSites' => $healthySites,
                'healthySites' => $healthySites,
                'criticalAlerts' => $criticalAlerts,
                'avgUptime' => $avgUptime,
                'totalRevenue' => $this->estimateRevenue($totalSites, $avgUptime),
                'avgSeoScore' => $averageSeo,
                'activitiesToday' => $activitiesToday,
                'warningSites' => Site::where('status', 'warning')->count(),
            ],
            'recentAlerts' => $this->recentAlerts(),
            'scheduledChecks' => $this->scheduledChecks(),
            'featuredSites' => $this->featuredSites(),
            'activities' => $this->activities(),
            'chartData' => [
                'sitesByStatus' => $this->sitesByStatus(),
                'alertFrequency' => $this->alertFrequency(),
                'uptimeTrend' => $this->uptimeTrend(),
                'topProblematicSites' => $this->topProblematicSites(),
            ],
        ]);
    }

    /**
     * Build recent alert payload for the sidebar.
     *
     * @return array<int, array<string, string|int>>
     */
    private function recentAlerts(): array
    {
        return Alert::latest('created_at')
            ->take(5)
            ->get(['id', 'type', 'severity', 'message', 'created_at'])
            ->map(function (Alert $alert): array {
                return [
                    'id' => $alert->id,
                    'title' => $alert->type.' · '.Str::limit($alert->message, 40),
                    'severity' => $this->mapSeverity($alert->severity),
                    'time' => $alert->created_at?->diffForHumans() ?? 'Just now',
                ];
            })
            ->all();
    }

    /**
     * Generate scheduled calendar events that match the UI.
     *
     * @return array<int, array<string, string>>
     */
    private function scheduledChecks(): array
    {
        $base = Carbon::now()->startOfMonth();

        return [
            [
                'date' => $base->copy()->addDays(3)->toDateString(),
                'title' => 'Site Monitoring',
                'subtitle' => '15 active sites',
                'tag' => 'Uptime 99.5%',
                'status' => 'info',
            ],
            [
                'date' => $base->copy()->addDays(9)->toDateString(),
                'title' => 'SEO Performance',
                'subtitle' => 'Revenue overview',
                'tag' => '$5,000',
                'status' => 'warning',
            ],
            [
                'date' => $base->copy()->addDays(14)->toDateString(),
                'title' => 'Alerts review',
                'subtitle' => '3 alerts pending',
                'tag' => 'Follow-up',
                'status' => 'danger',
            ],
            [
                'date' => $base->copy()->addDays(18)->toDateString(),
                'title' => 'Weekly activity report',
                'subtitle' => 'Ops & support',
                'tag' => 'Sync',
                'status' => 'success',
            ],
        ];
    }

    /**
     * Rough revenue estimation used for demo purposes.
     *
     * @param int $totalSites
     * @param float $uptime
     *
     * @return int
     */
    private function estimateRevenue(int $totalSites, float $uptime): int
    {
        $base = max(1, $totalSites) * 750;

        return (int) round($base * ($uptime / 95));
    }

    private function mapSeverity(?string $severity): string
    {
        return match (strtolower($severity ?? 'info')) {
            'critical', 'high' => 'critical',
            'medium' => 'warning',
            default => 'info',
        };
    }

    /**
     * Highlight a handful of visually rich sites on the Overview page.
     *
     * @return array<int, array<string, mixed>>
     */
    private function featuredSites(): array
    {
        return Site::query()
            ->orderByDesc('health_score')
            ->limit(6)
            ->get(['id', 'name', 'status', 'type', 'region', 'thumbnail_url', 'logo_url', 'uptime_percentage'])
            ->map(function (Site $site): array {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'status' => $site->status,
                    'platform' => $site->type,
                    'region' => $site->region,
                    'thumbnail' => $site->thumbnail_url ?? $this->fallbackThumbnail($site->id),
                    'logo' => $site->logo_url ?? $this->fallbackLogo($site->name),
                    'uptime' => $site->uptime_percentage ? number_format((float) $site->uptime_percentage, 2) : null,
                ];
            })
            ->all();
    }

    private function fallbackThumbnail(int $siteId): string
    {
        return "https://picsum.photos/seed/dashboard-{$siteId}/640/360";
    }

    private function fallbackLogo(string $name): string
    {
        $seed = Str::slug($name);

        return "https://api.dicebear.com/7.x/initials/svg?seed={$seed}&backgroundColor=111827,1c1f2b&fontSize=60";
    }

    /**
     * Get sites grouped by status for doughnut chart.
     *
     * @return array<string, int>
     */
    private function sitesByStatus(): array
    {
        return [
            'healthy' => Site::where('status', 'healthy')->count(),
            'warning' => Site::where('status', 'warning')->count(),
            'critical' => Site::where('status', 'critical')->count(),
            'offline' => Site::where('status', 'offline')->count(),
        ];
    }

    /**
     * Get alert frequency data for bar chart (last 30 days).
     *
     * @return array<int, array<string, string|int>>
     */
    private function alertFrequency(): array
    {
        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = [
                'date' => $date->format('M d'),
                'count' => Alert::whereDate('created_at', $date->toDateString())->count(),
            ];
        }

        return $days;
    }

    /**
     * Get uptime trend data for line chart (last 30 days).
     *
     * @return array<int, array<string, string|float>>
     */
    private function uptimeTrend(): array
    {
        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $avgUptime = (float) (Report::whereDate('created_at', $date->toDateString())
                ->avg('uptime_percentage') ?? 99.2);
            $days[] = [
                'date' => $date->format('M d'),
                'uptime' => round($avgUptime, 2),
            ];
        }

        return $days;
    }

    /**
     * Get top 5 problematic sites (lowest health scores or most alerts).
     *
     * @return array<int, array<string, mixed>>
     */
    private function topProblematicSites(): array
    {
        return Site::query()
            ->withCount('alerts')
            ->orderBy('health_score')
            ->orderByDesc('alerts_count')
            ->limit(5)
            ->get(['id', 'name', 'status', 'health_score', 'url', 'type'])
            ->map(function (Site $site): array {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'url' => $site->url,
                    'status' => $site->status,
                    'platform' => $site->type,
                    'healthScore' => $site->health_score ?? 0,
                    'alertCount' => $site->alerts_count ?? 0,
                ];
            })
            ->all();
    }

    /**
     * Build activity feed for the dashboard sidebar.
     *
     * @return array<int, array<string, string|int|null>>
     */
    private function activities(): array
    {
        return ActivityLog::query()
            ->latest('created_at')
            ->take(10)
            ->with(['user:id,name', 'site:id,name'])
            ->get()
            ->map(function (ActivityLog $log): array {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user?->name ?? 'System',
                    'site' => $log->site?->name ?? null,
                    'time' => $log->created_at?->diffForHumans() ?? 'Just now',
                    'timestamp' => $log->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }
}


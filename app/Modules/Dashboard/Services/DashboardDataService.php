<?php
declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Shared\Helpers\SiteMediaHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * DashboardDataService composes dashboard datasets and caches expensive queries.
 */
class DashboardDataService
{
    /**
     * Build aggregate stats block with lightweight caching.
     */
    public function stats(): array
    {
        return Cache::remember('dashboard:stats', 60, function (): array {
            $totalSites = Site::count();
            $healthySites = Site::where('status', 'healthy')->count();
            $avgUptime = round((float) (Report::avg('uptime_percentage') ?? 99.2), 2);

            return [
                'totalSites' => $totalSites,
                'activeSites' => $healthySites,
                'healthySites' => $healthySites,
                'criticalAlerts' => Alert::where('is_resolved', false)->count(),
                'avgUptime' => $avgUptime,
                'totalRevenue' => $this->estimateRevenue($totalSites, $avgUptime),
                'avgSeoScore' => (int) round(Site::avg('health_score') ?: 82),
                'activitiesToday' => ActivityLog::whereDate('created_at', Carbon::today())->count(),
                'warningSites' => Site::where('status', 'warning')->count(),
            ];
        });
    }

    public function recentAlerts(): array
    {
        return Alert::latest('created_at')
            ->take(5)
            ->get(['id', 'type', 'severity', 'message', 'created_at'])
            ->map(fn (Alert $alert): array => [
                'id' => $alert->id,
                'title' => $alert->type.' · '.Str::limit($alert->message, 40),
                'severity' => $this->mapSeverity($alert->severity),
                'time' => $alert->created_at?->diffForHumans() ?? 'Just now',
            ])
            ->all();
    }

    public function scheduledChecks(): array
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

    public function featuredSites(): array
    {
        return Site::query()
            ->orderByDesc('health_score')
            ->limit(6)
            ->get(['id', 'name', 'status', 'type', 'region', 'thumbnail_url', 'logo_url', 'uptime_percentage'])
            ->map(fn (Site $site): array => [
                'id' => $site->id,
                'name' => $site->name,
                'status' => $site->status,
                'platform' => $site->type,
                'region' => $site->region,
                'thumbnail' => $site->thumbnail_url ?? SiteMediaHelper::thumbnail($site->id, 'dashboard'),
                'logo' => $site->logo_url ?? SiteMediaHelper::logo($site->name),
                'uptime' => $site->uptime_percentage ? number_format((float) $site->uptime_percentage, 2) : null,
            ])
            ->all();
    }

    public function favoritedSites(): array
    {
        return Site::query()
            ->where('is_favorited', true)
            ->orderByDesc('health_score')
            ->limit(6)
            ->get(['id', 'name', 'status', 'type', 'region', 'thumbnail_url', 'logo_url', 'uptime_percentage', 'health_score'])
            ->map(fn (Site $site): array => [
                'id' => $site->id,
                'name' => $site->name,
                'status' => $site->status,
                'platform' => $site->type,
                'region' => $site->region,
                'thumbnail' => $site->thumbnail_url ?? SiteMediaHelper::thumbnail($site->id, 'favorite'),
                'logo' => $site->logo_url ?? SiteMediaHelper::logo($site->name),
                'uptime' => $site->uptime_percentage ? number_format((float) $site->uptime_percentage, 2) : null,
                'healthScore' => $site->health_score,
            ])
            ->all();
    }

    public function sitesByStatus(): array
    {
        return [
            'healthy' => Site::where('status', 'healthy')->count(),
            'warning' => Site::where('status', 'warning')->count(),
            'critical' => Site::where('status', 'critical')->count(),
            'offline' => Site::where('status', 'offline')->count(),
        ];
    }

    public function alertFrequency(): array
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

    public function uptimeTrend(): array
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

    public function topProblematicSites(): array
    {
        return Site::query()
            ->withCount('alerts')
            ->orderBy('health_score')
            ->orderByDesc('alerts_count')
            ->limit(5)
            ->get(['id', 'name', 'status', 'health_score', 'url', 'type'])
            ->map(fn (Site $site): array => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'status' => $site->status,
                'platform' => $site->type,
                'healthScore' => $site->health_score ?? 0,
                'alertCount' => $site->alerts_count ?? 0,
            ])
            ->all();
    }

    public function activities(): array
    {
        return ActivityLog::query()
            ->latest('created_at')
            ->take(10)
            ->with(['user:id,name', 'site:id,name'])
            ->get()
            ->map(fn (ActivityLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name ?? 'System',
                'site' => $log->site?->name ?? null,
                'time' => $log->created_at?->diffForHumans() ?? 'Just now',
                'timestamp' => $log->created_at?->toIso8601String(),
            ])
            ->all();
    }

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
}



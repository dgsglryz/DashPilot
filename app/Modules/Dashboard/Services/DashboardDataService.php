<?php
declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use App\Shared\Helpers\SiteMediaHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * DashboardDataService composes dashboard datasets and caches expensive queries.
 */
class DashboardDataService
{
    /**
     * Get scoped sites query based on user role.
     *
     * @param User|null $user
     * @return Builder
     */
    private function scopedSitesQuery(?User $user = null): Builder
    {
        $query = Site::query();
        
        if ($user && $user->role !== 'admin') {
            $query->whereHas('client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        
        return $query;
    }
    
    /**
     * Get scoped alerts query based on user role.
     *
     * @param User|null $user
     * @return Builder
     */
    private function scopedAlertsQuery(?User $user = null): Builder
    {
        $query = Alert::query();
        
        if ($user && $user->role !== 'admin') {
            $query->whereHas('site.client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        
        return $query;
    }
    
    /**
     * Get scoped activity logs query based on user role.
     *
     * @param User|null $user
     * @return Builder
     */
    private function scopedActivityQuery(?User $user = null): Builder
    {
        $query = ActivityLog::query();
        
        if ($user && $user->role !== 'admin') {
            $query->whereHas('site.client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        
        return $query;
    }
    
    /**
     * Build aggregate stats block with lightweight caching.
     */
    public function stats(?User $user = null): array
    {
        $cacheKey = $user && $user->role === 'admin' 
            ? 'dashboard:stats' 
            : "dashboard:stats:user:{$user->id}";
            
        return Cache::remember($cacheKey, 60, function () use ($user): array {
            $sitesQuery = $this->scopedSitesQuery($user);
            $totalSites = $sitesQuery->count();
            $healthySites = (clone $sitesQuery)->where('status', 'healthy')->count();
            $warningSites = (clone $sitesQuery)->where('status', 'warning')->count();
            
            // Scope reports query
            $reportsQuery = Report::query();
            if ($user && $user->role !== 'admin') {
                $reportsQuery->whereHas('site.client', function ($q) use ($user) {
                    $q->where('assigned_developer_id', $user->id);
                });
            }
            $avgUptime = round((float) ($reportsQuery->avg('uptime_percentage') ?? 99.2), 2);
            
            $alertsQuery = $this->scopedAlertsQuery($user);
            $criticalAlerts = (clone $alertsQuery)->where('is_resolved', false)->count();
            
            $activityQuery = $this->scopedActivityQuery($user);
            $activitiesToday = (clone $activityQuery)->whereDate('created_at', Carbon::today())->count();

            return [
                'totalSites' => $totalSites,
                'activeSites' => $healthySites,
                'healthySites' => $healthySites,
                'criticalAlerts' => $criticalAlerts,
                'avgUptime' => $avgUptime,
                'totalRevenue' => $this->estimateRevenue($totalSites, $avgUptime),
                'avgSeoScore' => (int) round($sitesQuery->avg('health_score') ?: 82),
                'activitiesToday' => $activitiesToday,
                'warningSites' => $warningSites,
            ];
        });
    }

    public function recentAlerts(?User $user = null): array
    {
        return $this->scopedAlertsQuery($user)
            ->latest('created_at')
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

    public function featuredSites(?User $user = null): array
    {
        return $this->scopedSitesQuery($user)
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

    public function favoritedSites(?User $user = null): array
    {
        return $this->scopedSitesQuery($user)
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

    public function sitesByStatus(?User $user = null): array
    {
        $query = $this->scopedSitesQuery($user);
        
        return [
            'healthy' => (clone $query)->where('status', 'healthy')->count(),
            'warning' => (clone $query)->where('status', 'warning')->count(),
            'critical' => (clone $query)->where('status', 'critical')->count(),
            'offline' => (clone $query)->where('status', 'offline')->count(),
        ];
    }

    public function alertFrequency(?User $user = null): array
    {
        $days = [];
        $alertsQuery = $this->scopedAlertsQuery($user);
        
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = [
                'date' => $date->format('M d'),
                'count' => (clone $alertsQuery)->whereDate('created_at', $date->toDateString())->count(),
            ];
        }

        return $days;
    }

    public function uptimeTrend(?User $user = null): array
    {
        $days = [];
        $reportsQuery = Report::query();
        
        if ($user && $user->role !== 'admin') {
            $reportsQuery->whereHas('site.client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $avgUptime = (float) ((clone $reportsQuery)->whereDate('created_at', $date->toDateString())
                ->avg('uptime_percentage') ?? 99.2);
            $days[] = [
                'date' => $date->format('M d'),
                'uptime' => round($avgUptime, 2),
            ];
        }

        return $days;
    }

    public function topProblematicSites(?User $user = null): array
    {
        return $this->scopedSitesQuery($user)
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

    public function activities(?User $user = null): array
    {
        return $this->scopedActivityQuery($user)
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




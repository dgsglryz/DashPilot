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
            ],
            'recentAlerts' => $this->recentAlerts(),
            'scheduledChecks' => $this->scheduledChecks(),
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
}


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
        $latestReport = Report::latest('report_month')->first();
        $averageSeo = (int) round(Site::avg('health_score') ?: 82);

        $cards = $this->buildCards($totalSites, $healthySites, $criticalAlerts, $latestReport, $averageSeo);

        $operations = [
            'sitePerformance' => [
                'updatedAgo' => 'Updated: '.Carbon::now()->subMinutes(5)->diffForHumans(null, true),
                'activeSites' => "{$healthySites}/{$totalSites}",
                'status' => $healthySites >= ($totalSites * 0.8) ? 'Healthy' : 'Needs attention',
            ],
            'metrics' => [
                ['label' => 'Uptime', 'value' => '99%', 'subtitle' => 'Load time'],
                ['label' => 'Revenue', 'value' => $this->currency($latestReport?->uptime_percentage ?? 5000), 'subtitle' => 'Last month'],
                ['label' => 'SEO Score', 'value' => "{$averageSeo}", 'subtitle' => 'Current'],
            ],
            'notifications' => $this->buildNotifications(),
        ];

        return Inertia::render('Dashboard', [
            'userProfile' => [
                'name' => $request->user()?->name ?? 'AgencyOps',
                'email' => $request->user()?->email ?? 'ops@agency.com',
                'avatar' => 'https://images.unsplash.com/photo-1504593811423-6dd665756598?auto=format&fit=crop&w=200&q=80',
            ],
            'cards' => $cards,
            'calendar' => [
                'range' => 'May 01 - May 21, 2023',
                'days' => $this->calendarDays(),
            ],
            'operations' => $operations,
            'activityFeed' => ActivityLog::latest()->take(5)->get(['action', 'description', 'created_at'])->map(fn (ActivityLog $log) => [
                'action' => $log->action,
                'description' => $log->description,
                'time' => $log->created_at?->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Build overview cards.
     *
     * @param int $totalSites
     * @param int $healthySites
     * @param int $criticalAlerts
     * @param Report|null $latestReport
     * @param int $averageSeo
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildCards(int $totalSites, int $healthySites, int $criticalAlerts, ?Report $latestReport, int $averageSeo): array
    {
        return [
            [
                'title' => 'Site Monitoring',
                'subtitle' => 'Track site performance',
                'description' => 'Real-time updates available. '.$totalSites.' sites monitored.',
                'stats' => [
                    ['label' => 'Uptime', 'value' => '99.5%'],
                    ['label' => 'Alerts', 'value' => "{$criticalAlerts} open"],
                ],
                'image' => 'https://images.unsplash.com/photo-1517430816045-df4b7de11d1d?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'title' => 'SEO Performance',
                'subtitle' => 'Check SEO scores now',
                'description' => 'Last updated: '.Carbon::now()->subMinutes(15)->format('g:i A'),
                'tag' => 'View Report',
                'stats' => [
                    ['label' => 'SEO Score', 'value' => "{$averageSeo}%"],
                    ['label' => 'Issues', 'value' => '5 unresolved'],
                ],
                'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'title' => 'Revenue Overview',
                'subtitle' => 'Shopify earnings this month',
                'description' => 'Total revenue: '.$this->currency($latestReport?->uptime_percentage ?? 5000),
                'tag' => 'View Stats',
                'stats' => [
                    ['label' => 'Growth', 'value' => '15% this week'],
                ],
                'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'title' => 'Recent Activities',
                'subtitle' => 'Updates on all sites',
                'description' => 'Last activity: '.Carbon::now()->subHours(1)->format('g A'),
                'stats' => [
                    ['label' => 'Weekly Summary', 'value' => 'Report ready'],
                ],
                'image' => 'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=600&q=80',
            ],
        ];
    }

    /**
     * Generate calendar tiles.
     *
     * @return array<int, array<string, mixed>>
     */
    private function calendarDays(): array
    {
        $events = [
            4 => ['title' => 'Site Monitoring', 'subtitle' => '15 Active Sites', 'tag' => 'Uptime 99.5%'],
            10 => ['title' => 'SEO Performance', 'subtitle' => 'Revenue Overview', 'tag' => '$5,000'],
            15 => ['title' => 'Alerts', 'subtitle' => '3 alerts pending', 'tag' => 'Check'],
            19 => ['title' => 'Recent Activities', 'subtitle' => 'Weekly summary'],
        ];

        return collect(range(1, 21))->map(function (int $day) use ($events) {
            return [
                'day' => $day,
                'title' => $events[$day]['title'] ?? null,
                'subtitle' => $events[$day]['subtitle'] ?? null,
                'tag' => $events[$day]['tag'] ?? null,
            ];
        })->all();
    }

    /**
     * Build notifications array.
     *
     * @return array<int, array<string, string>>
     */
    private function buildNotifications(): array
    {
        $latestAlert = Alert::where('is_resolved', false)->latest()->first();

        if ($latestAlert === null) {
            return [
                [
                    'title' => 'All systems nominal',
                    'description' => 'No new alerts. Keep monitoring scheduled jobs.',
                ],
            ];
        }

        return [
            [
                'title' => 'New alert: '.$latestAlert->type,
                'description' => $latestAlert->message,
            ],
        ];
    }

    /**
     * Format monetary values.
     *
     * @param float|int $amount
     *
     * @return string
     */
    private function currency(float|int|string $amount): string
    {
        return '$'.number_format((float) $amount, 0);
    }
}


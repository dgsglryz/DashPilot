<?php
declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardDataService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DashboardController assembles the data blocks required by the Inertia dashboard.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardDataService $dashboardData)
    {
    }

    public function __invoke(): Response
    {
        return Inertia::render('Dashboard/Pages/Index', [
            'stats' => $this->dashboardData->stats(),
            'recentAlerts' => $this->dashboardData->recentAlerts(),
            'scheduledChecks' => $this->dashboardData->scheduledChecks(),
            'featuredSites' => $this->dashboardData->featuredSites(),
            'favoritedSites' => $this->dashboardData->favoritedSites(),
            'activities' => $this->dashboardData->activities(),
            'chartData' => [
                'sitesByStatus' => $this->dashboardData->sitesByStatus(),
                'alertFrequency' => $this->dashboardData->alertFrequency(),
                'uptimeTrend' => $this->dashboardData->uptimeTrend(),
                'topProblematicSites' => $this->dashboardData->topProblematicSites(),
            ],
        ]);
    }

}


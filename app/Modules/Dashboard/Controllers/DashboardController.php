<?php
declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardDataService;
use Illuminate\Http\Request;
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

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        
        return Inertia::render('Dashboard/Pages/Index', [
            'stats' => $this->dashboardData->stats($user),
            'recentAlerts' => $this->dashboardData->recentAlerts($user),
            'scheduledChecks' => $this->dashboardData->scheduledChecks(),
            'featuredSites' => $this->dashboardData->featuredSites($user),
            'favoritedSites' => $this->dashboardData->favoritedSites($user),
            'activities' => $this->dashboardData->activities($user),
            'chartData' => [
                'sitesByStatus' => $this->dashboardData->sitesByStatus($user),
                'alertFrequency' => $this->dashboardData->alertFrequency($user),
                'uptimeTrend' => $this->dashboardData->uptimeTrend($user),
                'topProblematicSites' => $this->dashboardData->topProblematicSites($user),
            ],
        ]);
    }

}


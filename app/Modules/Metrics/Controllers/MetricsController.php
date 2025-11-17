<?php
declare(strict_types=1);

namespace App\Modules\Metrics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Metrics\Services\MetricsAggregator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MetricsController aggregates uptime, response, and traffic data for analytics.
 */
class MetricsController extends Controller
{
    public function __construct(private readonly MetricsAggregator $metricsAggregator)
    {
    }

    /**
     * Render the metrics dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $timeRange = $request->string('time_range')->value() ?? '7d';

        // Scope metrics to user's assigned clients (admin sees all)
        $cacheKey = $user->role === 'admin'
            ? "metrics:{$timeRange}"
            : "metrics:{$timeRange}:user:{$user->id}";

        $metrics = Cache::remember($cacheKey, 300, fn () => $this->metricsAggregator->buildMetrics($timeRange, $user));

        return Inertia::render('Metrics/Pages/Index', [
            'metrics' => $metrics,
        ]);
    }
}


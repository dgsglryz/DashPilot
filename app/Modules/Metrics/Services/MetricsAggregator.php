<?php
declare(strict_types=1);

namespace App\Modules\Metrics\Services;

use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * MetricsAggregator orchestrates metrics calculation and aggregation for the metrics dashboard.
 */
class MetricsAggregator
{
    public function __construct(
        private readonly MetricsCalculator $calculator,
        private readonly MetricsHistoryBuilder $historyBuilder,
        private readonly MetricsDistributionBuilder $distributionBuilder
    ) {
    }

    /**
     * Build the full metrics payload for a given time range.
     *
     * @param string $timeRange
     * @param User|null $user Optional user to scope metrics to their assigned clients
     *
     * @return array<string, mixed>
     */
    public function buildMetrics(string $timeRange, ?User $user = null): array
    {
        $bounds = $this->resolveTimeBounds($timeRange);
        $sitesQuery = $this->scopedSitesQuery($user);
        $siteChecksQuery = $this->scopedSiteChecksQuery($user);

        return [
            'averageUptime' => round($this->calculator->calculateAverageUptime(clone $sitesQuery), 2),
            'uptimeTrend' => round($this->calculator->calculateUptimeTrend($bounds['start'], $bounds['previous'], clone $siteChecksQuery), 2),
            'averageResponseTime' => round($this->calculator->calculateAverageResponseTime($bounds['start'], clone $siteChecksQuery), 2),
            'responseTrend' => round($this->calculator->calculateResponseTrend($bounds['start'], $bounds['previous'], clone $siteChecksQuery), 2),
            'totalRequests' => $this->calculator->calculateTotalRequests($bounds['start'], clone $siteChecksQuery),
            'requestsTrend' => round($this->calculator->calculateRequestsTrend($bounds['start'], $bounds['previous'], clone $siteChecksQuery), 2),
            'errorRate' => round($this->calculator->calculateErrorRate($bounds['start'], clone $siteChecksQuery), 2),
            'errorTrend' => round($this->calculator->calculateErrorTrend($bounds['start'], $bounds['previous'], clone $siteChecksQuery), 2),
            'uptimeHistory' => $this->historyBuilder->uptimeHistory($bounds['start'], clone $siteChecksQuery),
            'responseTimeHistory' => $this->historyBuilder->responseTimeHistory($bounds['start'], clone $siteChecksQuery),
            'trafficHistory' => $this->historyBuilder->trafficHistory($bounds['start'], clone $siteChecksQuery),
            'platformDistribution' => $this->distributionBuilder->platformDistribution(clone $sitesQuery),
            'topSites' => $this->distributionBuilder->topSites(clone $sitesQuery),
            'errorTypes' => $this->distributionBuilder->errorTypes($bounds['start'], clone $siteChecksQuery),
            'statusCodes' => $this->distributionBuilder->statusCodeDistribution($bounds['start'], clone $siteChecksQuery),
        ];
    }

    /**
     * Get scoped site query builder based on user role.
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
     * Get scoped site check query builder based on user role.
     *
     * @param User|null $user
     * @return Builder
     */
    private function scopedSiteChecksQuery(?User $user = null): Builder
    {
        $query = SiteCheck::query();

        if ($user && $user->role !== 'admin') {
            $query->whereHas('site.client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * Convert UI time range to concrete Carbon bounds.
     *
     * @return array{start: Carbon, previous: Carbon}
     */
    private function resolveTimeBounds(string $range): array
    {
        $map = [
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
        ];

        $days = $map[$range] ?? 7;
        $start = now()->subDays($days);
        $previous = now()->subDays($days * 2);

        return compact('start', 'previous');
    }
}



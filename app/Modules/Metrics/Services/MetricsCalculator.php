<?php
declare(strict_types=1);

namespace App\Modules\Metrics\Services;

use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * MetricsCalculator handles calculation of metrics values and trends.
 */
class MetricsCalculator
{
    /**
     * Calculate average uptime percentage across all sites.
     *
     * @param Builder $sitesQuery Scoped sites query
     * @return float
     */
    public function calculateAverageUptime(Builder $sitesQuery): float
    {
        return (float) ($sitesQuery->avg('uptime_percentage') ?? 0);
    }

    /**
     * Calculate uptime trend between two time periods.
     *
     * @param Carbon $currentStart Start of current period
     * @param Carbon $previousStart Start of previous period
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return float
     */
    public function calculateUptimeTrend(Carbon $currentStart, Carbon $previousStart, Builder $siteChecksQuery): float
    {
        $current = $this->percentageFromChecks($currentStart, null, $siteChecksQuery);
        $previous = $this->percentageFromChecks($previousStart, $currentStart, $siteChecksQuery);

        return $this->trendDelta($current, $previous);
    }

    /**
     * Calculate average response time.
     *
     * @param Carbon $start Start date
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return int
     */
    public function calculateAverageResponseTime(Carbon $start, Builder $siteChecksQuery): int
    {
        return (int) round(
            $siteChecksQuery->where('created_at', '>=', $start)->avg('response_time') ?? 0
        );
    }

    /**
     * Calculate response time trend between two periods.
     *
     * @param Carbon $currentStart Start of current period
     * @param Carbon $previousStart Start of previous period
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return float
     */
    public function calculateResponseTrend(Carbon $currentStart, Carbon $previousStart, Builder $siteChecksQuery): float
    {
        $current = $this->calculateAverageResponseTime($currentStart, $siteChecksQuery);
        $previous = $this->calculateAverageResponseTime($previousStart, $siteChecksQuery);

        return $this->trendDelta($previous, $current, invert: true);
    }

    /**
     * Calculate total number of requests.
     *
     * @param Carbon $start Start date
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return int
     */
    public function calculateTotalRequests(Carbon $start, Builder $siteChecksQuery): int
    {
        return $siteChecksQuery->where('created_at', '>=', $start)->count();
    }

    /**
     * Calculate requests trend between two periods.
     *
     * @param Carbon $currentStart Start of current period
     * @param Carbon $previousStart Start of previous period
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return float
     */
    public function calculateRequestsTrend(Carbon $currentStart, Carbon $previousStart, Builder $siteChecksQuery): float
    {
        $current = $this->calculateTotalRequests($currentStart, $siteChecksQuery);
        $previous = $siteChecksQuery->whereBetween('created_at', [$previousStart, $currentStart])->count();

        return $this->trendDelta($current, $previous);
    }

    /**
     * Calculate error rate percentage.
     *
     * @param Carbon $start Start date
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return float
     */
    public function calculateErrorRate(Carbon $start, Builder $siteChecksQuery): float
    {
        $query = $siteChecksQuery->where('created_at', '>=', $start);
        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $errors = (clone $query)->where('status', SiteCheck::STATUS_FAIL)->count();

        return round(($errors / $total) * 100, 2);
    }

    /**
     * Calculate error rate trend between two periods.
     *
     * @param Carbon $currentStart Start of current period
     * @param Carbon $previousStart Start of previous period
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return float
     */
    public function calculateErrorTrend(Carbon $currentStart, Carbon $previousStart, Builder $siteChecksQuery): float
    {
        $current = $this->calculateErrorRate($currentStart, $siteChecksQuery);
        $previous = $this->calculateErrorRate($previousStart, $siteChecksQuery);

        return $this->trendDelta($current, $previous);
    }

    /**
     * Calculate uptime percentage from site checks.
     *
     * @param Carbon $start Start date
     * @param Carbon|null $end End date (null for no end)
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return float
     */
    private function percentageFromChecks(Carbon $start, ?Carbon $end, Builder $siteChecksQuery): float
    {
        $query = $siteChecksQuery->where('created_at', '>=', $start);

        if ($end !== null) {
            $query->where('created_at', '<', $end);
        }

        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $passing = (clone $query)->where('status', '!=', SiteCheck::STATUS_FAIL)->count();

        return round(($passing / $total) * 100, 2);
    }

    /**
     * Calculate trend delta between two values.
     *
     * @param float $current Current value
     * @param float $previous Previous value
     * @param bool $invert Whether to invert the delta
     * @return float
     */
    private function trendDelta(float $current, float $previous, bool $invert = false): float
    {
        if ($previous <= 0) {
            return 0.0;
        }

        $delta = (($current - $previous) / $previous) * 100;

        return round($invert ? $delta * -1 : $delta, 2);
    }
}


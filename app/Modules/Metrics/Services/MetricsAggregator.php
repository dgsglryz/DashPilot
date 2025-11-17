<?php
declare(strict_types=1);

namespace App\Modules\Metrics\Services;

use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Models\Site;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MetricsAggregator encapsulates metrics calculations for the metrics dashboard.
 */
class MetricsAggregator
{
    /**
     * Build the full metrics payload for a given time range.
     *
     * @param string $timeRange
     *
     * @return array<string, mixed>
     */
    public function buildMetrics(string $timeRange): array
    {
        $bounds = $this->resolveTimeBounds($timeRange);

        return [
            'averageUptime' => round($this->calculateAverageUptime(), 2),
            'uptimeTrend' => round($this->calculateUptimeTrend($bounds['start'], $bounds['previous']), 2),
            'averageResponseTime' => round($this->calculateAverageResponseTime($bounds['start']), 2),
            'responseTrend' => round($this->calculateResponseTrend($bounds['start'], $bounds['previous']), 2),
            'totalRequests' => $this->calculateTotalRequests($bounds['start']),
            'requestsTrend' => round($this->calculateRequestsTrend($bounds['start'], $bounds['previous']), 2),
            'errorRate' => round($this->calculateErrorRate($bounds['start']), 2),
            'errorTrend' => round($this->calculateErrorTrend($bounds['start'], $bounds['previous']), 2),
            'uptimeHistory' => $this->uptimeHistory($bounds['start']),
            'responseTimeHistory' => $this->responseTimeHistory($bounds['start']),
            'trafficHistory' => $this->trafficHistory($bounds['start']),
            'platformDistribution' => $this->platformDistribution(),
            'topSites' => $this->topSites(),
            'errorTypes' => $this->errorTypes($bounds['start']),
            'statusCodes' => $this->statusCodeDistribution($bounds['start']),
        ];
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

    private function calculateAverageUptime(): float
    {
        return (float) (Site::avg('uptime_percentage') ?? 0);
    }

    private function calculateUptimeTrend(Carbon $currentStart, Carbon $previousStart): float
    {
        $current = $this->percentageFromChecks($currentStart);
        $previous = $this->percentageFromChecks($previousStart, $currentStart);

        return $this->trendDelta($current, $previous);
    }

    private function calculateAverageResponseTime(Carbon $start): int
    {
        return (int) round(
            SiteCheck::where('created_at', '>=', $start)->avg('response_time') ?? 0
        );
    }

    private function calculateResponseTrend(Carbon $currentStart, Carbon $previousStart): float
    {
        $current = $this->calculateAverageResponseTime($currentStart);
        $previous = $this->calculateAverageResponseTime($previousStart);

        return $this->trendDelta($previous, $current, invert: true);
    }

    private function calculateTotalRequests(Carbon $start): int
    {
        return SiteCheck::where('created_at', '>=', $start)->count();
    }

    private function calculateRequestsTrend(Carbon $currentStart, Carbon $previousStart): float
    {
        $current = $this->calculateTotalRequests($currentStart);
        $previous = SiteCheck::whereBetween('created_at', [$previousStart, $currentStart])->count();

        return $this->trendDelta($current, $previous);
    }

    private function calculateErrorRate(Carbon $start): float
    {
        $total = SiteCheck::where('created_at', '>=', $start)->count();

        if ($total === 0) {
            return 0.0;
        }

        $errors = SiteCheck::where('created_at', '>=', $start)
            ->where('status', SiteCheck::STATUS_FAIL)
            ->count();

        return round(($errors / $total) * 100, 2);
    }

    private function calculateErrorTrend(Carbon $currentStart, Carbon $previousStart): float
    {
        $current = $this->calculateErrorRate($currentStart);
        $previous = $this->calculateErrorRate($previousStart);

        return $this->trendDelta($current, $previous);
    }

    private function uptimeHistory(Carbon $start): array
    {
        $records = SiteCheck::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, AVG(CASE status WHEN ? THEN 100 WHEN ? THEN 85 ELSE 20 END) as uptime', [
                SiteCheck::STATUS_PASS,
                SiteCheck::STATUS_WARNING,
            ])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'labels' => $records->pluck('day')->map(fn (string $date) => Carbon::parse($date)->format('M d'))->all(),
            'values' => $records->pluck('uptime')->map(fn ($value) => round((float) $value, 2))->all(),
        ];
    }

    private function responseTimeHistory(Carbon $start): array
    {
        $records = SiteCheck::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, AVG(response_time) as avg_response')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'labels' => $records->pluck('day')->map(fn (string $date) => Carbon::parse($date)->format('M d'))->all(),
            'values' => $records->pluck('avg_response')->map(fn ($value) => (int) round((float) $value))->all(),
        ];
    }

    private function trafficHistory(Carbon $start): array
    {
        $records = SiteCheck::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as requests')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $requests = $records->pluck('requests')->map(fn ($value) => (int) $value)->all();

        return [
            'labels' => $records->pluck('day')->map(fn (string $date) => Carbon::parse($date)->format('M d'))->all(),
            'requests' => $requests,
            'bandwidth' => collect($requests)->map(fn (int $value) => round($value * 0.45, 1))->all(),
            'uniqueVisitors' => collect($requests)->map(fn (int $value) => (int) round($value * 0.7))->all(),
        ];
    }

    private function platformDistribution(): array
    {
        return Site::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();
    }

    private function topSites(): array
    {
        return Site::orderByDesc('uptime_percentage')
            ->limit(5)
            ->get()
            ->map(function (Site $site) {
                $host = parse_url($site->url, PHP_URL_HOST) ?? $site->url;

                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'url' => $site->url,
                    'favicon' => "https://www.google.com/s2/favicons?domain={$host}&sz=64",
                    'uptime' => (float) ($site->uptime_percentage ?? 0),
                    'responseTime' => (int) ($site->avg_load_time ?? 0),
                    'seoScore' => (int) ($site->health_score ?? 0),
                ];
            })
            ->all();
    }

    private function errorTypes(Carbon $start): array
    {
        $checks = SiteCheck::where('created_at', '>=', $start)
            ->where('status', SiteCheck::STATUS_FAIL)
            ->get(['check_type']);

        $grouped = $checks->groupBy('check_type')->map(fn (Collection $group) => $group->count());
        $total = max(1, $grouped->sum());

        return $grouped->map(function (int $count, string $type) use ($total) {
            return [
                'type' => $type,
                'label' => Str::headline($type),
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 1),
            ];
        })->values()->all();
    }

    private function statusCodeDistribution(Carbon $start): array
    {
        $codes = SiteCheck::where('created_at', '>=', $start)
            ->get(['details'])
            ->map(fn (SiteCheck $check) => $check->details['http_status'] ?? null)
            ->filter()
            ->countBy()
            ->all();

        if (empty($codes)) {
            return [
                '200' => 0,
                '301' => 0,
                '404' => 0,
                '500' => 0,
            ];
        }

        return $codes;
    }

    private function percentageFromChecks(Carbon $start, ?Carbon $end = null): float
    {
        $query = SiteCheck::where('created_at', '>=', $start);

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

    private function trendDelta(float $current, float $previous, bool $invert = false): float
    {
        if ($previous <= 0) {
            return 0.0;
        }

        $delta = (($current - $previous) / $previous) * 100;

        return round($invert ? $delta * -1 : $delta, 2);
    }
}



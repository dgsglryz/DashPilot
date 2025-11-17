<?php
declare(strict_types=1);

namespace App\Modules\Metrics\Services;

use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Models\Site;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MetricsDistributionBuilder builds distribution and aggregation data.
 */
class MetricsDistributionBuilder
{
    /**
     * Build platform distribution data.
     *
     * @param Builder $sitesQuery Scoped sites query
     * @return array<string, int>
     */
    public function platformDistribution(Builder $sitesQuery): array
    {
        // Clone query to avoid modifying the original
        $query = clone $sitesQuery;
        
        $distribution = $query
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderBy('total', 'desc')
            ->pluck('total', 'type')
            ->all();

        // Ensure wordpress and shopify keys always exist for chart compatibility
        return [
            'wordpress' => (int) ($distribution['wordpress'] ?? $distribution['woocommerce'] ?? 0),
            'shopify' => (int) ($distribution['shopify'] ?? 0),
        ];
    }

    /**
     * Build top sites list.
     *
     * @param Builder $sitesQuery Scoped sites query
     * @return array<int, array<string, mixed>>
     */
    public function topSites(Builder $sitesQuery): array
    {
        // Clone query to avoid modifying the original
        $query = clone $sitesQuery;
        
        return $query
            ->orderByDesc('uptime_percentage')
            ->limit(5)
            ->get()
            ->map(function (Site $site) {
                $url = $site->url ?? '';
                $host = $url ? (parse_url($url, PHP_URL_HOST) ?? $url) : '';

                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'url' => $url,
                    'favicon' => $host ? "https://www.google.com/s2/favicons?domain={$host}&sz=64" : '',
                    'uptime' => (float) ($site->uptime_percentage ?? 0),
                    'responseTime' => (int) ($site->avg_load_time ?? 0),
                    'seoScore' => (int) ($site->health_score ?? 0),
                ];
            })
            ->all();
    }

    /**
     * Build error types distribution.
     *
     * @param Carbon $start Start date
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return array<int, array<string, mixed>>
     */
    public function errorTypes(Carbon $start, Builder $siteChecksQuery): array
    {
        $checks = $siteChecksQuery->where('created_at', '>=', $start)
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

    /**
     * Build status code distribution.
     *
     * @param Carbon $start Start date
     * @param Builder $siteChecksQuery Scoped site checks query
     * @return array<string, int>
     */
    public function statusCodeDistribution(Carbon $start, Builder $siteChecksQuery): array
    {
        $codes = $siteChecksQuery->where('created_at', '>=', $start)
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
}


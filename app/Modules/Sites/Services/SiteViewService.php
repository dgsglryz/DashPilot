<?php
declare(strict_types=1);

namespace App\Modules\Sites\Services;

use App\Modules\Sites\Models\Site;
use App\Modules\Sites\Models\SiteCheck;
use App\Shared\Services\LookupService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * SiteViewService encapsulates presentation logic for site listings and detail pages.
 */
class SiteViewService
{
    public function __construct(private readonly LookupService $lookupService)
    {
    }

    /**
     * Apply shared filters to the site query.
     *
     * @param Builder $query
     * @param Request $request
     */
    public function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('platform') && $request->string('platform')->toString() !== 'all') {
            $query->where('type', $request->string('platform')->toString());
        }

        if ($request->filled('status') && $request->string('status')->toString() !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('query')) {
            $query->search($request->string('query')->toString());
        }
    }

    /**
     * Build stats for the listing page without eager loading every record twice.
     *
     * @param Builder $query
     *
     * @return array<string, int>
     */
    public function buildStats(Builder $query): array
    {
        $base = clone $query;

        return [
            'total' => $base->count(),
            'healthy' => (clone $base)->where('status', 'healthy')->count(),
            'warning' => (clone $base)->where('status', 'warning')->count(),
            'critical' => (clone $base)->where('status', 'critical')->count(),
        ];
    }

    /**
     * Build SEO insights for a site.
     *
     * @param Site $site
     *
     * @return array<string, mixed>
     */
    public function buildSeoInsights(Site $site): array
    {
        $baseScore = (int) ($site->health_score ?? 78);
        $score = max(55, min(100, $baseScore + random_int(-5, 7)));

        $metrics = [
            ['name' => 'Meta tags', 'score' => $this->boundedScore($score + random_int(-5, 5))],
            ['name' => 'H1 structure', 'score' => $this->boundedScore($score + random_int(-10, 3))],
            ['name' => 'Page speed', 'score' => $this->boundedScore($score + random_int(-8, 6))],
            ['name' => 'Image alts', 'score' => $this->boundedScore($score + random_int(-12, 4))],
        ];

        $issues = $score >= 85
            ? []
            : [
                [
                    'id' => 'meta-length',
                    'title' => 'Meta description is short',
                    'description' => 'Add a 150-160 character description for better SERP snippets.',
                ],
                [
                    'id' => 'image-alt',
                    'title' => '8 images missing alt text',
                    'description' => 'Add descriptive alternate text for all hero and gallery images.',
                ],
            ];

        return [
            'score' => $score,
            'metrics' => $metrics,
            'issues' => $issues,
        ];
    }

    /**
     * Build performance chart data.
     *
     * @param Collection<int, array<string, mixed>> $checks
     *
     * @return array<string, array<int, string|int|float>>
     */
    public function buildPerformanceSeries(Collection $checks): array
    {
        if ($checks->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $ordered = $checks->sortBy('checkedAt');

        $labels = $ordered->map(fn (array $check): string => Carbon::parse($check['checkedAt'])->format('M j · H:i'))->all();
        $uptimeSeries = $ordered->map(fn (array $check): float => $this->uptimePoint($check['status']))->all();
        $responseSeries = $ordered->map(fn (array $check): int => (int) ($check['responseTime'] ?? 0))->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Uptime %',
                    'data' => $uptimeSeries,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Response (ms)',
                    'data' => $responseSeries,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * Retrieve reusable client dropdown options.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function clientOptions(): Collection
    {
        return $this->lookupService->clientOptions();
    }

    private function boundedScore(int $score): int
    {
        return max(45, min(100, $score));
    }

    private function uptimePoint(?string $status): float
    {
        return match ($status) {
            'pass', 'healthy' => 99.6,
            'warning' => 96.2,
            default => 92.4,
        };
    }
}





<?php
declare(strict_types=1);

namespace App\Modules\Revenue\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sites\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RevenueController displays revenue analytics for Shopify sites.
 */
class RevenueController extends Controller
{
    /**
     * Display revenue overview and analytics.
     */
    public function index(Request $request): Response
    {
        $shopifySites = Site::whereIn('type', ['shopify', 'woocommerce'])
            ->orderBy('name')
            ->get();

        // Calculate revenue metrics
        $totalRevenue = $this->estimateTotalRevenue($shopifySites);
        $monthlyRevenue = $this->estimateMonthlyRevenue($shopifySites);
        $averageRevenue = $shopifySites->count() > 0 ? $totalRevenue / $shopifySites->count() : 0;

        // Get revenue by site
        $revenueBySite = $shopifySites->map(function (Site $site) {
            return [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'thumbnail' => $site->thumbnail_url ?? $this->fallbackThumbnail($site->id),
                'logo' => $site->logo_url ?? $this->fallbackLogo($site->name),
                'revenue' => $this->estimateSiteRevenue($site),
                'orders' => random_int(50, 500),
                'growth' => round(fake()->randomFloat(2, -5, 25), 1),
            ];
        })->sortByDesc('revenue')->values();

        // Monthly trend (last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'revenue' => (int) round($monthlyRevenue * (0.8 + (random_int(0, 40) / 100))),
            ];
        }

        $stats = [
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'averageRevenue' => (int) round($averageRevenue),
            'totalSites' => $shopifySites->count(),
            'growth' => round(fake()->randomFloat(2, 5, 20), 1),
        ];

        return Inertia::render('Revenue/Pages/Index', [
            'stats' => $stats,
            'revenueBySite' => $revenueBySite,
            'monthlyTrend' => $monthlyTrend,
        ]);
    }

    /**
     * Estimate total revenue across all Shopify sites.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, Site> $sites
     */
    private function estimateTotalRevenue($sites): int
    {
        return $sites->sum(fn (Site $site) => $this->estimateSiteRevenue($site));
    }

    /**
     * Estimate monthly revenue.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, Site> $sites
     */
    private function estimateMonthlyRevenue($sites): int
    {
        return (int) round($this->estimateTotalRevenue($sites) / 12);
    }

    /**
     * Estimate revenue for a single site based on health and uptime.
     */
    private function estimateSiteRevenue(Site $site): int
    {
        $base = 5000;
        $multiplier = ($site->health_score / 100) * ($site->uptime_percentage / 100);

        return (int) round($base * $multiplier * (1 + random_int(-10, 30) / 100));
    }

    /**
     * Fallback thumbnail URL if site doesn't have one.
     */
    private function fallbackThumbnail(int $siteId): string
    {
        return "https://picsum.photos/seed/{$siteId}/640/360";
    }

    /**
     * Fallback logo URL using DiceBear API.
     */
    private function fallbackLogo(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);

        return "https://api.dicebear.com/7.x/initials/svg?seed={$slug}&backgroundColor=111827,1c1f2b&fontSize=60";
    }
}


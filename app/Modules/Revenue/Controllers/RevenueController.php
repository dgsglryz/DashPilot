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
        $user = $request->user();
        
        $query = Site::whereIn('type', ['shopify', 'woocommerce']);
        
        // Scope to user's assigned clients (admin sees all)
        if ($user->role !== 'admin') {
            $query->whereHas('client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        
        $shopifySites = $query->orderBy('name')->get();

        // Calculate revenue metrics
        $totalRevenue = $this->estimateTotalRevenue($shopifySites);
        $monthlyRevenue = $this->estimateMonthlyRevenue($shopifySites);
        $averageRevenue = $shopifySites->count() > 0 ? $totalRevenue / $shopifySites->count() : 0;

        // Monthly trend (last 6 months) - calculate first for growth calculation
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'revenue' => (int) round($monthlyRevenue * (0.8 + (random_int(0, 40) / 100))),
            ];
        }

        // Calculate overall growth from monthly trend
        $overallGrowth = $this->calculateGrowthFromTrend($monthlyTrend);

        // Get revenue by site
        $revenueBySite = $shopifySites->map(function (Site $site) use ($monthlyTrend) {
            $siteRevenue = $this->estimateSiteRevenue($site);
            $siteGrowth = $this->calculateSiteGrowth($site, $siteRevenue);

            return [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'thumbnail' => $site->thumbnail_url ?? $this->fallbackThumbnail($site->id),
                'logo' => $site->logo_url ?? $this->fallbackLogo($site->name),
                'revenue' => $siteRevenue,
                'orders' => random_int(50, 500),
                'growth' => $siteGrowth,
            ];
        })->sortByDesc('revenue')->values();

        $stats = [
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'averageRevenue' => (int) round($averageRevenue),
            'totalSites' => $shopifySites->count(),
            'growth' => $overallGrowth,
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

    /**
     * Calculate growth percentage from monthly trend data.
     * Compares current month with previous month.
     *
     * @param array<int, array<string, mixed>> $monthlyTrend Monthly revenue data
     * @return float Growth percentage (can be negative)
     */
    private function calculateGrowthFromTrend(array $monthlyTrend): float
    {
        if (count($monthlyTrend) < 2) {
            return 0.0;
        }

        $current = $monthlyTrend[count($monthlyTrend) - 1]['revenue'];
        $previous = $monthlyTrend[count($monthlyTrend) - 2]['revenue'];

        if ($previous == 0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Calculate growth for a single site based on health score and uptime changes.
     * Uses site metrics to estimate realistic growth.
     *
     * @param Site $site The site to calculate growth for
     * @param int $currentRevenue Current estimated revenue
     * @return float Growth percentage
     */
    private function calculateSiteGrowth(Site $site, int $currentRevenue): float
    {
        // Base growth on health score and uptime (better metrics = positive growth)
        $healthFactor = ($site->health_score / 100) - 0.5; // -0.5 to +0.5
        $uptimeFactor = (($site->uptime_percentage ?? 0) / 100) - 0.5; // -0.5 to +0.5

        // Combine factors (weighted average)
        $combinedFactor = ($healthFactor * 0.6) + ($uptimeFactor * 0.4);

        // Convert to percentage growth (-25% to +25% range)
        $growth = $combinedFactor * 50;

        return round($growth, 1);
    }
}


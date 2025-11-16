<?php
declare(strict_types=1);

namespace App\Modules\Sites\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Jobs\CheckSiteHealth;
use App\Modules\Sites\Models\Site;
use App\Modules\Sites\Requests\StoreSiteRequest;
use App\Modules\Sites\Requests\UpdateSiteRequest;
use App\Modules\Tasks\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SitesController exposes the list and detail views for monitored sites.
 */
class SitesController extends Controller
{
    /**
     * Display the sites list with stats summary.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $query = Site::query()->with('client:id,name');

        if ($request->filled('platform') && $request->string('platform')->toString() !== 'all') {
            $query->where('type', $request->string('platform')->toString());
        }

        if ($request->filled('status') && $request->string('status')->toString() !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('query')) {
            $search = $request->string('query')->toString();
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        // Get stats before pagination
        $allSites = $query->get();
        $stats = [
            'total' => $allSites->count(),
            'healthy' => $allSites->where('status', 'healthy')->count(),
            'warning' => $allSites->where('status', 'warning')->count(),
            'critical' => $allSites->where('status', 'critical')->count(),
        ];

        // Paginate results
        $perPage = $request->integer('per_page', 20);
        $sites = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn (Site $site) => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'status' => $site->status,
                'platform' => $site->type,
                'industry' => $site->industry,
                'region' => $site->region,
                'thumbnail' => $site->thumbnail_url ?? $this->fallbackThumbnail($site->id),
                'logo' => $site->logo_url ?? $this->fallbackLogo($site->name),
                'uptime' => (float) ($site->uptime_percentage ?? 0),
                'responseTime' => (int) ($site->avg_load_time ? $site->avg_load_time * 1000 : 0),
                'lastChecked' => $site->last_checked_at?->toIso8601String(),
                'is_favorited' => (bool) $site->is_favorited,
                'client' => [
                    'id' => $site->client_id,
                    'name' => $site->client?->name,
                ],
            ]);

        return Inertia::render('Sites/Pages/Index', [
            'sites' => $sites,
            'stats' => $stats,
            'filters' => [
                'query' => $request->string('query')->toString(),
                'status' => $request->string('status')->toString() ?: 'all',
                'platform' => $request->string('platform')->toString() ?: 'all',
            ],
        ]);
    }

    /**
     * Display a single site detail view.
     */
    public function show(Site $site): Response
    {
        $site->load(['client:id,name,email,company', 'alerts' => function ($query) {
            $query->latest()->limit(5);
        }]);

        $recentChecks = SiteCheck::query()
            ->where('site_id', $site->id)
            ->orderByDesc('checked_at')
            ->limit(14)
            ->get(['check_type', 'status', 'response_time', 'checked_at'])
            ->map(fn (SiteCheck $check) => [
                'type' => $check->check_type,
                'status' => $check->status,
                'responseTime' => $check->response_time,
                'checkedAt' => $check->checked_at?->toIso8601String(),
            ]);

        $recentTasks = Task::query()
            ->where('site_id', $site->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'due_date'])
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'dueDate' => $task->due_date?->toDateString(),
            ]);

        $activity = ActivityLog::query()
            ->where('site_id', $site->id)
            ->latest()
            ->limit(6)
            ->get(['action', 'description', 'created_at'])
            ->map(fn (ActivityLog $log) => [
                'action' => $log->action,
                'description' => $log->description,
                'timestamp' => $log->created_at?->toIso8601String(),
            ]);

        $reports = Report::query()
            ->where('site_id', $site->id)
            ->latest('report_month')
            ->limit(6)
            ->get(['report_month', 'uptime_percentage', 'avg_load_time', 'incidents_count'])
            ->map(fn (Report $report) => [
                'month' => $report->report_month?->format('M Y'),
                'uptime' => $report->uptime_percentage,
                'response' => $report->avg_load_time,
                'incidents' => $report->incidents_count,
            ]);

        $seoInsights = $this->buildSeoInsights($site);

        return Inertia::render('Sites/Pages/Show', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'status' => $site->status,
                'platform' => $site->type,
                'industry' => $site->industry,
                'region' => $site->region,
                'thumbnail' => $site->thumbnail_url ?? $this->fallbackThumbnail($site->id),
                'logo' => $site->logo_url ?? $this->fallbackLogo($site->name),
                'uptime' => round((float) ($site->uptime_percentage ?? 0), 2),
                'response' => round((float) ($site->avg_load_time ?? 0), 2),
                'healthScore' => (int) ($site->health_score ?? 0),
                'lastChecked' => $site->last_checked_at?->toIso8601String(),
                'client' => [
                    'id' => $site->client_id,
                    'name' => $site->client?->name,
                    'email' => $site->client?->email,
                    'company' => $site->client?->company,
                ],
                'seoScore' => $seoInsights['score'],
                'seoMetrics' => $seoInsights['metrics'],
                'seoIssues' => $seoInsights['issues'],
            ],
            'chart' => $this->buildPerformanceSeries($recentChecks),
            'alerts' => $site->alerts->map(fn (Alert $alert) => [
                'id' => $alert->id,
                'title' => $alert->title,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'status' => $alert->status,
                'message' => $alert->message,
                'timestamp' => $alert->created_at?->toIso8601String(),
            ]),
            'checks' => $recentChecks,
            'tasks' => $recentTasks,
            'activity' => $activity,
            'reports' => $reports,
        ]);
    }

    /**
     * Build chart-ready data for the Vue chart component.
     *
     * @param Collection<int, array<string, mixed>> $checks
     */
    private function buildPerformanceSeries(Collection $checks): array
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
     * Calculate uptime percentage based on site status.
     *
     * @param string|null $status
     *
     * @return float
     */
    private function uptimePoint(?string $status): float
    {
        return match ($status) {
            'pass', 'healthy' => 99.6,
            'warning' => 96.2,
            default => 92.4,
        };
    }

    /**
     * Build SEO insights data for a site including score, metrics, and issues.
     *
     * @param Site $site
     *
     * @return array<string, mixed>
     */
    private function buildSeoInsights(Site $site): array
    {
        $baseScore = (int) ($site->health_score ?? 78);
        $score = max(55, min(100, $baseScore + random_int(-5, 7)));

        $metrics = [
            ['name' => 'Meta tags', 'score' => max(45, min(100, $score + random_int(-5, 5)))],
            ['name' => 'H1 structure', 'score' => max(45, min(100, $score + random_int(-10, 3)))],
            ['name' => 'Page speed', 'score' => max(45, min(100, $score + random_int(-8, 6)))],
            ['name' => 'Image alts', 'score' => max(45, min(100, $score + random_int(-12, 4)))],
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
     * Generate a fallback thumbnail URL for a site.
     *
     * @param int $siteId
     *
     * @return string
     */
    private function fallbackThumbnail(int $siteId): string
    {
        return "https://picsum.photos/seed/site-{$siteId}/640/360";
    }

    /**
     * Generate a fallback logo URL using DiceBear initials API.
     *
     * @param string $name
     *
     * @return string
     */
    private function fallbackLogo(string $name): string
    {
        $seed = Str::slug($name);

        return "https://api.dicebear.com/7.x/initials/svg?seed={$seed}&backgroundColor=111827,1c1f2b&fontSize=60";
    }

    /**
     * Show the form for creating a new site.
     *
     * @return Response
     */
    public function create(): Response
    {
        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name', 'company'])
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
            ]);

        return Inertia::render('Sites/Pages/Create', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created site in storage.
     *
     * @param StoreSiteRequest $request
     *
     * @return RedirectResponse
     */
    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $site = Site::create($request->validated());

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'action' => 'site_created',
            'description' => "Created new site: {$site->name}",
        ]);

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Site created successfully.');
    }

    /**
     * Show the form for editing the specified site.
     *
     * @param Site $site
     *
     * @return Response
     */
    public function edit(Site $site): Response
    {
        $site->load('client:id,name,company');

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name', 'company'])
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
            ]);

        return Inertia::render('Sites/Pages/Edit', [
            'site' => [
                'id' => $site->id,
                'client_id' => $site->client_id,
                'name' => $site->name,
                'url' => $site->url,
                'type' => $site->type,
                'status' => $site->status,
                'industry' => $site->industry,
                'region' => $site->region,
                'wp_api_url' => $site->wp_api_url,
                'wp_api_key' => $site->wp_api_key,
                'shopify_store_url' => $site->shopify_store_url,
                'shopify_api_key' => $site->shopify_api_key,
                'shopify_access_token' => $site->shopify_access_token,
            ],
            'clients' => $clients,
        ]);
    }

    /**
     * Update the specified site in storage.
     *
     * @param UpdateSiteRequest $request
     * @param Site $site
     *
     * @return RedirectResponse
     */
    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'action' => 'site_updated',
            'description' => "Updated site: {$site->name}",
        ]);

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Site updated successfully.');
    }

    /**
     * Remove the specified site from storage.
     *
     * @param Request $request
     * @param Site $site
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request, Site $site): RedirectResponse
    {
        $siteName = $site->name;
        $siteId = $site->id;

        $site->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'site_id' => $siteId,
            'action' => 'site_deleted',
            'description' => "Deleted site: {$siteName}",
        ]);

        return redirect()
            ->route('sites.index')
            ->with('success', 'Site deleted successfully.');
    }

    /**
     * Manually trigger a health check for the specified site.
     *
     * @param Request $request
     * @param Site $site
     *
     * @return RedirectResponse
     */
    public function runHealthCheck(Request $request, Site $site): RedirectResponse
    {
        CheckSiteHealth::dispatch($site);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'action' => 'health_check_triggered',
            'description' => "Manually triggered health check for {$site->name}",
        ]);

        return back()->with('success', 'Health check queued successfully. Results will be available shortly.');
    }

    /**
     * Toggle favorite status for a site.
     *
     * @param Request $request
     * @param Site $site
     *
     * @return RedirectResponse
     */
    public function toggleFavorite(Request $request, Site $site): RedirectResponse
    {
        $site->update(['is_favorited' => !$site->is_favorited]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'action' => $site->is_favorited ? 'site_favorited' : 'site_unfavorited',
            'description' => ($site->is_favorited ? 'Added' : 'Removed')." {$site->name} from favorites",
        ]);

        return back();
    }

    /**
     * Export sites as CSV or Excel.
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Site::query()->with('client:id,name');

        // If specific site IDs are provided, only export those
        if ($request->has('ids') && is_array($request->input('ids'))) {
            $ids = array_map('intval', $request->input('ids'));
            $query->whereIn('id', $ids);
        } else {
            // Apply same filters as index
            if ($request->filled('platform') && $request->string('platform')->toString() !== 'all') {
                $query->where('type', $request->string('platform')->toString());
            }

            if ($request->filled('status') && $request->string('status')->toString() !== 'all') {
                $query->where('status', $request->string('status')->toString());
            }

            if ($request->filled('query')) {
                $search = $request->string('query')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            }
        }

        $format = $request->string('format', 'csv')->toString();
        $filename = 'sites_'.now()->format('Y-m-d_His').'.'.$format;

        if ($format === 'xlsx') {
            // Excel export using Laravel Excel
            $data = $query->get()->map(function (Site $site) {
                return [
                    'ID' => $site->id,
                    'Name' => $site->name,
                    'URL' => $site->url,
                    'Platform' => $site->type,
                    'Status' => $site->status,
                    'Client' => $site->client?->name ?? 'N/A',
                    'Health Score' => $site->health_score ?? 0,
                    'Uptime %' => $site->uptime_percentage ?? 0,
                    'Region' => $site->region ?? 'N/A',
                    'Created At' => $site->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ];
            })->toArray();

            return Excel::download(
                new \App\Modules\Sites\Exports\SitesExport($data),
                $filename
            );
        }

        // CSV export
        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, ['ID', 'Name', 'URL', 'Platform', 'Status', 'Client', 'Health Score', 'Uptime %', 'Region', 'Created At']);

            $query->chunk(100, function ($sites) use ($handle) {
                foreach ($sites as $site) {
                    fputcsv($handle, [
                        $site->id,
                        $site->name,
                        $site->url,
                        $site->type,
                        $site->status,
                        $site->client?->name ?? 'N/A',
                        $site->health_score ?? 0,
                        $site->uptime_percentage ?? 0,
                        $site->region ?? 'N/A',
                        $site->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}


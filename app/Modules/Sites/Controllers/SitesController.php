<?php
declare(strict_types=1);

namespace App\Modules\Sites\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Jobs\CheckSiteHealth;
use App\Modules\Sites\Models\Site;
use App\Modules\Sites\Requests\StoreSiteRequest;
use App\Modules\Sites\Requests\UpdateSiteRequest;
use App\Modules\Sites\Services\SiteViewService;
use App\Modules\Tasks\Models\Task;
use App\Shared\Helpers\SiteMediaHelper;
use App\Shared\Services\LookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SitesController exposes the list and detail views for monitored sites.
 */
class SitesController extends Controller
{
    public function __construct(
        private readonly LookupService $lookupService,
        private readonly SiteViewService $siteViewService
    ) {
    }

    /**
     * Display the sites list with stats summary.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        // Admin users see all sites, others see only their assigned clients
        $user = $request->user();
        $query = Site::query()
            ->with(['client:id,name', 'checks' => fn ($q) => $q->latest()->take(5)]);
        
        if ($user->role !== 'admin') {
            $query->whereHas('client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }

        $this->siteViewService->applyFilters($query, $request);

        // Get stats using database queries (more efficient than loading all records)
        $stats = $this->siteViewService->buildStats(clone $query);

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
                'thumbnail' => $site->thumbnail_url ?? SiteMediaHelper::thumbnail($site->id),
                'logo' => $site->logo_url ?? SiteMediaHelper::logo($site->name),
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
            'clients' => $this->siteViewService->clientOptions(),
        ]);
    }

    /**
     * Display a single site detail view.
     */
    public function show(Site $site): Response
    {
        $this->authorize('view', $site);
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

        $seoInsights = $this->siteViewService->buildSeoInsights($site);

        return Inertia::render('Sites/Pages/Show', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'status' => $site->status,
                'platform' => $site->type,
                'industry' => $site->industry,
                'region' => $site->region,
                'thumbnail' => $site->thumbnail_url ?? SiteMediaHelper::thumbnail($site->id),
                'logo' => $site->logo_url ?? SiteMediaHelper::logo($site->name),
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
            'chart' => $this->siteViewService->buildPerformanceSeries($recentChecks),
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
     * Show the form for creating a new site.
     *
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('Sites/Pages/Create', [
            'clients' => $this->siteViewService->clientOptions(),
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
        $this->authorize('update', $site);
        $site->load('client:id,name,company');

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
                'has_wp_api_key' => !empty($site->wp_api_key),
                'shopify_store_url' => $site->shopify_store_url,
                'has_shopify_api_key' => !empty($site->shopify_api_key),
                'has_shopify_access_token' => !empty($site->shopify_access_token),
            ],
            'clients' => $this->siteViewService->clientOptions(),
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
        $this->authorize('update', $site);

        $validated = $request->validated();
        $updateData = $validated;

        // Only update API keys if they are provided (not empty)
        // This allows users to keep existing keys by leaving fields blank
        if (empty($validated['wp_api_key'])) {
            unset($updateData['wp_api_key']);
        }
        if (empty($validated['shopify_api_key'])) {
            unset($updateData['shopify_api_key']);
        }
        if (empty($validated['shopify_access_token'])) {
            unset($updateData['shopify_access_token']);
        }

        $site->update($updateData);

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
        $this->authorize('delete', $site);

        $siteName = $site->name;

        $site->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'site_id' => null, // Site is deleted, so set to null to avoid foreign key constraint
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
        $this->authorize('view', $site);

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
        $this->authorize('view', $site);

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
     * @return StreamedResponse|BinaryFileResponse
     */
    public function export(Request $request): StreamedResponse|BinaryFileResponse
    {
        // Filter sites to only show those belonging to user's assigned clients
        $query = Site::query()
            ->whereHas('client', function ($q) use ($request) {
                $q->where('assigned_developer_id', $request->user()->id);
            })
            ->with('client:id,name');

        if ($request->has('ids') && is_array($request->input('ids'))) {
            $ids = array_map('intval', $request->input('ids'));
            $query->whereIn('id', $ids);
        } else {
            $this->siteViewService->applyFilters($query, $request);
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


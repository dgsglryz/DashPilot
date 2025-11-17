<?php
declare(strict_types=1);

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Models\Report;
use App\Modules\Reports\Services\ReportGeneratorService;
use App\Modules\Sites\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ReportsController exposes the reporting dashboard and generation actions.
 */
class ReportsController extends Controller
{
    /**
     * Display report templates and recent activity.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);
        
        $user = $request->user();
        $sitesQuery = Site::query();
        
        if ($user->role !== 'admin') {
            $sitesQuery->whereHas('client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        
        $sites = $sitesQuery->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Reports/Pages/Index', [
            'reportTemplates' => $this->templates(),
            'recentReports' => $this->recentReports($request),
            'sites' => $sites,
        ]);
    }

    /**
     * Generate a new report via the service layer.
     */
    public function generate(Request $request, ReportGeneratorService $generator): RedirectResponse
    {
        $this->authorize('create', Report::class);
        
        $validated = $request->validate([
            'templateId' => ['required', 'integer'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'siteIds' => ['required', 'array', 'min:1'],
            'siteIds.*' => ['string'],
            'format' => ['required', 'in:pdf,csv,xlsx'],
        ]);

        $siteIds = $validated['siteIds'];

        // Filter out 'all' if specific sites are selected
        $siteIds = array_filter($siteIds, fn($id) => $id !== 'all');
        
        // If no specific sites selected or only 'all' was selected, use all sites for user's assigned clients
        if (empty($siteIds)) {
            $siteIds = Site::whereHas('client', function ($q) use ($request) {
                $q->where('assigned_developer_id', $request->user()->id);
            })
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        } else {
            // Verify all requested sites belong to user's assigned clients
            $validSiteIds = Site::whereHas('client', function ($q) use ($request) {
                $q->where('assigned_developer_id', $request->user()->id);
            })
                ->whereIn('id', array_map('intval', $siteIds))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            if (count($validSiteIds) !== count($siteIds)) {
                abort(403, 'Unauthorized access to one or more sites.');
            }

            $siteIds = $validSiteIds;
        }

        $generator->generate(
            templateId: (int) $validated['templateId'],
            startDate: $validated['startDate'],
            endDate: $validated['endDate'],
            siteIds: array_map('intval', $siteIds),
            format: $validated['format']
        );

        return back()->with('success', 'Report queued successfully.');
    }

    /**
     * Quickly generate a report for a single site and return a download link.
     */
    public function generateForSite(Request $request, Site $site, ReportGeneratorService $generator): JsonResponse
    {
        $this->authorize('view', $site);

        $validated = $request->validate([
            'templateId' => ['nullable', 'integer'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'format' => ['nullable', 'in:pdf,csv,xlsx'],
        ]);

        $templateId = (int) ($validated['templateId'] ?? 1);
        $format = $validated['format'] ?? 'pdf';

        $generator->generate(
            templateId: $templateId,
            startDate: $validated['startDate'],
            endDate: $validated['endDate'],
            siteIds: [$site->id],
            format: $format
        );

        $report = Report::where('site_id', $site->id)
            ->latest('generated_at')
            ->first();

        if (!$report || !$report->pdf_path) {
            return response()->json([
                'message' => 'Report generated but file is not available yet.',
            ], 202);
        }

        return response()->json([
            'message' => 'Report generated successfully.',
            'downloadUrl' => route('reports.download', $report),
        ]);
    }

    /**
     * Download a generated report asset.
     */
    public function download(Report $report)
    {
        $this->authorize('view', $report);

        if (!$report->pdf_path || !Storage::disk('local')->exists($report->pdf_path)) {
            return back()->with('error', 'Report file is not available.');
        }

        // Prevent path traversal attacks
        if (!str_starts_with($report->pdf_path, 'reports/')) {
            abort(404, 'Invalid file path.');
        }

        // Block any path traversal attempts
        if (str_contains($report->pdf_path, '..')) {
            abort(404, 'Invalid file path.');
        }

        return Storage::download($report->pdf_path, basename($report->pdf_path));
    }

    /**
     * Delete a report and any stored assets.
     */
    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        if ($report->pdf_path) {
            Storage::disk('local')->delete($report->pdf_path);
        }

        $report->delete();

        return back()->with('success', 'Report deleted.');
    }

    /**
     * Build template metadata for the UI.
     *
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Performance Summary',
                'description' => 'Comprehensive performance, uptime, and SEO snapshot.',
                'category' => 'Performance',
                'frequency' => 'Weekly',
                'format' => 'PDF, CSV',
                'icon' => 'ChartBarIcon',
                'iconBg' => 'bg-blue-500/20',
                'iconColor' => 'text-blue-400',
                'categoryColor' => 'bg-blue-500/10 text-blue-400',
            ],
            [
                'id' => 2,
                'name' => 'Security Audit',
                'description' => 'SSL status, patch cadence, and incident overview.',
                'category' => 'Security',
                'frequency' => 'Monthly',
                'format' => 'PDF',
                'icon' => 'ShieldCheckIcon',
                'iconBg' => 'bg-green-500/20',
                'iconColor' => 'text-green-400',
                'categoryColor' => 'bg-green-500/10 text-green-400',
            ],
            [
                'id' => 3,
                'name' => 'Uptime Report',
                'description' => 'Downtime incidents and SLA performance.',
                'category' => 'Monitoring',
                'frequency' => 'Daily',
                'format' => 'PDF, CSV',
                'icon' => 'ClockIcon',
                'iconBg' => 'bg-purple-500/20',
                'iconColor' => 'text-purple-400',
                'categoryColor' => 'bg-purple-500/10 text-purple-400',
            ],
        ];
    }

    /**
     * Fetch recent reports for the table.
     *
     * @param Request $request
     * @return array<int, array<string, mixed>>
     */
    private function recentReports(Request $request): array
    {
        $user = $request->user();
        $reportsQuery = Report::with('site');
        
        if ($user->role !== 'admin') {
            $reportsQuery->whereHas('site.client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        
        return $reportsQuery->latest('generated_at')->limit(10)
            ->get()
            ->map(function (Report $report) {
                return [
                    'id' => $report->id,
                    'name' => $report->site?->name.' - '.$report->report_month->format('F Y'),
                    'type' => $report->site?->type ?? 'Site',
                    'period' => $report->report_month->format('M Y'),
                    'createdAt' => $report->generated_at?->toIso8601String(),
                    'downloadUrl' => route('reports.download', $report),
                ];
            })
            ->all();
    }
}


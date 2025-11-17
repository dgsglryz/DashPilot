<?php
declare(strict_types=1);

namespace App\Modules\Activity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Sites\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ActivityController displays recent activity logs across all sites.
 * Supports filtering, CSV export, and real-time updates.
 */
class ActivityController extends Controller
{
    /**
     * Display the activity feed with filters.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        // Authorization: Users can view activity for their assigned clients
        $user = $request->user();
        
        $query = $this->filteredActivityQuery($request, ['site:id,name,url,thumbnail_url', 'user:id,name,email']);
        
        // Scope to user's assigned clients (admin sees all)
        if ($user->role !== 'admin') {
            $query->whereHas('site.client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }

        $activities = $query->paginate(20)->through(function (ActivityLog $log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'timestamp' => $log->created_at?->toIso8601String(),
                'site' => $log->site ? [
                    'id' => $log->site->id,
                    'name' => $log->site->name,
                    'url' => $log->site->url,
                    'thumbnail' => $log->site->thumbnail_url,
                ] : null,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                    'avatar' => $this->getUserAvatarUrl($log->user->email),
                ] : null,
            ];
        });

        // Scope stats to user's assigned clients
        $statsQuery = ActivityLog::query();
        if ($user->role !== 'admin') {
            $statsQuery->whereHas('site.client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'today' => (clone $statsQuery)->whereDate('created_at', today())->count(),
            'thisWeek' => (clone $statsQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'thisMonth' => (clone $statsQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        // Scope sites to user's assigned clients
        $sitesQuery = Site::query();
        if ($user->role !== 'admin') {
            $sitesQuery->whereHas('client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }
        $sites = $sitesQuery->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Activity/Pages/Index', [
            'activities' => $activities,
            'stats' => $stats,
            'sites' => $sites,
            'filters' => [
                'site_id' => $request->string('site_id')->toString(),
                'action' => $request->string('action')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
        ]);
    }

    /**
     * Export activity logs as CSV.
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        
        $query = $this->filteredActivityQuery($request, ['site:id,name,url', 'user:id,name,email']);
        
        // Scope to user's assigned clients (admin sees all)
        if ($user->role !== 'admin') {
            $query->whereHas('site.client', function ($q) use ($user) {
                $q->where('assigned_developer_id', $user->id);
            });
        }

        $filename = 'activity_logs_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, ['ID', 'Action', 'Description', 'Site', 'User', 'IP Address', 'Date']);

            $query->chunk(100, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->action,
                        $log->description,
                        $log->site?->name ?? 'N/A',
                        $log->user?->name ?? 'System',
                        $log->ip_address ?? 'N/A',
                        $log->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Get user avatar URL using DiceBear API or initials.
     *
     * @param string $email
     *
     * @return string
     */
    private function getUserAvatarUrl(string $email): string
    {
        // Use DiceBear Avataaars for consistent avatars
        // Using SHA256 instead of MD5 for better security (even though this is non-sensitive)
        $seed = hash('sha256', $email);
        return 'https://api.dicebear.com/7.x/avataaars/svg?seed='.$seed.'&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf';
    }

    /**
     * Apply common filters and eager loading for activity queries.
     *
     * @param Request $request
     * @param array<int, string> $relations
     *
     * @return Builder
     */
    private function filteredActivityQuery(Request $request, array $relations): Builder
    {
        $query = ActivityLog::with($relations)->latest('created_at');

        return $this->applyActivityFilters($query, $request);
    }

    /**
     * Apply filter clauses shared between index/export endpoints.
     *
     * @param Builder $query
     * @param Request $request
     *
     * @return Builder
     */
    private function applyActivityFilters(Builder $query, Request $request): Builder
    {
        if ($request->has('site_id') && $request->string('site_id')->isNotEmpty()) {
            $query->where('site_id', $request->integer('site_id'));
        }

        if ($request->has('action') && $request->string('action')->isNotEmpty()) {
            $query->where('action', 'like', '%'.$request->string('action').'%');
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        return $query;
    }
}



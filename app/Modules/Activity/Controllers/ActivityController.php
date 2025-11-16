<?php
declare(strict_types=1);

namespace App\Modules\Activity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Sites\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $query = ActivityLog::with(['site:id,name,url,thumbnail_url', 'user:id,name,email'])
            ->latest('created_at');

        // Filter by site if provided
        if ($request->has('site_id') && $request->string('site_id')->isNotEmpty()) {
            $query->where('site_id', $request->integer('site_id'));
        }

        // Filter by action type if provided
        if ($request->has('action') && $request->string('action')->isNotEmpty()) {
            $query->where('action', 'like', '%'.$request->string('action').'%');
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        $activities = $query->paginate(50)->through(function (ActivityLog $log) {
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
                    'avatar' => $this->getUserAvatarUrl($log->user->email, $log->user->name),
                ] : null,
            ];
        });

        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'thisWeek' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'thisMonth' => ActivityLog::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        $sites = Site::orderBy('name')->get(['id', 'name']);

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
        $query = ActivityLog::with(['site:id,name,url', 'user:id,name,email'])
            ->latest('created_at');

        // Apply same filters as index
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
     * @param string $name
     *
     * @return string
     */
    private function getUserAvatarUrl(string $email, string $name): string
    {
        // Use DiceBear Avataaars for consistent avatars
        $seed = md5($email);
        return 'https://api.dicebear.com/7.x/avataaars/svg?seed='.$seed.'&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf';
    }
}



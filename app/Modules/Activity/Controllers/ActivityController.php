<?php
declare(strict_types=1);

namespace App\Modules\Activity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Sites\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ActivityController displays recent activity logs across all sites.
 */
class ActivityController extends Controller
{
    /**
     * Display the activity feed with filters.
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
            ],
        ]);
    }
}



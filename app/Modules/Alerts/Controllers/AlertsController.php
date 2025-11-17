<?php
declare(strict_types=1);

namespace App\Modules\Alerts\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alerts\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AlertsController exposes the alert inbox, acknowledgement, and resolve actions.
 */
class AlertsController extends Controller
{
    /**
     * Display the alert center.
     */
    public function index(Request $request): Response
    {
        $perPage = $request->integer('per_page', 20);
        $alerts = Alert::with(['site:id,name,url', 'resolver:id,name', 'acknowledger:id,name'])
            ->latest('created_at')
            ->paginate($perPage)
            ->through(function (Alert $alert) {
                return [
                    'id' => $alert->id,
                    'title' => $alert->title ?? Str::headline($alert->type),
                    'message' => $alert->message,
                    'severity' => $this->mapSeverity($alert->severity),
                    'status' => $alert->status ?? ($alert->is_resolved ? 'resolved' : 'active'),
                    'type' => $this->mapType($alert->type),
                    'siteId' => $alert->site_id,
                    'siteName' => $alert->site?->name ?? 'Unknown Site',
                    'isRead' => $alert->is_read,
                    'createdAt' => $alert->created_at?->toIso8601String(),
                ];
            });

        // Get stats from all alerts (not paginated)
        $allAlerts = Alert::with('site:id,name')->get();
        $stats = [
            'critical' => $allAlerts->where('severity', 'critical')->where('status', '!=', 'resolved')->count(),
            'warning' => $allAlerts->where('severity', 'warning')->where('status', '!=', 'resolved')->count(),
            'info' => $allAlerts->where('severity', 'info')->where('status', '!=', 'resolved')->count(),
            'resolved' => $allAlerts->where('status', 'resolved')->count(),
        ];

        return Inertia::render('Alerts/Pages/Index', [
            'alerts' => $alerts,
            'stats' => $stats,
        ]);
    }

    /**
     * Mark every unread alert as read.
     */
    public function markAllRead(): RedirectResponse
    {
        Alert::where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'All alerts marked as read.');
    }

    /**
     * Acknowledge an alert without resolving it.
     */
    public function acknowledge(Alert $alert): RedirectResponse
    {
        $alert->update([
            'status' => 'acknowledged',
            'is_read' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => Auth::id(),
        ]);

        return back()->with('success', 'Alert acknowledged.');
    }

    /**
     * Resolve an alert and mark it complete.
     */
    public function resolve(Alert $alert): RedirectResponse
    {
        $alert->update([
            'status' => 'resolved',
            'is_resolved' => true,
            'is_read' => true,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);

        return back()->with('success', 'Alert resolved.');
    }

    /**
     * Normalize severity buckets used by the UI.
     */
    private function mapSeverity(?string $severity): string
    {
        return match ($severity) {
            'critical', 'high' => 'critical',
            'medium' => 'warning',
            default => 'info',
        };
    }

    /**
     * Export alerts as CSV (last 30 days).
     *
     * @return StreamedResponse
     */
    public function export(): StreamedResponse
    {
        $query = Alert::with(['site:id,name', 'resolver:id,name', 'acknowledger:id,name'])
            ->where('created_at', '>=', now()->subDays(30))
            ->latest('created_at');

        $filename = 'alerts_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, ['ID', 'Type', 'Severity', 'Message', 'Site', 'Status', 'Created At', 'Resolved At']);

            $query->chunk(100, function ($alerts) use ($handle) {
                foreach ($alerts as $alert) {
                    fputcsv($handle, [
                        $alert->id,
                        $alert->type ?? 'N/A',
                        $alert->severity ?? 'N/A',
                        $alert->message,
                        $alert->site?->name ?? 'N/A',
                        $alert->is_resolved ? 'Resolved' : 'Active',
                        $alert->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                        $alert->resolved_at?->format('Y-m-d H:i:s') ?? 'N/A',
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
     * Normalize alert type labels.
     */
    private function mapType(?string $type): string
    {
        return match ($type) {
            'site_down', 'downtime' => 'downtime',
            'security' => 'security',
            'backup_failed', 'backup' => 'backup',
            'ssl_expiry', 'ssl' => 'ssl',
            'performance' => 'performance',
            default => 'general',
        };
    }
}


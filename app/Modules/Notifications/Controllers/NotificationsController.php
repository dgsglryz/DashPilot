<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alerts\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NotificationsController handles in-app notification display and management.
 * Uses alerts as the source of notifications.
 */
class NotificationsController extends Controller
{
    /**
     * Get unread notifications (alerts) for the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $notifications = Alert::with(['site:id,name,url'])
            ->forUser($user)
            ->where('is_read', false)
            ->where('is_resolved', false)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(function (Alert $alert) {
                return [
                    'id' => $alert->id,
                    'title' => $alert->title ?? Str::headline($alert->type),
                    'message' => $alert->message,
                    'severity' => $this->mapSeverity($alert->severity),
                    'type' => $this->mapType($alert->type),
                    'siteId' => $alert->site_id,
                    'siteName' => $alert->site?->name ?? 'Unknown Site',
                    'isRead' => $alert->is_read,
                    'createdAt' => $alert->created_at?->toIso8601String(),
                    'url' => route('sites.show', $alert->site_id),
                ];
            });

        $unreadCount = Alert::forUser($user)
            ->where('is_read', false)
            ->where('is_resolved', false)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification (alert) as read.
     *
     * @param Request $request
     * @param int $id Alert ID
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        $alert = Alert::forUser($user)->findOrFail($id);
        
        $alert->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        
        Alert::forUser($user)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Normalize severity buckets used by the UI.
     *
     * @param string|null $severity
     * @return string
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
     * Normalize alert type labels.
     *
     * @param string|null $type
     * @return string
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


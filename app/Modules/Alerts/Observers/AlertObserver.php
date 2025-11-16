<?php
declare(strict_types=1);

namespace App\Modules\Alerts\Observers;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Jobs\SendEmailNotification;
use App\Modules\Notifications\Services\WebhookService;
use App\Shared\Services\LoggingService;

/**
 * AlertObserver handles email and webhook notifications when alerts are created or resolved.
 */
class AlertObserver
{
    /**
     * Handle the Alert "created" event.
     */
    public function created(Alert $alert): void
    {
        $logger = app(LoggingService::class);
        $logger->logServiceMethod(AlertObserver::class, 'created', [
            'alert_id' => $alert->id,
            'severity' => $alert->severity,
            'site_id' => $alert->site_id,
        ]);

        // Only send email for critical/high severity alerts
        if (in_array($alert->severity, ['critical', 'high'], true)) {
            SendEmailNotification::dispatch('alert_created', $alert);
            $logger->logServiceMethod(AlertObserver::class, 'created', [
                'alert_id' => $alert->id,
                'severity' => $alert->severity,
                'action' => 'email_dispatched',
            ]);
        }

        // Trigger webhooks for all alert creations
        app(WebhookService::class)->triggerAlertEvent('alert_created', $alert);
    }

    /**
     * Handle the Alert "updated" event.
     */
    public function updated(Alert $alert): void
    {
        // Check if alert was just resolved
        if ($alert->is_resolved && $alert->wasChanged('is_resolved')) {
            $logger = app(LoggingService::class);
            $logger->logServiceMethod(AlertObserver::class, 'updated', [
                'alert_id' => $alert->id,
                'site_id' => $alert->site_id,
                'action' => 'alert_resolved',
            ]);

            SendEmailNotification::dispatch('alert_resolved', $alert);
            app(WebhookService::class)->triggerAlertEvent('alert_resolved', $alert);
        }
    }
}


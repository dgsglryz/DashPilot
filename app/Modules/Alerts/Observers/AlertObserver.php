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
        // Only send email for critical/high severity alerts
        if (in_array($alert->severity, ['critical', 'high'], true)) {
            SendEmailNotification::dispatch('alert_created', $alert);
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
            SendEmailNotification::dispatch('alert_resolved', $alert);
            app(WebhookService::class)->triggerAlertEvent('alert_resolved', $alert);
        }
    }
}


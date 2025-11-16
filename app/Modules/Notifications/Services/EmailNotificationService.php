<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Jobs\SendEmailNotification;
use App\Modules\Notifications\Mail\AlertCreated;
use App\Modules\Notifications\Mail\AlertResolved;
use App\Modules\Notifications\Mail\DailyDigest;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use App\Shared\Services\LoggingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * EmailNotificationService manages email notification delivery for alerts and digests.
 */
class EmailNotificationService
{
    /**
     * Send email notification for alert creation (only critical/high severity).
     *
     * @param Alert $alert The alert that was created
     * @return void
     */
    public function sendAlertCreated(Alert $alert): void
    {
        // Only send email for critical/high severity alerts
        if (!in_array($alert->severity, ['critical', 'high'], true)) {
            return;
        }

        SendEmailNotification::dispatch('alert_created', $alert);
    }

    /**
     * Send email notification for alert resolution (all severities).
     *
     * @param Alert $alert The alert that was resolved
     * @return void
     */
    public function sendAlertResolved(Alert $alert): void
    {
        SendEmailNotification::dispatch('alert_resolved', $alert);
    }

    /**
     * Send daily digest email to users who opted in.
     *
     * @param Collection<int, Alert> $alerts Alerts from the last 24 hours
     * @param Collection<int, Site> $sites Sites with recent activity
     * @return void
     */
    public function sendDailyDigest(Collection $alerts, Collection $sites): void
    {
        SendEmailNotification::dispatch('daily_digest', [$alerts, $sites]);
    }

    /**
     * Get users who have the specified notification preference enabled.
     *
     * @param string $preference Notification preference key (e.g., 'emailAlerts', 'emailReports')
     * @return Collection<int, User>
     */
    public function getUsersForNotification(string $preference): Collection
    {
        return User::where('status', 'active')
            ->get()
            ->filter(function (User $user) use ($preference) {
                $settings = $user->notification_settings ?? [];

                return ($settings[$preference] ?? true) === true;
            });
    }

    /**
     * Send test email to verify configuration.
     *
     * @param string $email Recipient email address
     * @param LoggingService $logger Logging service instance
     * @return void
     */
    public function sendTestEmail(string $email, LoggingService $logger): void
    {
        try {
            Mail::to($email)->send(new \App\Modules\Notifications\Mail\AlertCreated(
                Alert::factory()->make([
                    'title' => 'Test Alert',
                    'message' => 'This is a test email to verify your email configuration.',
                    'severity' => 'high',
                    'type' => 'performance',
                ])
            ));

            $logger->logEmailNotification(AlertCreated::class, $email, true, [
                'type' => 'test',
            ]);
        } catch (\Throwable $e) {
            $logger->logEmailNotification(AlertCreated::class, $email, false, [
                'type' => 'test',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}


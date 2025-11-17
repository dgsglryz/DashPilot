<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Mail\AlertCreated;
use App\Modules\Notifications\Mail\AlertResolved;
use App\Modules\Notifications\Mail\DailyDigest;
use App\Modules\Users\Models\User;
use App\Shared\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * SendEmailNotification job queues email notifications for alerts and digests.
 */
class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param string $type Notification type: 'alert_created', 'alert_resolved', 'daily_digest'
     * @param mixed $data Data to pass to the mailable
     */
    public function __construct(
        private readonly string $type,
        private readonly mixed $data
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(LoggingService $logger): void
    {
        $logger->logJob(SendEmailNotification::class, [
            'type' => $this->type,
        ], 'started');

        try {
            match ($this->type) {
                'alert_created' => $this->sendAlertCreated($logger),
                'alert_resolved' => $this->sendAlertResolved($logger),
                'daily_digest' => $this->sendDailyDigest($logger),
                default => throw new \InvalidArgumentException("Unknown notification type: {$this->type}"),
            };

            $logger->logJob(SendEmailNotification::class, [
                'type' => $this->type,
            ], 'completed');
        } catch (\Throwable $e) {
            $logger->logJob(SendEmailNotification::class, [
                'type' => $this->type,
                'error' => $e->getMessage(),
            ], 'failed');

            throw $e;
        }
    }

    /**
     * Send alert created notification (only for critical/high severity).
     */
    private function sendAlertCreated(LoggingService $logger): void
    {
        $alert = $this->data;

        // Only send for critical/high severity
        if (!in_array($alert->severity, ['critical', 'high'], true)) {
            return;
        }

        // Get users who want email alerts
        $users = $this->getUsersForNotification('emailAlerts');

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new AlertCreated($alert));
                $logger->logEmailNotification(AlertCreated::class, $user->email, true, [
                    'alert_id' => $alert->id,
                ]);
            } catch (\Throwable $e) {
                $logger->logEmailNotification(AlertCreated::class, $user->email, false, [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send alert resolved notification (all severities).
     */
    private function sendAlertResolved(LoggingService $logger): void
    {
        $alert = $this->data;

        // Get users who want email alerts
        $users = $this->getUsersForNotification('emailAlerts');

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new AlertResolved($alert));
                $logger->logEmailNotification(AlertResolved::class, $user->email, true, [
                    'alert_id' => $alert->id,
                ]);
            } catch (\Throwable $e) {
                $logger->logEmailNotification(AlertResolved::class, $user->email, false, [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send daily digest to users who opted in.
     */
    private function sendDailyDigest(LoggingService $logger): void
    {
        [$alerts, $sites] = $this->data;

        // Get users who want daily digests
        $users = $this->getUsersForNotification('emailReports');

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new DailyDigest($user, $alerts, $sites));
                $logger->logEmailNotification(DailyDigest::class, $user->email, true);
            } catch (\Throwable $e) {
                $logger->logEmailNotification(DailyDigest::class, $user->email, false, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get users who have the specified notification preference enabled.
     *
     * @param string $preference Notification preference key
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function getUsersForNotification(string $preference): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('status', 'active')
            ->get()
            ->filter(function (User $user) use ($preference) {
                $settings = $user->notification_settings ?? [];

                return ($settings[$preference] ?? true) === true;
            });
    }
}


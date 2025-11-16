<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Mail\AlertCreated;
use App\Modules\Notifications\Mail\AlertResolved;
use App\Modules\Notifications\Mail\DailyDigest;
use App\Modules\Users\Models\User;
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
     * @param User|null $user Target user (optional, uses all users for digest)
     */
    public function __construct(
        private readonly string $type,
        private readonly mixed $data,
        private ?User $user = null
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        match ($this->type) {
            'alert_created' => $this->sendAlertCreated(),
            'alert_resolved' => $this->sendAlertResolved(),
            'daily_digest' => $this->sendDailyDigest(),
            default => throw new \InvalidArgumentException("Unknown notification type: {$this->type}"),
        };
    }

    /**
     * Send alert created notification (only for critical/high severity).
     */
    private function sendAlertCreated(): void
    {
        $alert = $this->data;

        // Only send for critical/high severity
        if (!in_array($alert->severity, ['critical', 'high'], true)) {
            return;
        }

        // Get users who want email alerts
        $users = $this->getUsersForNotification('emailAlerts');

        foreach ($users as $user) {
            Mail::to($user->email)->send(new AlertCreated($alert));
        }
    }

    /**
     * Send alert resolved notification (all severities).
     */
    private function sendAlertResolved(): void
    {
        $alert = $this->data;

        // Get users who want email alerts
        $users = $this->getUsersForNotification('emailAlerts');

        foreach ($users as $user) {
            Mail::to($user->email)->send(new AlertResolved($alert));
        }
    }

    /**
     * Send daily digest to users who opted in.
     */
    private function sendDailyDigest(): void
    {
        [$alerts, $sites] = $this->data;

        // Get users who want daily digests
        $users = $this->getUsersForNotification('emailReports');

        foreach ($users as $user) {
            Mail::to($user->email)->send(new DailyDigest($user, $alerts, $sites));
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


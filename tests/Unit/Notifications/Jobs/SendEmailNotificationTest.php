<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Jobs;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Jobs\SendEmailNotification;
use App\Modules\Notifications\Mail\AlertCreated;
use App\Modules\Notifications\Mail\AlertResolved;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use App\Shared\Services\LoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_alert_created_only_for_critical_high_severity(): void
    {
        Mail::fake();

        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'critical',
        ]);

        $job = new SendEmailNotification('alert_created', $alert);
        $job->handle(app(LoggingService::class));

        Mail::assertSent(AlertCreated::class);
    }

    public function test_send_alert_created_skips_low_severity(): void
    {
        Mail::fake();

        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'low',
        ]);

        $job = new SendEmailNotification('alert_created', $alert);
        $job->handle(app(LoggingService::class));

        Mail::assertNothingSent();
    }

    public function test_send_alert_resolved_for_all_severities(): void
    {
        Mail::fake();

        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'low',
            'is_resolved' => true,
        ]);

        $job = new SendEmailNotification('alert_resolved', $alert);
        $job->handle(app(LoggingService::class));

        Mail::assertSent(AlertResolved::class);
    }

    public function test_send_email_respects_user_notification_preferences(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'notification_settings' => ['emailAlerts' => false],
        ]);

        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'critical',
        ]);

        $job = new SendEmailNotification('alert_created', $alert);
        $job->handle(app(LoggingService::class));

        Mail::assertNotSent(AlertCreated::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}

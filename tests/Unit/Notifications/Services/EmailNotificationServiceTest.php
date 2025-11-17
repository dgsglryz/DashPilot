<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Services;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Jobs\SendEmailNotification;
use App\Modules\Notifications\Services\EmailNotificationService;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_alert_created_dispatches_job_for_critical_alerts(): void
    {
        Queue::fake();
        $service = new EmailNotificationService();
        $alert = Alert::factory()->create(['severity' => 'critical']);

        $service->sendAlertCreated($alert);

        Queue::assertPushed(SendEmailNotification::class);
    }

    public function test_send_alert_created_dispatches_job_for_high_alerts(): void
    {
        Queue::fake();
        $service = new EmailNotificationService();
        $alert = Alert::factory()->create(['severity' => 'high']);

        $service->sendAlertCreated($alert);

        Queue::assertPushed(SendEmailNotification::class);
    }

    public function test_send_alert_created_skips_low_severity(): void
    {
        Queue::fake();
        $service = new EmailNotificationService();
        $alert = Alert::factory()->create(['severity' => 'low']);

        $service->sendAlertCreated($alert);

        Queue::assertNothingPushed();
    }

    public function test_send_alert_resolved_dispatches_job(): void
    {
        Queue::fake();
        $service = new EmailNotificationService();
        $alert = Alert::factory()->create(['severity' => 'low']);

        $service->sendAlertResolved($alert);

        Queue::assertPushed(SendEmailNotification::class);
    }

    public function test_send_daily_digest_dispatches_job(): void
    {
        Queue::fake();
        $service = new EmailNotificationService();
        $alerts = Alert::factory()->count(3)->create();
        $sites = Site::factory()->count(2)->create();

        $service->sendDailyDigest($alerts, $sites);

        Queue::assertPushed(SendEmailNotification::class);
    }

    public function test_get_users_for_notification_returns_active_users(): void
    {
        $service = new EmailNotificationService();
        User::factory()->create(['status' => 'active', 'notification_settings' => ['emailAlerts' => true]]);
        User::factory()->create(['status' => 'inactive']);

        $users = $service->getUsersForNotification('emailAlerts');

        $this->assertCount(1, $users);
    }
}


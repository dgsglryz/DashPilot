<?php
declare(strict_types=1);

namespace Tests\Unit\Alerts\Observers;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Alerts\Observers\AlertObserver;
use App\Modules\Notifications\Jobs\DeliverWebhook;
use App\Modules\Notifications\Jobs\SendEmailNotification;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AlertObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_dispatches_email_for_critical_alerts(): void
    {
        Queue::fake();

        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'critical',
        ]);

        $observer = new AlertObserver();
        $observer->created($alert);

        Queue::assertPushed(SendEmailNotification::class);
    }

    public function test_created_dispatches_webhook_for_all_alerts(): void
    {
        Queue::fake();

        $user = \App\Modules\Users\Models\User::factory()->create();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'low',
        ]);

        // Create an active webhook for the event
        \App\Modules\Notifications\Models\Webhook::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'events' => ['alert_created'],
        ]);

        $observer = new AlertObserver();
        $observer->created($alert);

        Queue::assertPushed(DeliverWebhook::class);
    }

    public function test_updated_dispatches_notifications_when_resolved(): void
    {
        Queue::fake();

        $user = \App\Modules\Users\Models\User::factory()->create();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'is_resolved' => false,
        ]);

        // Create an active webhook for the event
        \App\Modules\Notifications\Models\Webhook::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'events' => ['alert_resolved'],
        ]);

        $alert->is_resolved = true;
        $alert->save();

        $observer = new AlertObserver();
        $observer->updated($alert);

        Queue::assertPushed(SendEmailNotification::class);
        Queue::assertPushed(DeliverWebhook::class);
    }
}

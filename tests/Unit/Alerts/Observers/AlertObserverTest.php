<?php
declare(strict_types=1);

namespace Tests\Unit\Alerts\Observers;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Alerts\Observers\AlertObserver;
use App\Modules\Notifications\Jobs\SendEmailNotification;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AlertObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_created_dispatches_email_for_critical_alerts(): void
    {
        Queue::fake();
        $observer = new AlertObserver();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'critical',
        ]);

        $observer->created($alert);

        Queue::assertPushed(SendEmailNotification::class);
    }

    public function test_alert_created_dispatches_email_for_high_alerts(): void
    {
        Queue::fake();
        $observer = new AlertObserver();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'high',
        ]);

        $observer->created($alert);

        Queue::assertPushed(SendEmailNotification::class);
    }

    public function test_alert_created_skips_email_for_low_severity(): void
    {
        Queue::fake();
        $observer = new AlertObserver();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'low',
        ]);

        $observer->created($alert);

        Queue::assertNotPushed(SendEmailNotification::class);
    }

    public function test_alert_updated_dispatches_email_when_resolved(): void
    {
        Queue::fake();
        $observer = new AlertObserver();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'is_resolved' => false,
        ]);

        $alert->is_resolved = true;
        $alert->syncChanges();

        $observer->updated($alert);

        Queue::assertPushed(SendEmailNotification::class);
    }
}

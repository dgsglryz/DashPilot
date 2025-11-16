<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Services;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Jobs\DeliverWebhook;
use App\Modules\Notifications\Models\Webhook;
use App\Modules\Notifications\Services\WebhookService;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trigger_alert_event_dispatches_webhook_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'critical',
        ]);

        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'events' => ['alert_created'],
        ]);

        $service = new WebhookService();
        $service->triggerAlertEvent('alert_created', $alert);

        Queue::assertPushed(DeliverWebhook::class);
    }

    public function test_trigger_alert_event_ignores_inactive_webhooks(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create(['site_id' => $site->id]);

        Webhook::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
            'events' => ['alert_created'],
        ]);

        $service = new WebhookService();
        $service->triggerAlertEvent('alert_created', $alert);

        // Only check that DeliverWebhook was not pushed (AlertObserver may push SendEmailNotification)
        Queue::assertNotPushed(DeliverWebhook::class);
    }

    public function test_trigger_alert_event_filters_by_event_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create(['site_id' => $site->id]);

        Webhook::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'events' => ['alert_resolved'], // Different event
        ]);

        $service = new WebhookService();
        $service->triggerAlertEvent('alert_created', $alert);

        Queue::assertNothingPushed();
    }

    public function test_generate_signature_creates_hmac_sha256(): void
    {
        $service = new WebhookService();
        $payload = ['test' => 'data'];
        $secret = 'test-secret';

        $signature = $service->generateSignature($payload, $secret);

        $this->assertNotEmpty($signature);
        $this->assertEquals(64, strlen($signature)); // SHA256 hex length
        $this->assertNotEquals(
            $service->generateSignature($payload, 'different-secret'),
            $signature
        );
    }
}

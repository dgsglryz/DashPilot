<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Services;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Jobs\DeliverWebhook;
use App\Modules\Notifications\Models\Webhook;
use App\Modules\Notifications\Services\WebhookService;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trigger_alert_event_dispatches_webhook_job(): void
    {
        Queue::fake();
        $service = new WebhookService();
        $site = Site::factory()->create();
        $alert = Alert::factory()->create(['site_id' => $site->id]);
        $webhook = Webhook::factory()->create([
            'is_active' => true,
            'events' => ['alert_created'],
        ]);

        $service->triggerAlertEvent('alert_created', $alert);

        Queue::assertPushed(DeliverWebhook::class);
    }

    public function test_trigger_alert_event_filters_by_event_type(): void
    {
        Queue::fake();
        $service = new WebhookService();
        $alert = Alert::factory()->create();
        Webhook::factory()->create([
            'is_active' => true,
            'events' => ['alert_created'],
        ]);
        Webhook::factory()->create([
            'is_active' => true,
            'events' => ['alert_resolved'],
        ]);

        $service->triggerAlertEvent('alert_created', $alert);

        Queue::assertPushed(DeliverWebhook::class, 1);
    }

    public function test_trigger_alert_event_respects_wildcard_events(): void
    {
        Queue::fake();
        $service = new WebhookService();
        $alert = Alert::factory()->create();
        Webhook::factory()->create([
            'is_active' => true,
            'events' => ['*'],
        ]);

        $service->triggerAlertEvent('alert_created', $alert);

        Queue::assertPushed(DeliverWebhook::class);
    }

    public function test_generate_signature_creates_hmac_sha256(): void
    {
        $service = new WebhookService();
        $payload = ['test' => 'data'];
        $secret = 'test-secret';

        $signature = $service->generateSignature($payload, $secret);

        $this->assertIsString($signature);
        $this->assertEquals(64, strlen($signature)); // SHA256 hex length
    }

    public function test_generate_signature_is_deterministic(): void
    {
        $service = new WebhookService();
        $payload = ['test' => 'data'];
        $secret = 'test-secret';

        $signature1 = $service->generateSignature($payload, $secret);
        $signature2 = $service->generateSignature($payload, $secret);

        $this->assertEquals($signature1, $signature2);
    }
}

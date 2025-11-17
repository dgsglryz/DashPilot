<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Jobs;

use App\Modules\Notifications\Jobs\DeliverWebhook;
use App\Modules\Notifications\Models\Webhook;
use App\Modules\Notifications\Models\WebhookLog;
use App\Modules\Notifications\Services\WebhookService;
use App\Shared\Services\LoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unit tests for DeliverWebhook job.
 */
class DeliverWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test backoff returns correct delays.
     */
    public function test_backoff_returns_correct_delays(): void
    {
        $webhook = Webhook::factory()->create();
        $job = new DeliverWebhook($webhook, 'test_event', ['data' => 'test']);

        $backoff = $job->backoff();

        $this->assertEquals([60, 300, 900], $backoff);
    }

    /**
     * Test job is queued on webhooks queue.
     */
    public function test_job_is_queued_on_webhooks_queue(): void
    {
        $webhook = Webhook::factory()->create();
        $job = new DeliverWebhook($webhook, 'test_event', ['data' => 'test']);

        $this->assertEquals('webhooks', $job->queue);
    }

    /**
     * Test job handles successful webhook delivery.
     */
    public function test_handle_delivers_webhook_successfully(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret' => null,
        ]);

        $job = new DeliverWebhook($webhook, 'test_event', ['data' => 'test']);
        $webhookService = app(WebhookService::class);
        $logger = app(LoggingService::class);

        $job->handle($webhookService, $logger);

        Http::assertSent(function ($request) use ($webhook) {
            return $request->url() === $webhook->url
                && $request->method() === 'POST';
        });

        $this->assertDatabaseHas('webhook_logs', [
            'webhook_id' => $webhook->id,
            'event_type' => 'test_event',
            'success' => true,
        ]);

        $webhook->refresh();
        $this->assertNotNull($webhook->last_triggered_at);
    }

    /**
     * Test job adds signature when secret is configured.
     */
    public function test_handle_adds_signature_when_secret_configured(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret' => 'test-secret',
        ]);

        $job = new DeliverWebhook($webhook, 'test_event', ['data' => 'test']);
        $webhookService = app(WebhookService::class);
        $logger = app(LoggingService::class);

        $job->handle($webhookService, $logger);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['signature']);
        });
    }

    /**
     * Test job throws exception on failed delivery.
     */
    public function test_handle_throws_exception_on_failed_delivery(): void
    {
        $this->expectException(\App\Modules\Notifications\Exceptions\WebhookDeliveryException::class);

        Http::fake([
            '*' => Http::response(['error' => 'Failed'], 500),
        ]);

        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
        ]);

        $job = new DeliverWebhook($webhook, 'test_event', ['data' => 'test']);
        $webhookService = app(WebhookService::class);
        $logger = app(LoggingService::class);

        $job->handle($webhookService, $logger);
    }

    /**
     * Test job logs failed attempt.
     */
    public function test_handle_logs_failed_attempt(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Failed'], 500),
        ]);

        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
        ]);

        $job = new DeliverWebhook($webhook, 'test_event', ['data' => 'test']);
        $webhookService = app(WebhookService::class);
        $logger = app(LoggingService::class);

        try {
            $job->handle($webhookService, $logger);
        } catch (\Exception $e) {
            // Expected to throw
        }

        $this->assertDatabaseHas('webhook_logs', [
            'webhook_id' => $webhook->id,
            'event_type' => 'test_event',
            'success' => false,
        ]);
    }

    /**
     * Test job has correct number of tries.
     */
    public function test_job_has_correct_tries(): void
    {
        $webhook = Webhook::factory()->create();
        $job = new DeliverWebhook($webhook, 'test_event', ['data' => 'test']);

        $this->assertEquals(3, $job->tries);
    }
}

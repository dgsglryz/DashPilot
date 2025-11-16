<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Jobs;

use App\Modules\Notifications\Jobs\DeliverWebhook;
use App\Modules\Notifications\Models\Webhook;
use App\Modules\Notifications\Models\WebhookLog;
use App\Modules\Notifications\Services\WebhookService;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliverWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_deliver_webhook_sends_request(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
        ]);

        $payload = ['event' => 'test'];
        $job = new DeliverWebhook($webhook, 'test_event', $payload);
        $job->handle(app(WebhookService::class), app(\App\Shared\Services\LoggingService::class));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook' &&
                   $request->method() === 'POST';
        });

        $this->assertDatabaseHas('webhook_logs', [
            'webhook_id' => $webhook->id,
            'success' => true,
        ]);
    }

    public function test_deliver_webhook_adds_signature_when_secret_exists(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'test-secret',
        ]);

        $payload = ['event' => 'test'];
        $job = new DeliverWebhook($webhook, 'test_event', $payload);
        $job->handle(app(WebhookService::class), app(\App\Shared\Services\LoggingService::class));

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['signature']);
        });
    }

    public function test_deliver_webhook_logs_failure(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['error' => 'Failed'], 500),
        ]);

        $user = User::factory()->create();
        $webhook = Webhook::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
        ]);

        $payload = ['event' => 'test'];
        $job = new DeliverWebhook($webhook, 'test_event', $payload);

        try {
            $job->handle(app(WebhookService::class), app(\App\Shared\Services\LoggingService::class));
        } catch (\Exception $e) {
            // Expected to throw
        }

        $this->assertDatabaseHas('webhook_logs', [
            'webhook_id' => $webhook->id,
            'success' => false,
        ]);
    }
}

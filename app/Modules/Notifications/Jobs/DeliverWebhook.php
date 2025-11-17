<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Exceptions\WebhookDeliveryException;
use App\Modules\Notifications\Models\Webhook;
use App\Modules\Notifications\Models\WebhookLog;
use App\Modules\Notifications\Services\WebhookService;
use App\Shared\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeliverWebhook job delivers webhook payloads with retry logic and logging.
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     * Retry delays: 1min, 5min, 15min
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * Create a new job instance.
     *
     * @param Webhook $webhook The webhook configuration
     * @param string $eventType Event type that triggered this webhook
     * @param array<string, mixed> $payload The payload to deliver
     */
    public function __construct(
        private readonly Webhook $webhook,
        private readonly string $eventType,
        private readonly array $payload
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService, LoggingService $logger): void
    {
        $attemptNumber = $this->attempts();

        $logger->logJob(DeliverWebhook::class, [
            'webhook_id' => $this->webhook->id,
            'url' => $this->webhook->url,
            'event_type' => $this->eventType,
            'attempt' => $attemptNumber,
        ], 'started');

        try {
            $payload = $this->payload;

            // Add signature if secret is configured
            if ($this->webhook->secret) {
                $signature = $webhookService->generateSignature($payload, $this->webhook->secret);
                $payload['signature'] = $signature;
            }

            // Send webhook with 10 second timeout
            $startTime = microtime(true);
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'DashPilot/1.0',
                ])
                ->post($this->webhook->url, $payload);

            $duration = (microtime(true) - $startTime) * 1000;
            $success = $response->ok();
            $statusCode = $response->status();
            $responseBody = $response->body();

            // Log the attempt
            $this->logAttempt($attemptNumber, $success, $statusCode, $responseBody);

            // Log via LoggingService
            $logger->logWebhookDelivery(
                $this->webhook->id,
                $this->webhook->url,
                $this->eventType,
                $success,
                [
                    'status_code' => $statusCode,
                    'duration_ms' => round($duration, 2),
                    'attempt' => $attemptNumber,
                ]
            );

            // Update webhook last triggered timestamp
            $this->webhook->update([
                'last_triggered_at' => now(),
            ]);

            if (!$success) {
                throw new WebhookDeliveryException(
                    "Webhook delivery failed with status {$statusCode}: {$responseBody}"
                );
            }

            $logger->logJob(DeliverWebhook::class, [
                'webhook_id' => $this->webhook->id,
                'success' => true,
            ], 'completed');
        } catch (\Exception $e) {
            $this->logAttempt($attemptNumber, false, 0, $e->getMessage());

            $logger->logWebhookDelivery(
                $this->webhook->id,
                $this->webhook->url,
                $this->eventType,
                false,
                [
                    'error' => $e->getMessage(),
                    'attempt' => $attemptNumber,
                ]
            );

            $logger->logJob(DeliverWebhook::class, [
                'webhook_id' => $this->webhook->id,
                'error' => $e->getMessage(),
            ], 'failed');

            throw $e;
        }
    }

    /**
     * Log webhook delivery attempt.
     *
     * @param int $attemptNumber Attempt number (1, 2, 3)
     * @param bool $success Whether the delivery was successful
     * @param int $statusCode HTTP status code
     * @param string $responseBody Response body or error message
     */
    private function logAttempt(int $attemptNumber, bool $success, int $statusCode, string $responseBody): void
    {
        WebhookLog::create([
            'webhook_id' => $this->webhook->id,
            'event_type' => $this->eventType,
            'payload' => $this->payload,
            'response_status' => $statusCode,
            'response_body' => substr($responseBody, 0, 1000), // Limit to 1000 chars
            'attempt_number' => $attemptNumber,
            'success' => $success,
            'error_message' => $success ? null : $responseBody,
        ]);
    }
}


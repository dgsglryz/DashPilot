<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Jobs\DeliverWebhook;
use App\Modules\Notifications\Models\Webhook;
use Illuminate\Support\Collection;

/**
 * WebhookService manages webhook delivery for alerts and other events.
 */
class WebhookService
{
    /**
     * Trigger webhooks for an alert event.
     *
     * @param string $eventType Event type: 'alert_created', 'alert_resolved'
     * @param Alert $alert The alert that triggered the event
     * @return void
     */
    public function triggerAlertEvent(string $eventType, Alert $alert): void
    {
        $webhooks = $this->getActiveWebhooksForEvent($eventType);

        foreach ($webhooks as $webhook) {
            $payload = $this->buildAlertPayload($eventType, $alert, $webhook);

            DeliverWebhook::dispatch($webhook, $eventType, $payload);
        }
    }

    /**
     * Get active webhooks that listen to the specified event.
     *
     * @param string $eventType Event type to filter by
     * @return Collection<int, Webhook>
     */
    private function getActiveWebhooksForEvent(string $eventType): Collection
    {
        return Webhook::where('is_active', true)
            ->get()
            ->filter(function (Webhook $webhook) use ($eventType) {
                $events = $webhook->events ?? [];

                return in_array($eventType, $events, true) || in_array('*', $events, true);
            });
    }

    /**
     * Build webhook payload based on event type and webhook type (Slack/Discord/custom).
     *
     * @param string $eventType Event type
     * @param Alert $alert The alert
     * @param Webhook $webhook The webhook configuration
     * @return array<string, mixed>
     */
    private function buildAlertPayload(string $eventType, Alert $alert, Webhook $webhook): array
    {
        $url = $webhook->url;
        $isSlack = str_contains($url, 'hooks.slack.com');
        $isDiscord = str_contains($url, 'discord.com') || str_contains($url, 'discordapp.com');

        if ($isSlack) {
            return $this->buildSlackPayload($eventType, $alert);
        }

        if ($isDiscord) {
            return $this->buildDiscordPayload($eventType, $alert);
        }

        // Generic JSON payload for custom endpoints
        return $this->buildGenericPayload($eventType, $alert);
    }

    /**
     * Build Slack-formatted payload.
     *
     * @param string $eventType Event type
     * @param Alert $alert The alert
     * @return array<string, mixed>
     */
    private function buildSlackPayload(string $eventType, Alert $alert): array
    {
        $color = match ($alert->severity) {
            'critical' => '#dc2626',
            'high' => '#f59e0b',
            'medium' => '#eab308',
            default => '#6b7280',
        };

        $emoji = $eventType === 'alert_resolved' ? '✅' : '🚨';
        $siteName = $alert->site?->name ?? 'Unknown Site';

        return [
            'text' => "{$emoji} Alert: {$siteName}",
            'attachments' => [
                [
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => 'Type',
                            'value' => $alert->type ?? 'General',
                            'short' => true,
                        ],
                        [
                            'title' => 'Severity',
                            'value' => ucfirst($alert->severity ?? 'low'),
                            'short' => true,
                        ],
                        [
                            'title' => 'Message',
                            'value' => $alert->message,
                            'short' => false,
                        ],
                        [
                            'title' => 'Site',
                            'value' => $alert->site?->url ?? 'N/A',
                            'short' => false,
                        ],
                    ],
                    'ts' => $alert->created_at?->timestamp ?? time(),
                ],
            ],
        ];
    }

    /**
     * Build Discord embed-formatted payload.
     *
     * @param string $eventType Event type
     * @param Alert $alert The alert
     * @return array<string, mixed>
     */
    private function buildDiscordPayload(string $eventType, Alert $alert): array
    {
        $color = match ($alert->severity) {
            'critical' => 15158332, // Red
            'high' => 16776960,      // Yellow
            'medium' => 16776960,    // Yellow
            default => 9807270,      // Gray
        };

        $siteName = $alert->site?->name ?? 'Unknown Site';
        $title = $eventType === 'alert_resolved'
            ? "✅ Alert Resolved: {$siteName}"
            : "🚨 Alert: {$siteName}";

        return [
            'embeds' => [
                [
                    'title' => $title,
                    'description' => $alert->message,
                    'color' => $color,
                    'fields' => [
                        [
                            'name' => 'Type',
                            'value' => $alert->type ?? 'General',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Severity',
                            'value' => ucfirst($alert->severity ?? 'low'),
                            'inline' => true,
                        ],
                        [
                            'name' => 'Site URL',
                            'value' => $alert->site?->url ?? 'N/A',
                            'inline' => false,
                        ],
                    ],
                    'timestamp' => $alert->created_at?->toIso8601String() ?? now()->toIso8601String(),
                ],
            ],
        ];
    }

    /**
     * Build generic JSON payload for custom endpoints.
     *
     * @param string $eventType Event type
     * @param Alert $alert The alert
     * @return array<string, mixed>
     */
    private function buildGenericPayload(string $eventType, Alert $alert): array
    {
        return [
            'event' => $eventType,
            'timestamp' => now()->toIso8601String(),
            'alert' => [
                'id' => $alert->id,
                'title' => $alert->title,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'message' => $alert->message,
                'status' => $alert->status,
                'is_resolved' => $alert->is_resolved,
                'created_at' => $alert->created_at?->toIso8601String(),
                'resolved_at' => $alert->resolved_at?->toIso8601String(),
            ],
            'site' => [
                'id' => $alert->site?->id,
                'name' => $alert->site?->name,
                'url' => $alert->site?->url,
            ],
        ];
    }

    /**
     * Generate HMAC-SHA256 signature for webhook payload.
     *
     * @param array<string, mixed> $payload The payload to sign
     * @param string $secret The secret key
     * @return string The signature
     */
    public function generateSignature(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $json, $secret);
    }
}


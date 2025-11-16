<?php
declare(strict_types=1);

namespace App\Shared\Services;

use Illuminate\Support\Facades\Log;

/**
 * LoggingService provides structured logging for debugging and monitoring.
 */
class LoggingService
{
    /**
     * Log API request with context.
     *
     * @param string $service Service name (e.g., 'WordPress', 'Shopify')
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $context Additional context
     */
    public function logApiRequest(string $service, string $endpoint, array $context = []): void
    {
        Log::info("API Request: {$service}", [
            'service' => $service,
            'endpoint' => $endpoint,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log API response with context.
     *
     * @param string $service Service name
     * @param string $endpoint API endpoint
     * @param int $statusCode HTTP status code
     * @param array<string, mixed> $context Additional context
     */
    public function logApiResponse(string $service, string $endpoint, int $statusCode, array $context = []): void
    {
        $level = $statusCode >= 400 ? 'error' : 'info';

        Log::{$level}("API Response: {$service}", [
            'service' => $service,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log job execution.
     *
     * @param string $jobClass Job class name
     * @param array<string, mixed> $payload Job payload
     * @param string $status Job status (started, completed, failed)
     */
    public function logJob(string $jobClass, array $payload, string $status = 'started'): void
    {
        $level = match ($status) {
            'failed' => 'error',
            'completed' => 'info',
            default => 'debug',
        };

        Log::{$level}("Job {$status}: {$jobClass}", [
            'job' => $jobClass,
            'status' => $status,
            'payload' => $payload,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log service method execution.
     *
     * @param string $service Service class name
     * @param string $method Method name
     * @param array<string, mixed> $context Additional context
     */
    public function logServiceMethod(string $service, string $method, array $context = []): void
    {
        Log::debug("Service Method: {$service}::{$method}", [
            'service' => $service,
            'method' => $method,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log controller action.
     *
     * @param string $controller Controller class name
     * @param string $action Action method name
     * @param array<string, mixed> $context Additional context
     */
    public function logControllerAction(string $controller, string $action, array $context = []): void
    {
        Log::info("Controller Action: {$controller}::{$action}", [
            'controller' => $controller,
            'action' => $action,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log exception with full context.
     *
     * @param \Throwable $exception The exception
     * @param array<string, mixed> $context Additional context
     */
    public function logException(\Throwable $exception, array $context = []): void
    {
        Log::error("Exception: {$exception->getMessage()}", [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log webhook delivery attempt.
     *
     * @param int $webhookId Webhook ID
     * @param string $url Webhook URL
     * @param string $eventType Event type
     * @param bool $success Whether delivery was successful
     * @param array<string, mixed> $context Additional context
     */
    public function logWebhookDelivery(int $webhookId, string $url, string $eventType, bool $success, array $context = []): void
    {
        $level = $success ? 'info' : 'warning';

        Log::{$level}("Webhook Delivery: {$eventType}", [
            'webhook_id' => $webhookId,
            'url' => $url,
            'event_type' => $eventType,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log email notification.
     *
     * @param string $mailable Mailable class name
     * @param string $recipient Recipient email
     * @param bool $success Whether sending was successful
     * @param array<string, mixed> $context Additional context
     */
    public function logEmailNotification(string $mailable, string $recipient, bool $success, array $context = []): void
    {
        $level = $success ? 'info' : 'error';

        Log::{$level}("Email Notification: {$mailable}", [
            'mailable' => $mailable,
            'recipient' => $recipient,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log database query (for debugging slow queries).
     *
     * @param string $query SQL query
     * @param float $time Query execution time in milliseconds
     * @param array<string, mixed> $bindings Query bindings
     */
    public function logSlowQuery(string $query, float $time, array $bindings = []): void
    {
        if ($time > 100) { // Log queries slower than 100ms
            Log::warning("Slow Query Detected", [
                'query' => $query,
                'time_ms' => $time,
                'bindings' => $bindings,
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Log cache operation.
     *
     * @param string $operation Cache operation (hit, miss, set, forget)
     * @param string $key Cache key
     * @param array<string, mixed> $context Additional context
     */
    public function logCacheOperation(string $operation, string $key, array $context = []): void
    {
        Log::debug("Cache {$operation}: {$key}", [
            'operation' => $operation,
            'key' => $key,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }
}


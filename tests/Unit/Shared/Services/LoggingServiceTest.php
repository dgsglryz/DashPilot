<?php
declare(strict_types=1);

namespace Tests\Unit\Shared\Services;

use App\Shared\Services\LoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoggingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_api_request_logs_info(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/API Request/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logApiRequest('TestService', 'https://api.test.com/endpoint');
    }

    public function test_log_api_response_logs_info_for_success(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/API Response/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logApiResponse('TestService', 'https://api.test.com/endpoint', 200);
    }

    public function test_log_api_response_logs_error_for_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/API Response/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logApiResponse('TestService', 'https://api.test.com/endpoint', 500);
    }

    public function test_log_job_logs_with_correct_level(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Job failed/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logJob('TestJob', ['test' => 'data'], 'failed');
    }

    public function test_log_service_method_logs_debug(): void
    {
        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::pattern('/Service Method/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logServiceMethod('TestService', 'testMethod');
    }

    public function test_log_controller_action_logs_info(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Controller Action/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logControllerAction('TestController', 'index');
    }

    public function test_log_exception_logs_error(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Exception/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logException(new \Exception('Test exception'));
    }

    public function test_log_webhook_delivery_logs_info_for_success(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Webhook Delivery/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logWebhookDelivery(1, 'https://webhook.test.com', 'test_event', true);
    }

    public function test_log_email_notification_logs_info_for_success(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Email Notification/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logEmailNotification('TestMailable', 'test@example.com', true);
    }

    public function test_log_slow_query_logs_warning_for_slow_queries(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::pattern('/Slow Query/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logSlowQuery('SELECT * FROM users', 150.0);
    }

    public function test_log_cache_operation_logs_debug(): void
    {
        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::pattern('/Cache/'), \Mockery::type('array'));

        $service = new LoggingService();
        $service->logCacheOperation('hit', 'cache.key');
    }
}


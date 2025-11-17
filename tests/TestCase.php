<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Application;

/**
 * Base test case for all tests.
 * 
 * Automatically disables CSRF middleware and configures test environment.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Setup test environment.
     * Disables CSRF middleware for all tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF middleware for tests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // Ensure cache uses array driver in tests (no Redis extension needed)
        config(['cache.default' => 'array']);
        config(['queue.default' => 'sync']);
        config(['session.driver' => 'array']);
        
        // Disable event listeners for Alert model in tests to avoid Redis/Queue issues
        \App\Modules\Alerts\Models\Alert::flushEventListeners();
    }
}

<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Base test case for all tests.
 * 
 * Automatically disables CSRF middleware and configures test environment.
 * Uses database transactions for faster test execution (no migrations per test).
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Use database transactions instead of RefreshDatabase for speed.
     * Transactions are much faster than running migrations for each test.
     */
    use DatabaseTransactions;

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

        // Run migrations once if database is empty (for :memory: SQLite)
        static $migrated = false;
        if (!$migrated && config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
            $this->artisan('migrate', ['--database' => 'sqlite'])->run();
            $migrated = true;
        }

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

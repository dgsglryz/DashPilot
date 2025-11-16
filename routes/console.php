<?php
declare(strict_types=1);

use App\Modules\Sites\Jobs\CheckSiteHealth;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Schedule automated health checks every 5 minutes.
 * This dispatches CheckSiteHealth jobs for all active sites to the Redis queue.
 */
Schedule::call(function (): void {
    Site::where('status', '!=', 'archived')
        ->chunk(50, function ($sites): void {
            foreach ($sites as $site) {
                CheckSiteHealth::dispatch($site);
            }
        });
})->everyFiveMinutes()
    ->name('health-checks')
    ->withoutOverlapping()
    ->onOneServer();

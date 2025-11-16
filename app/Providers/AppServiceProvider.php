<?php
declare(strict_types=1);

namespace App\Providers;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Alerts\Observers\AlertObserver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory');

        Vite::prefetch(concurrency: 3);

        // Register model observers
        Alert::observe(AlertObserver::class);
    }
}

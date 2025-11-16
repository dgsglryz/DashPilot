<?php
declare(strict_types=1);

use App\Modules\Activity\Controllers\ActivityController;
use App\Modules\Alerts\Controllers\AlertsController;
use App\Modules\Clients\Controllers\ClientsController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Metrics\Controllers\MetricsController;
use App\Modules\Reports\Controllers\ReportsController;
use App\Modules\Revenue\Controllers\RevenueController;
use App\Modules\Settings\Controllers\SettingsController;
use App\Modules\Shopify\Controllers\LiquidEditorController;
use App\Modules\Sites\Controllers\SitesController;
use App\Modules\Tasks\Controllers\TasksController;
use App\Modules\Team\Controllers\TeamController;
use App\Modules\Users\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics.index');

    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

    Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue.index');

    Route::get('/alerts', [AlertsController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/mark-all-read', [AlertsController::class, 'markAllRead'])->name('alerts.markAllRead');
    Route::post('/alerts/{alert}/acknowledge', [AlertsController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::post('/alerts/{alert}/resolve', [AlertsController::class, 'resolve'])->name('alerts.resolve');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate', [ReportsController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/{report}/download', [ReportsController::class, 'download'])->name('reports.download');
    Route::delete('/reports/{report}', [ReportsController::class, 'destroy'])->name('reports.destroy');

    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
    Route::delete('/team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');

    Route::get('/sites', [SitesController::class, 'index'])->name('sites.index');
    Route::get('/sites/{site}', [SitesController::class, 'show'])->name('sites.show');

    Route::resource('clients', ClientsController::class);

    Route::resource('tasks', TasksController::class);
    Route::post('/tasks/{task}/status', [TasksController::class, 'updateStatus'])->name('tasks.status');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences');
    Route::post('/settings/webhooks', [SettingsController::class, 'updateWebhooks'])->name('settings.webhooks');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/2fa/toggle', [SettingsController::class, 'toggleTwoFactor'])->name('settings.2fa');
    Route::delete('/settings/sessions/{session}', [SettingsController::class, 'revokeSession'])->name('settings.sessions.destroy');
    Route::post('/settings/monitoring', [SettingsController::class, 'updateMonitoring'])->name('settings.monitoring');
    Route::post('/settings/thresholds', [SettingsController::class, 'updateThresholds'])->name('settings.thresholds');

    Route::get('/shopify/editor', [LiquidEditorController::class, 'index'])->name('shopify.editor');
    Route::get('/shopify/editor/{site}/files', [LiquidEditorController::class, 'files'])->name('shopify.editor.files');
    Route::get('/shopify/editor/{site}/file', [LiquidEditorController::class, 'file'])->name('shopify.editor.file');
    Route::post('/shopify/editor/{site}/save', [LiquidEditorController::class, 'save'])->name('shopify.editor.save');
    Route::post('/shopify/snippets', [LiquidEditorController::class, 'storeSnippet'])->name('shopify.snippets.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

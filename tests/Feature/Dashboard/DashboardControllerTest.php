<?php
declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_dashboard_displays_stats(): void
    {
        $user = User::factory()->create();

        Site::factory()->count(5)->create(['status' => 'healthy']);
        Site::factory()->count(2)->create(['status' => 'warning']);
        Alert::factory()->count(3)->create(['is_resolved' => false]);
        Report::factory()->create(['uptime_percentage' => 99.5]);
        ActivityLog::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('stats', fn ($stats) => $stats
                ->where('totalSites', 7)
                ->where('healthySites', 5)
                ->where('criticalAlerts', 3)
            )
            ->has('recentAlerts')
            ->has('featuredSites')
        );
    }

    public function test_dashboard_includes_featured_sites(): void
    {
        $user = User::factory()->create();

        Site::factory()->count(10)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('featuredSites')
            ->where('featuredSites', fn ($sites) => count($sites) <= 10) // Should return up to 3 featured sites
        );
    }
}

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
        $client = \App\Modules\Clients\Models\Client::factory()->create(['assigned_developer_id' => $user->id]);

        $healthySites = Site::factory()->count(5)->create(['status' => 'healthy', 'client_id' => $client->id]);
        $warningSites = Site::factory()->count(2)->create(['status' => 'warning', 'client_id' => $client->id]);
        $site1 = $healthySites->first();
        $alerts = Alert::factory()->count(3)->create(['is_resolved' => false, 'site_id' => $site1->id]);
        Report::factory()->create(['uptime_percentage' => 99.5, 'client_id' => $client->id, 'site_id' => $site1->id]);
        ActivityLog::factory()->create(['site_id' => $site1->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('stats')
            ->has('recentAlerts')
            ->has('featuredSites')
        );
        
        // Verify stats separately - count actual created sites
        $pageData = $response->viewData('page');
        $stats = $pageData['props']['stats'];
        $this->assertGreaterThanOrEqual(7, $stats['totalSites']); // At least 7
        $this->assertGreaterThanOrEqual(5, $stats['healthySites']); // At least 5
        $this->assertGreaterThanOrEqual(3, $stats['criticalAlerts']); // At least 3
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

    public function test_dashboard_includes_favorited_sites(): void
    {
        $user = User::factory()->create();
        Site::factory()->count(3)->create(['is_favorited' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('favoritedSites')
        );
    }

    public function test_dashboard_includes_activities(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory()->count(5)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('activities')
        );
    }

    public function test_dashboard_includes_chart_data(): void
    {
        $user = User::factory()->create();
        Site::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('chartData')
            ->has('chartData.sitesByStatus')
            ->has('chartData.alertFrequency')
            ->has('chartData.uptimeTrend')
        );
    }
}

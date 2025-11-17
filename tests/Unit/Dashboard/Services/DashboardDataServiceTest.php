<?php
declare(strict_types=1);

namespace Tests\Unit\Dashboard\Services;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Dashboard\Services\DashboardDataService;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * DashboardDataServiceTest verifies dashboard data aggregation and scoping.
 */
class DashboardDataServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardDataService();
        Cache::flush();
    }

    /**
     * Test that stats returns correct counts for user's assigned clients.
     */
    public function test_stats_returns_correct_counts(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $otherUser = User::factory()->create(['role' => 'developer']);
        
        $assignedClient = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $unassignedClient = Client::factory()->create(['assigned_developer_id' => $otherUser->id]);
        
        Site::factory()->count(3)->create(['client_id' => $assignedClient->id, 'status' => 'healthy']);
        Site::factory()->count(2)->create(['client_id' => $unassignedClient->id, 'status' => 'healthy']);

        $stats = $this->service->stats($user);

        $this->assertEquals(3, $stats['totalSites']);
        $this->assertEquals(3, $stats['healthySites']);
        $this->assertEquals(0, $stats['warningSites']);
    }

    /**
     * Test that stats are scoped to user's assigned clients.
     */
    public function test_stats_scoped_to_user_clients(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        Site::factory()->count(5)->create(['client_id' => $client1->id]);
        Site::factory()->count(3)->create(['client_id' => $client2->id]);

        $stats1 = $this->service->stats($user1);
        $stats2 = $this->service->stats($user2);

        $this->assertEquals(5, $stats1['totalSites']);
        $this->assertEquals(3, $stats2['totalSites']);
    }

    /**
     * Test that admin sees all sites in stats.
     */
    public function test_admin_sees_all_sites_in_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        Site::factory()->count(3)->create(['client_id' => $client1->id]);
        Site::factory()->count(2)->create(['client_id' => $client2->id]);

        $stats = $this->service->stats($admin);

        $this->assertEquals(5, $stats['totalSites']);
    }

    /**
     * Test that recentAlerts returns only user's alerts.
     */
    public function test_recent_alerts_returns_only_user_alerts(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $otherUser = User::factory()->create(['role' => 'developer']);
        
        $assignedClient = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $unassignedClient = Client::factory()->create(['assigned_developer_id' => $otherUser->id]);
        
        $assignedSite = Site::factory()->create(['client_id' => $assignedClient->id]);
        $unassignedSite = Site::factory()->create(['client_id' => $unassignedClient->id]);
        
        Alert::factory()->count(3)->create(['site_id' => $assignedSite->id]);
        Alert::factory()->count(2)->create(['site_id' => $unassignedSite->id]);

        $alerts = $this->service->recentAlerts($user);

        $this->assertCount(3, $alerts);
    }

    /**
     * Test that featuredSites returns only user's sites.
     */
    public function test_featured_sites_returns_only_user_sites(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $otherUser = User::factory()->create(['role' => 'developer']);
        
        $assignedClient = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $unassignedClient = Client::factory()->create(['assigned_developer_id' => $otherUser->id]);
        
        Site::factory()->count(3)->create(['client_id' => $assignedClient->id, 'health_score' => 95]);
        Site::factory()->count(2)->create(['client_id' => $unassignedClient->id, 'health_score' => 90]);

        $sites = $this->service->featuredSites($user);

        $this->assertCount(3, $sites);
    }

    /**
     * Test that favoritedSites returns only user's favorited sites.
     */
    public function test_favorited_sites_returns_only_user_sites(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $otherUser = User::factory()->create(['role' => 'developer']);
        
        $assignedClient = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $unassignedClient = Client::factory()->create(['assigned_developer_id' => $otherUser->id]);
        
        Site::factory()->count(2)->create([
            'client_id' => $assignedClient->id,
            'is_favorited' => true,
            'health_score' => 95,
        ]);
        Site::factory()->count(1)->create([
            'client_id' => $unassignedClient->id,
            'is_favorited' => true,
            'health_score' => 90,
        ]);

        $sites = $this->service->favoritedSites($user);

        $this->assertCount(2, $sites);
    }

    /**
     * Test that sitesByStatus groups correctly.
     */
    public function test_sites_by_status_groups_correctly(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        
        Site::factory()->count(2)->create(['client_id' => $client->id, 'status' => 'healthy']);
        Site::factory()->count(1)->create(['client_id' => $client->id, 'status' => 'warning']);
        Site::factory()->count(1)->create(['client_id' => $client->id, 'status' => 'critical']);

        $statuses = $this->service->sitesByStatus($user);

        $this->assertEquals(2, $statuses['healthy']);
        $this->assertEquals(1, $statuses['warning']);
        $this->assertEquals(1, $statuses['critical']);
    }

    /**
     * Test that alertFrequency calculates correctly.
     */
    public function test_alert_frequency_calculates_correctly(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);
        
        Alert::factory()->count(5)->create([
            'site_id' => $site->id,
            'created_at' => now(),
        ]);

        $frequency = $this->service->alertFrequency($user);

        $this->assertIsArray($frequency);
        $this->assertCount(30, $frequency); // 30 days
    }

    /**
     * Test that topProblematicSites returns only user's sites.
     */
    public function test_top_problematic_sites_returns_only_user_sites(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $otherUser = User::factory()->create(['role' => 'developer']);
        
        $assignedClient = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $unassignedClient = Client::factory()->create(['assigned_developer_id' => $otherUser->id]);
        
        $assignedSite = Site::factory()->create(['client_id' => $assignedClient->id, 'health_score' => 50]);
        $unassignedSite = Site::factory()->create(['client_id' => $unassignedClient->id, 'health_score' => 30]);
        
        Alert::factory()->count(3)->create(['site_id' => $assignedSite->id]);
        Alert::factory()->count(5)->create(['site_id' => $unassignedSite->id]);

        $sites = $this->service->topProblematicSites($user);

        $this->assertCount(1, $sites);
        $this->assertEquals($assignedSite->id, $sites[0]['id']);
    }

    /**
     * Test that activities returns only user's activity logs.
     */
    public function test_activities_returns_only_user_activity_logs(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $otherUser = User::factory()->create(['role' => 'developer']);
        
        $assignedClient = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $unassignedClient = Client::factory()->create(['assigned_developer_id' => $otherUser->id]);
        
        $assignedSite = Site::factory()->create(['client_id' => $assignedClient->id]);
        $unassignedSite = Site::factory()->create(['client_id' => $unassignedClient->id]);
        
        \App\Modules\Activity\Models\ActivityLog::factory()->count(2)->create(['site_id' => $assignedSite->id]);
        \App\Modules\Activity\Models\ActivityLog::factory()->count(1)->create(['site_id' => $unassignedSite->id]);

        $activities = $this->service->activities($user);

        $this->assertCount(2, $activities);
    }
}



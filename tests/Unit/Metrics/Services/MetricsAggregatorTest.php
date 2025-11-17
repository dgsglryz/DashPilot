<?php
declare(strict_types=1);

namespace Tests\Unit\Metrics\Services;

use App\Modules\Clients\Models\Client;
use App\Modules\Metrics\Services\MetricsAggregator;
use App\Modules\Metrics\Services\MetricsCalculator;
use App\Modules\Metrics\Services\MetricsHistoryBuilder;
use App\Modules\Metrics\Services\MetricsDistributionBuilder;
use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MetricsAggregatorTest verifies metrics calculation and scoping.
 */
class MetricsAggregatorTest extends TestCase
{
    use RefreshDatabase;

    private MetricsAggregator $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetricsAggregator(
            new MetricsCalculator(),
            new MetricsHistoryBuilder(),
            new MetricsDistributionBuilder()
        );
    }

    /**
     * Test that buildMetrics returns complete structure.
     */
    public function test_build_metrics_returns_complete_structure(): void
    {
        $metrics = $this->service->buildMetrics('7d');

        $this->assertArrayHasKey('averageUptime', $metrics);
        $this->assertArrayHasKey('uptimeTrend', $metrics);
        $this->assertArrayHasKey('averageResponseTime', $metrics);
        $this->assertArrayHasKey('responseTrend', $metrics);
        $this->assertArrayHasKey('totalRequests', $metrics);
        $this->assertArrayHasKey('requestsTrend', $metrics);
        $this->assertArrayHasKey('errorRate', $metrics);
        $this->assertArrayHasKey('errorTrend', $metrics);
        $this->assertArrayHasKey('uptimeHistory', $metrics);
        $this->assertArrayHasKey('responseTimeHistory', $metrics);
        $this->assertArrayHasKey('trafficHistory', $metrics);
        $this->assertArrayHasKey('platformDistribution', $metrics);
        $this->assertArrayHasKey('topSites', $metrics);
        $this->assertArrayHasKey('errorTypes', $metrics);
        $this->assertArrayHasKey('statusCodes', $metrics);
    }

    /**
     * Test that platformDistribution groups correctly.
     */
    public function test_platform_distribution_groups_correctly(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        
        Site::factory()->count(3)->create(['client_id' => $client->id, 'type' => 'wordpress']);
        Site::factory()->count(2)->create(['client_id' => $client->id, 'type' => 'shopify']);

        $metrics = $this->service->buildMetrics('7d', $user);
        $distribution = $metrics['platformDistribution'];

        $this->assertEquals(3, $distribution['wordpress'] ?? 0);
        $this->assertEquals(2, $distribution['shopify'] ?? 0);
    }

    /**
     * Test that platformDistribution is scoped to user's sites.
     */
    public function test_platform_distribution_scoped_to_user_sites(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        Site::factory()->count(3)->create(['client_id' => $client1->id, 'type' => 'wordpress']);
        Site::factory()->count(2)->create(['client_id' => $client2->id, 'type' => 'shopify']);

        $metrics1 = $this->service->buildMetrics('7d', $user1);
        $distribution1 = $metrics1['platformDistribution'];

        $this->assertEquals(3, $distribution1['wordpress'] ?? 0);
        // shopify key always exists now, but should be 0 for user1's sites
        $this->assertEquals(0, $distribution1['shopify'] ?? 0);
    }

    /**
     * Test that topSites ranks by uptime percentage.
     */
    public function test_top_sites_ranks_by_uptime_percentage(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        
        $site1 = Site::factory()->create(['client_id' => $client->id, 'uptime_percentage' => 99.5]);
        $site2 = Site::factory()->create(['client_id' => $client->id, 'uptime_percentage' => 98.0]);
        $site3 = Site::factory()->create(['client_id' => $client->id, 'uptime_percentage' => 99.9]);

        $metrics = $this->service->buildMetrics('7d', $user);
        $topSites = $metrics['topSites'];

        $this->assertCount(3, $topSites);
        // Should be ordered by uptime descending
        $this->assertEquals($site3->id, $topSites[0]['id']);
        $this->assertEquals($site1->id, $topSites[1]['id']);
        $this->assertEquals($site2->id, $topSites[2]['id']);
    }

    /**
     * Test that topSites returns only user's sites.
     */
    public function test_top_sites_returns_only_user_sites(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        Site::factory()->count(3)->create(['client_id' => $client1->id, 'uptime_percentage' => 99.0]);
        Site::factory()->count(2)->create(['client_id' => $client2->id, 'uptime_percentage' => 98.0]);

        $metrics = $this->service->buildMetrics('7d', $user1);
        $topSites = $metrics['topSites'];

        $this->assertCount(3, $topSites);
    }

    /**
     * Test that aggregateMetrics calculates uptime correctly.
     */
    public function test_aggregate_metrics_calculates_uptime_correctly(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        
        Site::factory()->create(['client_id' => $client->id, 'uptime_percentage' => 99.5]);
        Site::factory()->create(['client_id' => $client->id, 'uptime_percentage' => 98.0]);

        $metrics = $this->service->buildMetrics('7d', $user);

        $this->assertIsFloat($metrics['averageUptime']);
        $this->assertGreaterThan(0, $metrics['averageUptime']);
    }

    /**
     * Test that admin sees all sites in metrics.
     */
    public function test_admin_sees_all_sites_in_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        // Create sites with explicit wordpress type (default) to ensure they're counted
        Site::factory()->count(3)->create(['client_id' => $client1->id, 'type' => 'wordpress']);
        Site::factory()->count(2)->create(['client_id' => $client2->id, 'type' => 'wordpress']);

        $metrics = $this->service->buildMetrics('7d', $admin);
        $distribution = $metrics['platformDistribution'];

        // Admin should see all 5 sites (all wordpress type)
        $total = array_sum($distribution);
        $this->assertEquals(5, $total);
        $this->assertEquals(5, $distribution['wordpress'] ?? 0);
        $this->assertEquals(0, $distribution['shopify'] ?? 0);
    }
}



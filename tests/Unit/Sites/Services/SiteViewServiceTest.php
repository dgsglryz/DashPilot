<?php
declare(strict_types=1);

namespace Tests\Unit\Sites\Services;

use App\Modules\Sites\Models\Site;
use App\Modules\Sites\Services\SiteViewService;
use App\Shared\Services\LookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * SiteViewServiceTest verifies site filtering and presentation logic.
 */
class SiteViewServiceTest extends TestCase
{
    use RefreshDatabase;

    private SiteViewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $lookupService = $this->createMock(LookupService::class);
        $this->service = new SiteViewService($lookupService);
    }

    /**
     * Test that applyFilters filters by platform.
     */
    public function test_apply_filters_filters_by_platform(): void
    {
        Site::factory()->count(3)->create(['type' => 'wordpress']);
        Site::factory()->count(2)->create(['type' => 'shopify']);

        $query = Site::query();
        $request = Request::create('/sites', 'GET', ['platform' => 'wordpress']);
        
        $this->service->applyFilters($query, $request);

        $this->assertEquals(3, $query->count());
    }

    /**
     * Test that applyFilters filters by status.
     */
    public function test_apply_filters_filters_by_status(): void
    {
        Site::factory()->count(3)->create(['status' => 'healthy']);
        Site::factory()->count(2)->create(['status' => 'warning']);

        $query = Site::query();
        $request = Request::create('/sites', 'GET', ['status' => 'healthy']);
        
        $this->service->applyFilters($query, $request);

        $this->assertEquals(3, $query->count());
    }

    /**
     * Test that applyFilters searches by query.
     */
    public function test_apply_filters_searches_by_query(): void
    {
        Site::factory()->create(['name' => 'Test Site', 'url' => 'https://example.com']);
        Site::factory()->create(['name' => 'Other Site', 'url' => 'https://other.com']);

        $query = Site::query();
        $request = Request::create('/sites', 'GET', ['query' => 'Test']);
        
        $this->service->applyFilters($query, $request);

        $this->assertEquals(1, $query->count());
        $this->assertEquals('Test Site', $query->first()->name);
    }

    /**
     * Test that buildStats calculates correct counts.
     */
    public function test_build_stats_calculates_correct_counts(): void
    {
        Site::factory()->count(2)->create(['status' => 'healthy']);
        Site::factory()->count(1)->create(['status' => 'warning']);
        Site::factory()->count(1)->create(['status' => 'critical']);

        $query = Site::query();
        $stats = $this->service->buildStats($query);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['healthy']);
        $this->assertEquals(1, $stats['warning']);
        $this->assertEquals(1, $stats['critical']);
    }

    /**
     * Test that buildSeoInsights returns valid structure.
     */
    public function test_build_seo_insights_returns_valid_structure(): void
    {
        $site = Site::factory()->create(['health_score' => 80]);

        $insights = $this->service->buildSeoInsights($site);

        $this->assertArrayHasKey('score', $insights);
        $this->assertArrayHasKey('metrics', $insights);
        $this->assertArrayHasKey('issues', $insights);
        $this->assertIsInt($insights['score']);
        $this->assertIsArray($insights['metrics']);
        $this->assertIsArray($insights['issues']);
    }

    /**
     * Test that buildPerformanceSeries handles empty collection.
     */
    public function test_build_performance_series_handles_empty_collection(): void
    {
        $series = $this->service->buildPerformanceSeries(collect());

        $this->assertArrayHasKey('labels', $series);
        $this->assertArrayHasKey('datasets', $series);
        $this->assertEmpty($series['labels']);
    }
}



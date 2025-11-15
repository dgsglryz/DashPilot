<?php
declare(strict_types=1);

namespace Tests\Unit\Sites\Jobs;

use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Jobs\CheckSiteHealth;
use App\Modules\Sites\Models\Site;
use App\Modules\Sites\Services\WordPressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckSiteHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_persists_site_check_and_updates_site(): void
    {
        config(['cache.default' => 'array']);

        $site = Site::factory()->create([
            'wp_api_url' => 'https://example.com',
            'wp_api_key' => 'token',
            'health_score' => 60,
        ]);

        Http::fake([
            'https://example.com/wp-json/dashpilot/v1/health' => Http::response([
                'status' => 'ok',
                'score' => 92,
                'response_time' => 180,
            ], 200),
        ]);

        $job = new CheckSiteHealth($site);
        $job->handle(new WordPressService());

        $this->assertDatabaseHas('site_checks', [
            'site_id' => $site->id,
            'check_type' => SiteCheck::TYPE_PERFORMANCE,
            'status' => SiteCheck::STATUS_PASS,
        ]);

        $updatedSite = $site->fresh();
        $this->assertSame(92, $updatedSite->health_score);
        $this->assertNotNull($updatedSite->last_checked_at);
    }
}


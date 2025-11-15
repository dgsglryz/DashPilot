<?php
declare(strict_types=1);

namespace Tests\Unit\SEO\Services;

use App\Modules\SEO\Services\SEOService;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SEOServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_returns_score_and_issues(): void
    {
        config(['cache.default' => 'array', 'services.seo.mock_endpoint' => 'https://mock-seo.test']);
        Cache::flush();

        $site = Site::factory()->create(['url' => 'https://example.com']);

        Http::fake([
            'https://mock-seo.test*' => Http::response([
                'meta_description' => '',
                'h1_count' => 0,
                'uses_ssl' => false,
                'page_speed' => 4.2,
                'has_viewport_tag' => false,
                'missing_alt_tags' => 6,
            ], 200),
        ]);

        $service = new SEOService();

        $result = $service->analyze($site);

        $this->assertSame(23, $result['score']); // 100 -10 -15 -20 -10 -10 -12
        $this->assertCount(6, $result['issues']);

        // Cache hit second time (no additional HTTP call)
        $service->analyze($site);
        Http::assertSentCount(1);
    }
}


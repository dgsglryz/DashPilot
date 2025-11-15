<?php
declare(strict_types=1);

namespace Tests\Unit\Sites\Services;

use App\Modules\Sites\Exceptions\WordPressApiException;
use App\Modules\Sites\Models\Site;
use App\Modules\Sites\Services\WordPressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WordPressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_health_data_is_cached(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();

        $site = Site::factory()->create([
            'wp_api_url' => 'https://example.com',
            'wp_api_key' => 'token',
        ]);

        Http::fake([
            'https://example.com/wp-json/dashpilot/v1/health' => Http::response([
                'status' => 'ok',
                'score' => 95,
                'response_time' => 120,
            ], 200),
        ]);

        $service = new WordPressService();

        $first = $service->fetchHealthData($site);
        Http::assertSentCount(1);

        $second = $service->fetchHealthData($site);
        Http::assertSentCount(1); // Cache prevents repeat HTTP calls.

        $this->assertSame($first, $second);
        $this->assertSame(95, $first['score']);
    }

    public function test_fetch_health_data_throws_when_request_fails(): void
    {
        $this->expectException(WordPressApiException::class);

        $site = Site::factory()->create([
            'wp_api_url' => 'https://example.com',
        ]);

        Http::fake([
            'https://example.com/wp-json/dashpilot/v1/health' => Http::response([], 500),
        ]);

        (new WordPressService())->fetchHealthData($site);
    }
}


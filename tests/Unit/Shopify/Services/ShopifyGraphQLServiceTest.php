<?php
declare(strict_types=1);

namespace Tests\Unit\Shopify\Services;

use App\Modules\Shopify\Exceptions\ShopifyApiException;
use App\Modules\Shopify\Services\ShopifyGraphQLService;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyGraphQLServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_graphql_payload_is_cached(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();

        $site = Site::factory()->create([
            'shopify_store_url' => 'https://dashpilot-test.myshopify.com',
            'shopify_access_token' => 'token',
        ]);

        Http::fake([
            'https://dashpilot-test.myshopify.com/admin/api/2024-10/graphql.json' => Http::response([
                'data' => [
                    'shop' => ['name' => 'DashPilot'],
                ],
            ], 200),
        ]);

        $service = new ShopifyGraphQLService();
        $first = $service->fetchAnalytics($site);
        $second = $service->fetchAnalytics($site);

        Http::assertSentCount(1);
        $this->assertSame($first, $second);
        $this->assertSame('DashPilot', $first['shop']['name']);
    }

    public function test_graphql_failure_throws_exception(): void
    {
        $this->expectException(ShopifyApiException::class);

        $site = Site::factory()->create([
            'shopify_store_url' => 'https://dashpilot-test.myshopify.com',
            'shopify_access_token' => 'token',
        ]);

        Http::fake([
            'https://dashpilot-test.myshopify.com/admin/api/2024-10/graphql.json' => Http::response([], 500),
        ]);

        (new ShopifyGraphQLService())->fetchAnalytics($site);
    }
}


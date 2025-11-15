<?php
declare(strict_types=1);

namespace Tests\Unit\Shopify\Services;

use App\Modules\Shopify\Exceptions\ShopifyApiException;
use App\Modules\Shopify\Services\ShopifyRestService;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyRestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_is_cached(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();

        $site = Site::factory()->create([
            'shopify_store_url' => 'https://dashpilot-test.myshopify.com',
            'shopify_access_token' => 'token',
        ]);

        Http::fake([
            'https://dashpilot-test.myshopify.com/admin/api/2024-10/shop.json' => Http::response(['shop' => ['name' => 'DashPilot']], 200),
            'https://dashpilot-test.myshopify.com/admin/api/2024-10/orders.json*' => Http::response(['orders' => [['name' => '#1001']]], 200),
            'https://dashpilot-test.myshopify.com/admin/api/2024-10/products/count.json' => Http::response(['count' => 42], 200),
        ]);

        $service = new ShopifyRestService();
        $first = $service->fetchOverview($site);
        $second = $service->fetchOverview($site);

        Http::assertSentCount(3); // only first call hits network (three endpoints)
        $this->assertSame($first, $second);
        $this->assertSame(42, $first['products_count']);
    }

    public function test_exceptions_bubble_up_on_failure(): void
    {
        $this->expectException(ShopifyApiException::class);

        $site = Site::factory()->create([
            'shopify_store_url' => 'https://dashpilot-test.myshopify.com',
            'shopify_access_token' => 'token',
        ]);

        Http::fake([
            'https://dashpilot-test.myshopify.com/admin/api/2024-10/shop.json' => Http::response([], 401),
        ]);

        (new ShopifyRestService())->fetchOverview($site);
    }
}


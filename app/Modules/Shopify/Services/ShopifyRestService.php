<?php
declare(strict_types=1);

namespace App\Modules\Shopify\Services;

use App\Modules\Shopify\Exceptions\ShopifyApiException;
use App\Modules\Sites\Models\Site;
use App\Shared\Services\LoggingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * ShopifyRestService aggregates data from the Shopify REST Admin API.
 */
class ShopifyRestService
{
    private const CACHE_TTL_SECONDS = 600;

    /**
     * Fetch overview stats for the given Shopify store and cache the payload.
     *
     * @param Site $site
     *
     * @return array<string, mixed>
     */
    public function fetchOverview(Site $site): array
    {
        $this->guardCredentials($site);

        $cacheKey = sprintf('shopify.%d.overview', $site->id);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => $this->requestOverview($site),
        );
    }

    /**
     * Ensure the site record contains the Shopify credentials.
     *
     * @param Site $site
     *
     * @return void
     */
    private function guardCredentials(Site $site): void
    {
        if (empty($site->shopify_store_url) || empty($site->shopify_access_token)) {
            throw new ShopifyApiException('Shopify credentials missing for site.');
        }
    }

    /**
     * Perform the REST requests.
     *
     * @param Site $site
     *
     * @return array<string, mixed>
     */
    private function requestOverview(Site $site): array
    {
        $version = config('services.shopify.version', '2024-10');
        $base = rtrim($site->shopify_store_url, '/')."/admin/api/{$version}";
        $headers = [
            'X-Shopify-Access-Token' => $site->shopify_access_token,
            'Accept' => 'application/json',
        ];

        $logger = app(LoggingService::class);
        $logger->logServiceMethod(ShopifyRestService::class, 'requestOverview', [
            'site_id' => $site->id,
            'base_url' => $base,
        ]);

        $shop = $this->get("{$base}/shop.json", $headers, $logger);
        $orders = $this->get("{$base}/orders.json?status=any&limit=50", $headers, $logger);
        $productsCount = $this->get("{$base}/products/count.json", $headers, $logger);

        return [
            'shop' => $shop['shop'] ?? [],
            'orders' => $orders['orders'] ?? [],
            'order_count' => count($orders['orders'] ?? []),
            'products_count' => $productsCount['count'] ?? 0,
        ];
    }

    /**
     * Execute a GET request with shared error handling.
     *
     * @param string $url
     * @param array<string, string> $headers
     * @param LoggingService|null $logger
     *
     * @return array<string, mixed>
     */
    private function get(string $url, array $headers, ?LoggingService $logger = null): array
    {
        $logger = $logger ?? app(LoggingService::class);
        $logger->logApiRequest('Shopify', $url);

        $startTime = microtime(true);

        try {
            $response = Http::timeout((int) config('services.shopify.timeout', 10))
                ->withHeaders($headers)
                ->get($url);

            $duration = (microtime(true) - $startTime) * 1000;
            $logger->logApiResponse('Shopify', $url, $response->status(), [
                'duration_ms' => round($duration, 2),
            ]);

            if ($response->failed()) {
                throw new ShopifyApiException(
                    sprintf('Shopify REST request failed: %s', $response->status()),
                    $response->status()
                );
            }

            $payload = $response->json();

            if (!is_array($payload)) {
                throw new ShopifyApiException('Unexpected Shopify REST payload.');
            }

            return $payload;
        } catch (\Throwable $e) {
            $logger->logException($e, [
                'url' => $url,
                'service' => 'Shopify',
            ]);
            throw $e;
        }
    }
}


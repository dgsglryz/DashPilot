<?php
declare(strict_types=1);

namespace App\Modules\Shopify\Services;

use App\Modules\Shopify\Exceptions\ShopifyApiException;
use App\Modules\Sites\Models\Site;
use App\Shared\Services\LoggingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * ShopifyGraphQLService executes the analytics GraphQL query for deeper insights.
 */
class ShopifyGraphQLService
{
    private const CACHE_TTL_SECONDS = 600;
    private const SERVICE_NAME = 'Shopify GraphQL';

    private const ANALYTICS_QUERY = <<<'GQL'
    {
      shop {
        name
        email
        plan {
          displayName
        }
      }
      products(first: 10) {
        edges {
          node {
            title
            totalInventory
            variants(first: 5) {
              edges {
                node {
                  price
                  inventoryQuantity
                }
              }
            }
          }
        }
      }
      orders(first: 20) {
        edges {
          node {
            name
            totalPrice
            lineItems(first: 10) {
              edges {
                node {
                  title
                  quantity
                }
              }
            }
          }
        }
      }
    }
    GQL;

    /**
     * Fetch analytics via GraphQL and cache the payload.
     *
     * @param Site $site
     *
     * @return array<string, mixed>
     */
    public function fetchAnalytics(Site $site): array
    {
        $this->guardCredentials($site);

        $cacheKey = sprintf('shopify.%d.analytics', $site->id);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => $this->requestAnalytics($site),
        );
    }

    /**
     * Ensure Shopify credentials exist on the site record.
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
     * Execute the GraphQL POST request.
     *
     * @param Site $site
     *
     * @return array<string, mixed>
     */
    private function requestAnalytics(Site $site): array
    {
        $endpoint = rtrim($site->shopify_store_url, '/').'/admin/api/2024-10/graphql.json';

        $logger = app(LoggingService::class);
        $logger->logServiceMethod(self::class, 'requestAnalytics', [
            'site_id' => $site->id,
            'endpoint' => $endpoint,
        ]);

        $logger->logApiRequest(self::SERVICE_NAME, $endpoint, [
            'site_id' => $site->id,
            'query_length' => strlen(self::ANALYTICS_QUERY),
        ]);

        $startTime = microtime(true);

        try {
            $response = Http::timeout((int) config('services.shopify.timeout', 10))
                ->withHeaders([
                    'X-Shopify-Access-Token' => $site->shopify_access_token,
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, [
                    'query' => self::ANALYTICS_QUERY,
                ]);

            $duration = (microtime(true) - $startTime) * 1000;
            $logger->logApiResponse(self::SERVICE_NAME, $endpoint, $response->status(), [
                'site_id' => $site->id,
                'duration_ms' => round($duration, 2),
            ]);

            if ($response->failed()) {
                throw new ShopifyApiException(
                    sprintf('%s request failed: %s', self::SERVICE_NAME, $response->status()),
                    $response->status()
                );
            }

            $payload = $response->json();

            if (!is_array($payload) || !isset($payload['data'])) {
                throw new ShopifyApiException(sprintf('Unexpected %s payload.', self::SERVICE_NAME));
            }

            return $payload['data'];
        } catch (\Throwable $e) {
            $logger->logException($e, [
                'endpoint' => $endpoint,
                'service' => self::SERVICE_NAME,
                'site_id' => $site->id,
            ]);
            throw $e;
        }
    }
}


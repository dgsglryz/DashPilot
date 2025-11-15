<?php
declare(strict_types=1);

namespace App\Modules\Sites\Services;

use App\Modules\Sites\Exceptions\WordPressApiException;
use App\Modules\Sites\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * WordPressService fetches remote health data from managed WordPress sites.
 */
class WordPressService
{
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Fetch WordPress health payload for the given site with caching.
     *
     * @param Site $site The site whose WordPress API should be queried.
     *
     * @return array<string, mixed> Normalized health payload.
     *
     * @throws WordPressApiException When credentials or remote response are invalid.
     */
    public function fetchHealthData(Site $site): array
    {
        if (empty($site->wp_api_url)) {
            throw new WordPressApiException('WordPress API URL missing for site.');
        }

        $cacheKey = sprintf('wp.%d.health', $site->id);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => $this->requestHealthData($site),
        );
    }

    /**
     * Execute the HTTP call to the WordPress REST endpoint.
     *
     * @param Site $site
     *
     * @return array<string, mixed>
     *
     * @throws WordPressApiException
     */
    private function requestHealthData(Site $site): array
    {
        $endpoint = rtrim($site->wp_api_url, '/').'/wp-json/dashpilot/v1/health';

        $response = Http::timeout((int) config('services.wordpress.timeout', 10))
            ->acceptJson()
            ->when(
                !empty($site->wp_api_key),
                fn ($request) => $request->withToken($site->wp_api_key),
            )
            ->get($endpoint);

        if ($response->failed()) {
            throw new WordPressApiException(
                sprintf('WordPress API request failed with status %s', $response->status()),
                $response->status()
            );
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new WordPressApiException('Unexpected WordPress API payload.');
        }

        return [
            'status' => $payload['status'] ?? 'unknown',
            'score' => $payload['score'] ?? null,
            'plugins' => $payload['plugins'] ?? [],
            'themes' => $payload['themes'] ?? [],
            'php_version' => $payload['php_version'] ?? null,
            'wp_version' => $payload['wp_version'] ?? null,
            'last_backup' => $payload['last_backup'] ?? null,
            'response_time' => $payload['response_time'] ?? null,
        ];
    }
}


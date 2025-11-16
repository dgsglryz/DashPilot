<?php
declare(strict_types=1);

namespace App\Modules\SEO\Services;

use App\Modules\Sites\Models\Site;
use App\Shared\Services\LoggingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * SEOService performs lightweight on-page checks and returns a score + issues.
 */
class SEOService
{
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * Run the SEO audit for a given site.
     *
     * @param Site $site
     *
     * @return array{score:int, issues:array<int, string>, details:array<string, mixed>}
     */
    public function analyze(Site $site): array
    {
        $logger = app(LoggingService::class);
        $logger->logServiceMethod(SEOService::class, 'analyze', [
            'site_id' => $site->id,
            'url' => $site->url,
        ]);

        $cacheKey = sprintf('seo.%d.score', $site->id);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => $this->evaluate($site->url, $logger),
        );
    }

    /**
     * Execute the checks using a mocked HTTP request (placeholder for real crawler).
     *
     * @param string $url
     * @param LoggingService|null $logger
     *
     * @return array{score:int, issues:array<int, string>, details:array<string, mixed>}
     */
    private function evaluate(string $url, ?LoggingService $logger = null): array
    {
        $logger = $logger ?? app(LoggingService::class);
        $endpoint = config('services.seo.mock_endpoint').'?url='.urlencode($url);

        $logger->logApiRequest('SEO', $endpoint, ['url' => $url]);

        $startTime = microtime(true);
        $response = Http::timeout(10)->get($endpoint);
        $duration = (microtime(true) - $startTime) * 1000;

        $logger->logApiResponse('SEO', $endpoint, $response->status(), [
            'duration_ms' => round($duration, 2),
        ]);

        if ($response->failed()) {
            return [
                'score' => 0,
                'issues' => ['Unable to fetch SEO payload.'],
                'details' => [],
            ];
        }

        $payload = $response->json();

        $score = 100;
        $issues = [];

        if (empty($payload['meta_description'])) {
            $score -= 10;
            $issues[] = 'Missing meta description.';
        }

        if ((int) ($payload['h1_count'] ?? 0) !== 1) {
            $score -= 15;
            $issues[] = 'H1 tag missing or multiple H1s found.';
        }

        if (empty($payload['uses_ssl'])) {
            $score -= 20;
            $issues[] = 'Site is not served over HTTPS.';
        }

        if (($payload['page_speed'] ?? 4) > 3) {
            $score -= 10;
            $issues[] = 'Page speed slower than 3 seconds.';
        }

        if (empty($payload['has_viewport_tag'])) {
            $score -= 10;
            $issues[] = 'Missing mobile viewport meta tag.';
        }

        $missingAlt = (int) ($payload['missing_alt_tags'] ?? 0);
        if ($missingAlt > 0) {
            $deduction = min(20, $missingAlt * 2);
            $score -= $deduction;
            $issues[] = sprintf('%d images missing alt text.', $missingAlt);
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'details' => $payload,
        ];
    }
}


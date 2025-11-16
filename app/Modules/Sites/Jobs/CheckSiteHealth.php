<?php
declare(strict_types=1);

namespace App\Modules\Sites\Jobs;

use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Models\Site;
use App\Modules\Sites\Services\WordPressService;
use App\Shared\Services\LoggingService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * CheckSiteHealth orchestrates fetching health metrics and persisting SiteCheck rows.
 */
class CheckSiteHealth implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param Site $site The site to check.
     */
    public function __construct(private readonly Site $site)
    {
        $this->onQueue('health-checks');
    }

    /**
     * Execute the job.
     *
     * @param WordPressService $wordpressService
     *
     * @return void
     */
    public function handle(WordPressService $wordpressService, LoggingService $logger): void
    {
        $logger->logJob(CheckSiteHealth::class, [
            'site_id' => $this->site->id,
            'site_name' => $this->site->name,
        ], 'started');

        try {
            $payload = $wordpressService->fetchHealthData($this->site);

            $status = $payload['status'] ?? 'unknown';
            $score = (int) ($payload['score'] ?? $this->site->health_score);

            $this->site->checks()->create([
                'check_type' => SiteCheck::TYPE_PERFORMANCE,
                'status' => $status === 'ok' ? SiteCheck::STATUS_PASS : SiteCheck::STATUS_WARNING,
                'response_time' => $payload['response_time'] ?? null,
                'details' => $payload,
                'checked_at' => CarbonImmutable::now(),
            ]);

            $this->site->forceFill([
                'health_score' => $score,
                'last_checked_at' => CarbonImmutable::now(),
            ])->save();

            $logger->logJob(CheckSiteHealth::class, [
                'site_id' => $this->site->id,
                'status' => $status,
                'score' => $score,
            ], 'completed');
        } catch (\Throwable $e) {
            $logger->logJob(CheckSiteHealth::class, [
                'site_id' => $this->site->id,
                'error' => $e->getMessage(),
            ], 'failed');

            throw $e;
        }
    }
}


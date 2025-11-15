<?php
declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ReportGeneratorService produces lightweight summary files for demo purposes.
 */
class ReportGeneratorService
{
    /**
     * Generate reports for the provided sites.
     *
     * @param int $templateId
     * @param string $startDate
     * @param string $endDate
     * @param array<int> $siteIds
     * @param string $format
     */
    public function generate(int $templateId, string $startDate, string $endDate, array $siteIds, string $format): void
    {
        $sites = Site::whereIn('id', $siteIds)->get();

        $sites->each(function (Site $site) use ($templateId, $startDate, $endDate, $format): void {
            $report = Report::create([
                'client_id' => $site->client_id,
                'site_id' => $site->id,
                'report_month' => $site->last_checked_at?->startOfMonth() ?? now()->startOfMonth(),
                'uptime_percentage' => $site->uptime_percentage ?? 99.5,
                'avg_load_time' => $site->avg_load_time ?? 1200,
                'total_backups' => random_int(3, 12),
                'security_scans' => random_int(2, 6),
                'incidents_count' => random_int(0, 3),
                'pdf_path' => $this->writeFile($site, $templateId, $format, $startDate, $endDate),
                'generated_at' => now(),
            ]);

            $report->refresh();
        });
    }

    /**
     * Persist a simple text artifact for download.
     */
    private function writeFile(Site $site, int $templateId, string $format, string $startDate, string $endDate): string
    {
        $directory = 'reports';
        $filename = sprintf(
            '%s/report-%s-%s.%s',
            $directory,
            $site->id,
            Str::uuid()->toString(),
            $format === 'pdf' ? 'pdf' : $format
        );

        Storage::disk('local')->put(
            $filename,
            $this->stubContent($site, $templateId, $startDate, $endDate, $format)
        );

        return $filename;
    }

    /**
     * Build placeholder file contents.
     */
    private function stubContent(Site $site, int $templateId, string $startDate, string $endDate, string $format): string
    {
        $lines = [
            'DashPilot Report',
            '================',
            'Site: '.$site->name,
            'Template: '.$templateId,
            'Period: '.$startDate.' → '.$endDate,
            'Format: '.strtoupper($format),
            'Generated: '.now()->toDateTimeString(),
            '',
            'Uptime: '.($site->uptime_percentage ?? 'N/A').'%',
            'Average Response Time: '.($site->avg_load_time ?? 'N/A').' ms',
            'Total Backups: '.random_int(3, 12),
            'Security Scans: '.random_int(2, 6),
        ];

        return implode(PHP_EOL, $lines);
    }
}


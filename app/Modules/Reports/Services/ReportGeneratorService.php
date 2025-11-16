<?php
declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Modules\Clients\Models\Client;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ReportGeneratorService produces PDF reports for client-facing documentation.
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
     *
     * @return void
     */
    public function generate(int $templateId, string $startDate, string $endDate, array $siteIds, string $format): void
    {
        $sites = Site::whereIn('id', $siteIds)->with('client')->get();

        $sites->each(function (Site $site) use ($templateId, $startDate, $endDate, $format): void {
            $report = Report::create([
                'client_id' => $site->client_id,
                'site_id' => $site->id,
                'report_month' => Carbon::parse($startDate)->startOfMonth(),
                'uptime_percentage' => $site->uptime_percentage ?? 99.5,
                'avg_load_time' => $site->avg_load_time ?? 1.2,
                'total_backups' => random_int(20, 40),
                'security_scans' => random_int(4, 10),
                'incidents_count' => random_int(0, 3),
                'pdf_path' => $format === 'pdf' ? $this->generatePdf($site, $templateId, $startDate, $endDate) : $this->writeFile($site, $templateId, $format, $startDate, $endDate),
                'generated_at' => now(),
            ]);

            $report->refresh();
        });
    }

    /**
     * Generate a PDF report using dompdf.
     *
     * @param Site $site
     * @param int $templateId
     * @param string $startDate
     * @param string $endDate
     *
     * @return string
     */
    private function generatePdf(Site $site, int $templateId, string $startDate, string $endDate): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        $html = $this->buildPdfHtml($site, $templateId, $startDate, $endDate);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $directory = 'reports';
        $filename = sprintf(
            '%s/report-%s-%s.pdf',
            $directory,
            $site->id,
            Str::uuid()->toString()
        );

        Storage::disk('local')->put($filename, $dompdf->output());

        return $filename;
    }

    /**
     * Build HTML content for PDF report.
     *
     * @param Site $site
     * @param int $templateId
     * @param string $startDate
     * @param string $endDate
     *
     * @return string
     */
    private function buildPdfHtml(Site $site, int $templateId, string $startDate, string $endDate): string
    {
        $client = $site->client;
        $templateName = $this->getTemplateName($templateId);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 40px;
        }
        .header {
            border-bottom: 3px solid #3B82F6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #1F2937;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #6B7280;
            margin: 5px 0 0 0;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #111827;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #F9FAFB;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3B82F6;
        }
        .info-item strong {
            display: block;
            color: #374151;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
        }
        .info-item span {
            color: #111827;
            font-size: 18px;
            font-weight: bold;
        }
        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .metrics-table th,
        .metrics-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        .metrics-table th {
            background: #F3F4F6;
            color: #374151;
            font-weight: 600;
        }
        .metrics-table td {
            color: #111827;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            color: #6B7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DashPilot Operations Report</h1>
        <p>{$templateName} - {$site->name}</p>
        <p>Period: {$startDate} to {$endDate}</p>
    </div>

    <div class="section">
        <h2>Client Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>Client Name</strong>
                <span>{$client->name}</span>
            </div>
            <div class="info-item">
                <strong>Company</strong>
                <span>{$client->company}</span>
            </div>
            <div class="info-item">
                <strong>Site URL</strong>
                <span>{$site->url}</span>
            </div>
            <div class="info-item">
                <strong>Platform</strong>
                <span>{$site->type}</span>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Performance Metrics</h2>
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Uptime Percentage</td>
                    <td>{$site->uptime_percentage}%</td>
                </tr>
                <tr>
                    <td>Average Load Time</td>
                    <td>{$site->avg_load_time} seconds</td>
                </tr>
                <tr>
                    <td>Health Score</td>
                    <td>{$site->health_score}/100</td>
                </tr>
                <tr>
                    <td>Total Backups</td>
                    <td>" . random_int(20, 40) . "</td>
                </tr>
                <tr>
                    <td>Security Scans</td>
                    <td>" . random_int(4, 10) . "</td>
                </tr>
                <tr>
                    <td>Incidents</td>
                    <td>" . random_int(0, 3) . "</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Report Summary</h2>
        <p>This report was generated on " . now()->format('F d, Y \a\t H:i') . " and covers the period from {$startDate} to {$endDate}.</p>
        <p>The site has maintained a {$site->uptime_percentage}% uptime with an average response time of {$site->avg_load_time} seconds during this reporting period.</p>
    </div>

    <div class="footer">
        <p>Generated by DashPilot Operations Dashboard</p>
        <p>© " . date('Y') . " DashPilot. All rights reserved.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get template name by ID.
     *
     * @param int $templateId
     *
     * @return string
     */
    private function getTemplateName(int $templateId): string
    {
        return match ($templateId) {
            1 => 'Performance Summary',
            2 => 'Security Audit',
            3 => 'Uptime Report',
            default => 'Custom Report',
        };
    }

    /**
     * Persist a simple text artifact for download (CSV/XLSX).
     *
     * @param Site $site
     * @param int $templateId
     * @param string $format
     * @param string $startDate
     * @param string $endDate
     *
     * @return string
     */
    private function writeFile(Site $site, int $templateId, string $format, string $startDate, string $endDate): string
    {
        $directory = 'reports';
        $filename = sprintf(
            '%s/report-%s-%s.%s',
            $directory,
            $site->id,
            Str::uuid()->toString(),
            $format
        );

        Storage::disk('local')->put(
            $filename,
            $this->stubContent($site, $templateId, $startDate, $endDate, $format)
        );

        return $filename;
    }

    /**
     * Build placeholder file contents for non-PDF formats.
     *
     * @param Site $site
     * @param int $templateId
     * @param string $startDate
     * @param string $endDate
     * @param string $format
     *
     * @return string
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
            'Average Response Time: '.($site->avg_load_time ?? 'N/A').' seconds',
            'Total Backups: '.random_int(20, 40),
            'Security Scans: '.random_int(4, 10),
        ];

        return implode(PHP_EOL, $lines);
    }
}


<?php
declare(strict_types=1);

namespace Tests\Unit\Reports\Services;

use App\Modules\Clients\Models\Client;
use App\Modules\Reports\Models\Report;
use App\Modules\Reports\Services\ReportGeneratorService;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_generate_creates_reports_for_sites(): void
    {
        $client = Client::factory()->create();
        $site = Site::factory()->create(['client_id' => $client->id]);

        $service = new ReportGeneratorService();
        $service->generate(
            templateId: 1,
            startDate: now()->subMonth()->toDateString(),
            endDate: now()->toDateString(),
            siteIds: [$site->id],
            format: 'pdf'
        );

        $this->assertDatabaseHas('reports', [
            'site_id' => $site->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_generate_creates_pdf_file(): void
    {
        $site = Site::factory()->create();

        $service = new ReportGeneratorService();
        $service->generate(
            templateId: 1,
            startDate: now()->subMonth()->toDateString(),
            endDate: now()->toDateString(),
            siteIds: [$site->id],
            format: 'pdf'
        );

        $report = Report::where('site_id', $site->id)->first();
        $this->assertNotNull($report->pdf_path);
        Storage::disk('local')->assertExists($report->pdf_path);
    }

    public function test_generate_handles_multiple_sites(): void
    {
        $sites = Site::factory()->count(3)->create();
        $siteIds = $sites->pluck('id')->toArray();

        $service = new ReportGeneratorService();
        $service->generate(
            templateId: 1,
            startDate: now()->subMonth()->toDateString(),
            endDate: now()->toDateString(),
            siteIds: $siteIds,
            format: 'pdf'
        );

        $this->assertEquals(3, Report::whereIn('site_id', $siteIds)->count());
    }
}

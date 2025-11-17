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
        $service = new ReportGeneratorService();
        $client = Client::factory()->create();
        $site = Site::factory()->create(['client_id' => $client->id]);

        $service->generate(1, '2024-01-01', '2024-01-31', [$site->id], 'pdf');

        $this->assertDatabaseHas('reports', [
            'site_id' => $site->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_generate_stores_pdf_file(): void
    {
        $service = new ReportGeneratorService();
        $site = Site::factory()->create();

        $service->generate(1, '2024-01-01', '2024-01-31', [$site->id], 'pdf');

        $report = Report::where('site_id', $site->id)->first();
        $this->assertNotNull($report->pdf_path);
        Storage::disk('local')->assertExists($report->pdf_path);
    }

    public function test_generate_creates_multiple_reports(): void
    {
        $service = new ReportGeneratorService();
        $sites = Site::factory()->count(3)->create();

        $service->generate(1, '2024-01-01', '2024-01-31', $sites->pluck('id')->toArray(), 'pdf');

        $this->assertCount(3, Report::all());
    }
}

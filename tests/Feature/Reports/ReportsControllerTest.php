<?php
declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_requires_authentication(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_reports_index_displays_reports(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();
        Report::factory()->count(3)->create(['site_id' => $site->id]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('reportTemplates')
            ->has('recentReports')
            ->has('sites')
        );
    }

    public function test_reports_generate_creates_reports(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $response = $this->actingAs($user)->post(route('reports.generate'), [
            'templateId' => 1,
            'startDate' => '2024-01-01',
            'endDate' => '2024-01-31',
            'siteIds' => [(string) $site->id],
            'format' => 'pdf',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', ['site_id' => $site->id]);
    }

    public function test_reports_download_returns_file(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['pdf_path' => 'reports/test.pdf']);

        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('reports/test.pdf', 'test content');

        $response = $this->actingAs($user)->get(route('reports.download', $report));

        $response->assertOk();
    }

    public function test_reports_destroy_deletes_report(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $response = $this->actingAs($user)->delete(route('reports.destroy', $report));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }
}

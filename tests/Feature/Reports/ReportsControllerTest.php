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
}

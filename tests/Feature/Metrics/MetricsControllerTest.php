<?php
declare(strict_types=1);

namespace Tests\Feature\Metrics;

use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_index_requires_authentication(): void
    {
        $response = $this->get(route('metrics.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_metrics_index_displays_metrics(): void
    {
        $user = User::factory()->create();
        Site::factory()->count(3)->create(['uptime_percentage' => 99.5]);
        SiteCheck::factory()->count(10)->create();

        $response = $this->actingAs($user)->get(route('metrics.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('metrics')
        );
    }

    public function test_metrics_index_respects_time_range(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('metrics.index', ['time_range' => '30d']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('metrics')
        );
    }
}

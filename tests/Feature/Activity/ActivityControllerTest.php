<?php
declare(strict_types=1);

namespace Tests\Feature\Activity;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_index_requires_authentication(): void
    {
        $response = $this->get(route('activity.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_activity_index_displays_activities(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();
        ActivityLog::factory()->count(5)->create(['site_id' => $site->id]);

        $response = $this->actingAs($user)->get(route('activity.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('activities.data', 5)
        );
    }

    public function test_activity_index_filters_by_search(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['name' => 'Test Site']);
        ActivityLog::factory()->create([
            'site_id' => $site->id,
            'action' => 'Site checked',
        ]);

        $response = $this->actingAs($user)->get(route('activity.index', ['search' => 'Test']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('activities.data', 1)
        );
    }
}

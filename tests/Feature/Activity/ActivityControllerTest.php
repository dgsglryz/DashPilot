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
        $user = User::factory()->create(['role' => 'admin']);
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
        $user = User::factory()->create(['role' => 'admin']);
        $site = Site::factory()->create(['name' => 'Test Site']);
        ActivityLog::factory()->create([
            'site_id' => $site->id,
            'action' => 'Site checked',
        ]);

        $response = $this->actingAs($user)->get(route('activity.index', ['action' => 'Site checked']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('activities.data', 1)
        );
    }

    public function test_activity_index_filters_by_site(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();
        ActivityLog::factory()->create(['site_id' => $site1->id]);
        ActivityLog::factory()->create(['site_id' => $site2->id]);

        $response = $this->actingAs($user)->get(route('activity.index', ['site_id' => $site1->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('activities.data', 1)
        );
    }

    public function test_activity_index_filters_by_action(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        ActivityLog::factory()->create(['action' => 'site_checked']);
        ActivityLog::factory()->create(['action' => 'alert_created']);

        $response = $this->actingAs($user)->get(route('activity.index', ['action' => 'site_checked']));

        $response->assertOk();
    }

    public function test_activity_index_filters_by_date_range(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        ActivityLog::factory()->create(['created_at' => now()->subDays(5)]);
        ActivityLog::factory()->create(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($user)->get(route('activity.index', [
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
    }

    public function test_activity_export_generates_csv(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        ActivityLog::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('activity.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        // Streamed responses need to be read differently
        $content = $response->streamedContent();
        $this->assertStringContainsString('ID,Action,Description', $content);
    }

    public function test_activity_export_applies_filters(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $site = Site::factory()->create();
        ActivityLog::factory()->create(['site_id' => $site->id, 'action' => 'site_checked']);
        ActivityLog::factory()->create(['action' => 'alert_created']);

        $response = $this->actingAs($user)->get(route('activity.export', [
            'site_id' => $site->id,
            'action' => 'site_checked',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('site_checked', $content);
    }
}

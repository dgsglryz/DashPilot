<?php
declare(strict_types=1);

namespace Tests\Feature\Sites;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sites_index_requires_authentication(): void
    {
        $response = $this->get(route('sites.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_sites_index_displays_all_sites(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->count(5)->create(['client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sites.data', 5)
            ->has('stats')
        );
    }

    public function test_sites_index_filters_by_platform(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->count(3)->create(['type' => 'wordpress', 'client_id' => $client->id]);
        Site::factory()->count(2)->create(['type' => 'shopify', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('sites.index', ['platform' => 'wordpress']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sites.data', 3)
        );
    }

    public function test_sites_index_filters_by_status(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->count(3)->create(['status' => 'healthy', 'client_id' => $client->id]);
        Site::factory()->count(2)->create(['status' => 'warning', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('sites.index', ['status' => 'healthy']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sites.data', 3)
        );
    }

    public function test_sites_index_searches_by_name(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->create(['name' => 'Test Site', 'client_id' => $client->id]);
        Site::factory()->create(['name' => 'Another Site', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('sites.index', ['query' => 'Test']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sites.data', 1)
            ->where('sites.data.0.name', 'Test Site')
        );
    }

    public function test_sites_show_displays_site_details(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('sites.show', $site));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('site')
        );
        
        // Verify site data structure
        $pageData = $response->viewData('page');
        $this->assertEquals($site->id, $pageData['props']['site']['id']);
        $this->assertEquals($site->name, $pageData['props']['site']['name']);
    }

    public function test_sites_create_displays_form(): void
    {
        $user = User::factory()->create();
        Client::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('sites.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('clients', 3)
        );
    }

    public function test_sites_store_creates_new_site(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->post(route('sites.store'), [
            'name' => 'New Site',
            'url' => 'https://example.com',
            'type' => 'wordpress',
            'client_id' => $client->id,
            'status' => 'healthy',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sites', [
            'name' => 'New Site',
            'url' => 'https://example.com',
            'type' => 'wordpress',
            'client_id' => $client->id,
        ]);
    }

    public function test_sites_edit_displays_form(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('sites.edit', $site));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('site')
            ->where('site.id', $site->id)
        );
    }

    public function test_sites_update_modifies_site(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['name' => 'Old Name', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->put(route('sites.update', $site), [
            'name' => 'Updated Name',
            'url' => $site->url,
            'type' => $site->type,
            'status' => $site->status,
            'client_id' => $client->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_sites_destroy_deletes_site(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($user)->delete(route('sites.destroy', $site));

        $response->assertRedirect(route('sites.index'));
        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
        // Verify activity log was created (with null site_id since site is deleted)
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'site_deleted',
            'user_id' => $user->id,
        ]);
    }

    public function test_sites_run_health_check_dispatches_job(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);

        \Illuminate\Support\Facades\Queue::fake();

        $response = $this->actingAs($user)->post(route('sites.health-check', $site));

        $response->assertRedirect();
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Modules\Sites\Jobs\CheckSiteHealth::class);
    }

    public function test_sites_toggle_favorite_updates_status(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['is_favorited' => false, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->post(route('sites.toggle-favorite', $site));

        $response->assertRedirect();
        $this->assertTrue($site->fresh()->is_favorited);
    }

    public function test_sites_export_csv_downloads_file(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->count(3)->create(['client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('sites.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_sites_export_xlsx_downloads_file(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->count(3)->create(['client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('sites.export', ['format' => 'xlsx']));

        $response->assertOk();
        // Excel::download returns BinaryFileResponse, not StreamedResponse
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }
}

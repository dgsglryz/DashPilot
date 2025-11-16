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
        Site::factory()->count(5)->create();

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sites', 5)
            ->has('stats')
        );
    }

    public function test_sites_index_filters_by_platform(): void
    {
        $user = User::factory()->create();
        Site::factory()->count(3)->create(['type' => 'wordpress']);
        Site::factory()->count(2)->create(['type' => 'shopify']);

        $response = $this->actingAs($user)->get(route('sites.index', ['platform' => 'wordpress']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sites', 3)
        );
    }

    public function test_sites_index_filters_by_status(): void
    {
        $user = User::factory()->create();
        Site::factory()->count(3)->create(['status' => 'healthy']);
        Site::factory()->count(2)->create(['status' => 'warning']);

        $response = $this->actingAs($user)->get(route('sites.index', ['status' => 'healthy']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sites', 3)
        );
    }

    public function test_sites_index_searches_by_name(): void
    {
        $user = User::factory()->create();
        Site::factory()->create(['name' => 'Test Site']);
        Site::factory()->create(['name' => 'Another Site']);

        $response = $this->actingAs($user)->get(route('sites.index', ['query' => 'Test']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sites', 1)
            ->where('sites.0.name', 'Test Site')
        );
    }

    public function test_sites_show_displays_site_details(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
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
}

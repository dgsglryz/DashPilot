<?php
declare(strict_types=1);

namespace Tests\Feature\Clients;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ClientsControllerTest tests client CRUD operations and filtering functionality.
 */
class ClientsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_index_requires_authentication(): void
    {
        $response = $this->get(route('clients.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_clients_index_displays_all_clients(): void
    {
        $user = User::factory()->create();
        Client::factory()->count(5)->create();

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('clients', 5)
            ->has('developers')
        );
    }

    public function test_clients_index_filters_by_status(): void
    {
        $user = User::factory()->create();
        Client::factory()->count(3)->create(['status' => 'active']);
        Client::factory()->count(2)->create(['status' => 'inactive']);

        $response = $this->actingAs($user)->get(route('clients.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('clients', 3)
        );
    }

    public function test_clients_index_search_functionality(): void
    {
        $user = User::factory()->create();
        Client::factory()->create(['name' => 'Test Client', 'company' => 'Test Corp']);
        Client::factory()->create(['name' => 'Other Client', 'company' => 'Other Inc']);

        $response = $this->actingAs($user)->get(route('clients.index', ['query' => 'Test']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->where('clients.0.name', 'Test Client')
        );
    }

    public function test_client_create_page_displays(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('clients.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('developers')
        );
    }

    public function test_client_store_creates_new_client(): void
    {
        $user = User::factory()->create();
        $developer = User::factory()->create(['role' => 'developer']);

        $response = $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'New Client',
            'company' => 'New Corp',
            'email' => 'client@example.com',
            'status' => 'active',
            'assigned_developer_id' => $developer->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'New Client',
            'company' => 'New Corp',
            'email' => 'client@example.com',
        ]);

        $client = \App\Modules\Clients\Models\Client::where('email', 'client@example.com')->first();
        $this->assertNotNull($client);
        $response->assertRedirect(route('clients.show', $client));
    }

    public function test_client_show_displays_client_details(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->get(route('clients.show', $client));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('client')
            ->has('sites')
            ->has('recentTasks')
        );
    }

    public function test_client_edit_page_displays(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->get(route('clients.edit', $client));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('client')
            ->has('developers')
        );
    }

    public function test_client_update_modifies_existing_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'company' => $client->company,
            'email' => $client->email,
            'status' => $client->status,
        ]);

        $response->assertRedirect(route('clients.show', $client));
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_client_destroy_deletes_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->delete(route('clients.destroy', $client));

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_client_reports_displays_reports(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $site = Site::factory()->create(['client_id' => $client->id]);
        \App\Modules\Reports\Models\Report::factory()->create(['client_id' => $client->id, 'site_id' => $site->id]);

        $response = $this->actingAs($user)->get(route('clients.reports', $client));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('reports')
            ->has('client')
        );
    }
}


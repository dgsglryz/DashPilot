<?php
declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_authentication(): void
    {
        $response = $this->getJson(route('search', ['q' => 'test']));

        $response->assertUnauthorized();
    }

    public function test_search_returns_empty_for_short_query(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'a']));

        $response->assertOk()
            ->assertJson(['results' => []]);
    }

    public function test_search_defaults_to_pages_scope(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'dash']));

        $response->assertOk();

        $data = $response->json();
        $this->assertNotEmpty($data['results']);
        $this->assertTrue(
            collect($data['results'])->every(
                fn ($result) => in_array($result['type'], ['page', 'card'], true)
            ),
            'Results should only include page or card suggestions for default scope',
        );
    }

    public function test_search_finds_cards_by_keyword(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'seo']));

        $response->assertOk()
            ->assertJsonStructure(['results']);

        $this->assertTrue(
            collect($response->json('results'))->contains(
                fn ($result) => $result['type'] === 'card' && $result['label'] === 'SEO Performance'
            ),
        );
    }

    public function test_search_finds_sites_when_scoped(): void
    {
        $user = User::factory()->create();
        Site::factory()->create(['name' => 'Test Site', 'url' => 'https://test.com']);

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'Test', 'scope' => 'sites']));

        $response->assertOk()
            ->assertJsonStructure(['results']);

        $data = $response->json();
        $this->assertNotEmpty($data['results']);
        $this->assertTrue(
            collect($data['results'])->contains(fn ($result) => $result['type'] === 'site')
        );
    }

    public function test_search_finds_clients_when_scoped(): void
    {
        $user = User::factory()->create();
        Client::factory()->create(['name' => 'Test Client', 'company' => 'Test Company']);

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'Test', 'scope' => 'clients']));

        $response->assertOk();

        $data = $response->json();
        $this->assertTrue(
            collect($data['results'])->contains(fn ($result) => $result['type'] === 'client')
        );
    }

    public function test_search_finds_alerts_when_scoped(): void
    {
        $user = User::factory()->create();
        Alert::factory()->create(['title' => 'Test Alert', 'message' => 'Test message']);

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'Test', 'scope' => 'alerts']));

        $response->assertOk();

        $data = $response->json();
        $this->assertTrue(
            collect($data['results'])->contains(fn ($result) => $result['type'] === 'alert')
        );
    }

    public function test_search_finds_tasks_when_scoped(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['title' => 'Test Task', 'description' => 'Test description']);

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'Test', 'scope' => 'tasks']));

        $response->assertOk();

        $data = $response->json();
        $this->assertTrue(
            collect($data['results'])->contains(fn ($result) => $result['type'] === 'task')
        );
    }

    public function test_search_finds_pages_by_keyword(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'dashboard']));

        $response->assertOk();

        $data = $response->json();
        $this->assertTrue(
            collect($data['results'])->contains(fn ($result) => 
                $result['type'] === 'page' && $result['label'] === 'Dashboard'
            )
        );
    }

    public function test_search_limits_results(): void
    {
        $user = User::factory()->create();
        Site::factory()->count(30)->create(['name' => 'Test Site']);

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'Test', 'scope' => 'sites']));

        $response->assertOk();

        $data = $response->json();
        $this->assertLessThanOrEqual(20, count($data['results']));
    }

    public function test_invalid_scope_falls_back_to_pages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'team', 'scope' => 'unknown']));

        $response->assertOk();

        $this->assertTrue(
            collect($response->json('results'))->every(
                fn ($result) => in_array($result['type'], ['page', 'card'], true)
            ),
        );
    }
}


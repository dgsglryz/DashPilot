<?php
declare(strict_types=1);

namespace Tests\Feature\Revenue;

use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_index_requires_authentication(): void
    {
        $response = $this->get(route('revenue.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_revenue_index_displays_revenue_data(): void
    {
        $user = User::factory()->create();
        Site::factory()->count(3)->create(['type' => 'shopify']);

        $response = $this->actingAs($user)->get(route('revenue.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('stats')
            ->has('revenueBySite')
            ->has('monthlyTrend')
        );
    }

    public function test_revenue_index_includes_woocommerce_sites(): void
    {
        $user = User::factory()->create();
        $client = \App\Modules\Clients\Models\Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->create(['type' => 'shopify', 'client_id' => $client->id]);
        Site::factory()->create(['type' => 'woocommerce', 'client_id' => $client->id]);
        Site::factory()->create(['type' => 'wordpress', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('revenue.index'));

        $response->assertOk();
        $pageData = $response->viewData('page');
        $this->assertCount(2, $pageData['props']['revenueBySite']);
    }

    public function test_revenue_index_calculates_stats(): void
    {
        $user = User::factory()->create();
        $client = \App\Modules\Clients\Models\Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->count(2)->create(['type' => 'shopify', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('revenue.index'));

        $response->assertOk();
        $pageData = $response->viewData('page');
        $stats = $pageData['props']['stats'];
        $this->assertArrayHasKey('totalRevenue', $stats);
        $this->assertArrayHasKey('monthlyRevenue', $stats);
        $this->assertArrayHasKey('averageRevenue', $stats);
        $this->assertEquals(2, $stats['totalSites']);
    }

    public function test_revenue_index_generates_monthly_trend(): void
    {
        $user = User::factory()->create();
        Site::factory()->create(['type' => 'shopify']);

        $response = $this->actingAs($user)->get(route('revenue.index'));

        $response->assertOk();
        $pageData = $response->viewData('page');
        $trend = $pageData['props']['monthlyTrend'];
        $this->assertCount(6, $trend);
    }
}

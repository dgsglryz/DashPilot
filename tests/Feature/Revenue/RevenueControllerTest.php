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
        Site::factory()->count(5)->create(['type' => 'shopify']);

        $response = $this->actingAs($user)->get(route('revenue.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('stats')
            ->has('revenueBySite')
            ->has('monthlyTrend')
        );
    }
}

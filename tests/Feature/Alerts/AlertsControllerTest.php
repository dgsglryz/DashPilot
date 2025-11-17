<?php
declare(strict_types=1);

namespace Tests\Feature\Alerts;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_index_requires_authentication(): void
    {
        $response = $this->get(route('alerts.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_alerts_index_displays_all_alerts(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);
        Alert::factory()->count(5)->create(['site_id' => $site->id]);

        $response = $this->actingAs($user)->get(route('alerts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('alerts.data', 5)
            ->has('stats')
        );
    }

    public function test_alerts_mark_all_read(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);
        Alert::factory()->count(3)->create([
            'site_id' => $site->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->post(route('alerts.markAllRead'));

        $response->assertRedirect();
        // Only count alerts for user's assigned clients
        $this->assertEquals(0, Alert::whereHas('site.client', fn ($q) => $q->where('assigned_developer_id', $user->id))
            ->where('is_read', false)->count());
    }

    public function test_alerts_acknowledge(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->post(route('alerts.acknowledge', $alert));

        $response->assertRedirect();
        $alert->refresh();
        $this->assertTrue($alert->is_read);
        $this->assertEquals('acknowledged', $alert->status);
        $this->assertEquals($user->id, $alert->acknowledged_by);
    }

    public function test_alerts_resolve(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'is_resolved' => false,
        ]);

        $response = $this->actingAs($user)->post(route('alerts.resolve', $alert));

        $response->assertRedirect();
        $alert->refresh();
        $this->assertTrue($alert->is_resolved);
        $this->assertEquals('resolved', $alert->status);
        $this->assertEquals($user->id, $alert->resolved_by);
    }

    public function test_alerts_export_generates_csv(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);
        Alert::factory()->count(3)->create(['site_id' => $site->id]);

        $response = $this->actingAs($user)->get(route('alerts.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}

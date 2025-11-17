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

    // ========== AUTHORIZATION TESTS ==========

    /**
     * Test that user cannot view alerts from unassigned clients.
     */
    public function test_user_cannot_view_alerts_from_unassigned_clients(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        $site1 = Site::factory()->create(['client_id' => $client1->id]);
        $site2 = Site::factory()->create(['client_id' => $client2->id]);
        
        $alert1 = Alert::factory()->create(['site_id' => $site1->id]);
        $alert2 = Alert::factory()->create(['site_id' => $site2->id]);

        // User1 should only see alert1
        $response = $this->actingAs($user1)->get(route('alerts.index'));
        $response->assertOk();
        
        $pageData = $response->viewData('page');
        $alerts = collect($pageData['props']['alerts']['data'] ?? []);
        $alertIds = $alerts->pluck('id')->toArray();
        
        $this->assertContains($alert1->id, $alertIds);
        $this->assertNotContains($alert2->id, $alertIds);
    }

    /**
     * Test that user cannot resolve alerts from unassigned clients.
     */
    public function test_user_cannot_resolve_alerts_from_unassigned_clients(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        $site2 = Site::factory()->create(['client_id' => $client2->id]);
        $alert = Alert::factory()->create([
            'site_id' => $site2->id,
            'is_resolved' => false,
        ]);

        $response = $this->actingAs($user1)->post(route('alerts.resolve', $alert));

        $response->assertForbidden();
        $alert->refresh();
        $this->assertFalse($alert->is_resolved);
    }

    /**
     * Test that user cannot acknowledge alerts from unassigned clients.
     */
    public function test_user_cannot_acknowledge_alerts_from_unassigned_clients(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        $site2 = Site::factory()->create(['client_id' => $client2->id]);
        $alert = Alert::factory()->create([
            'site_id' => $site2->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user1)->post(route('alerts.acknowledge', $alert));

        $response->assertForbidden();
        $alert->refresh();
        $this->assertFalse($alert->is_read);
    }

    // ========== DATA SCOPING TESTS ==========

    /**
     * Test that markAllRead only marks user's alerts (critical bug fix test).
     */
    public function test_mark_all_read_only_marks_users_alerts(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        $site1 = Site::factory()->create(['client_id' => $client1->id]);
        $site2 = Site::factory()->create(['client_id' => $client2->id]);
        
        // Create unread alerts for both users
        $user1Alert1 = Alert::factory()->create([
            'site_id' => $site1->id,
            'is_read' => false,
        ]);
        $user1Alert2 = Alert::factory()->create([
            'site_id' => $site1->id,
            'is_read' => false,
        ]);
        $user2Alert = Alert::factory()->create([
            'site_id' => $site2->id,
            'is_read' => false,
        ]);

        // User1 marks all as read
        $response = $this->actingAs($user1)->post(route('alerts.markAllRead'));
        $response->assertRedirect();

        // Verify user1's alerts are marked as read
        $user1Alert1->refresh();
        $user1Alert2->refresh();
        $this->assertTrue($user1Alert1->is_read);
        $this->assertTrue($user1Alert2->is_read);

        // CRITICAL: Verify user2's alert is NOT marked as read
        $user2Alert->refresh();
        $this->assertFalse($user2Alert->is_read, 'User2 alert should NOT be marked as read by user1');
    }

    /**
     * Test that alerts index only shows alerts for user's assigned clients.
     */
    public function test_alerts_only_show_for_user_sites(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $otherUser = User::factory()->create(['role' => 'developer']);
        
        $assignedClient = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $unassignedClient = Client::factory()->create(['assigned_developer_id' => $otherUser->id]);
        
        $assignedSite = Site::factory()->create(['client_id' => $assignedClient->id]);
        $unassignedSite = Site::factory()->create(['client_id' => $unassignedClient->id]);
        
        $assignedAlert = Alert::factory()->create(['site_id' => $assignedSite->id]);
        $unassignedAlert = Alert::factory()->create(['site_id' => $unassignedSite->id]);

        $response = $this->actingAs($user)->get(route('alerts.index'));
        $response->assertOk();
        
        $pageData = $response->viewData('page');
        $alerts = collect($pageData['props']['alerts']['data'] ?? []);
        $alertIds = $alerts->pluck('id')->toArray();
        
        $this->assertContains($assignedAlert->id, $alertIds);
        $this->assertNotContains($unassignedAlert->id, $alertIds);
    }

    /**
     * Test that admin can see all alerts.
     */
    public function test_admin_can_see_all_alerts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        $site1 = Site::factory()->create(['client_id' => $client1->id]);
        $site2 = Site::factory()->create(['client_id' => $client2->id]);
        
        $alert1 = Alert::factory()->create(['site_id' => $site1->id]);
        $alert2 = Alert::factory()->create(['site_id' => $site2->id]);

        $response = $this->actingAs($admin)->get(route('alerts.index'));
        $response->assertOk();
        
        $pageData = $response->viewData('page');
        $alerts = collect($pageData['props']['alerts']['data'] ?? []);
        $alertIds = $alerts->pluck('id')->toArray();
        
        $this->assertContains($alert1->id, $alertIds);
        $this->assertContains($alert2->id, $alertIds);
    }
}

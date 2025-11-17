<?php
declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_index_requires_authentication(): void
    {
        $response = $this->get(route('team.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_team_index_displays_members(): void
    {
        $user = User::factory()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('team.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('members')
            ->has('stats')
        );
    }

    public function test_team_invite_creates_pending_user(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('team.invite'), [
            'name' => 'New Member',
            'email' => 'new@example.com',
            'role' => 'member',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_team_destroy_removes_member(): void
    {
        $user = User::factory()->create();
        $member = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('team.destroy', $member));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_team_destroy_prevents_self_deletion(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('team.destroy', $user));

        $response->assertForbidden();
    }
}

<?php
declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_edit_requires_authentication(): void
    {
        $response = $this->get(route('profile.edit'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_profile_edit_displays_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('mustVerifyEmail'));
    }

    public function test_profile_update_modifies_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertEquals('Updated Name', $user->fresh()->name);
    }

    public function test_profile_destroy_deletes_user(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $response = $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}


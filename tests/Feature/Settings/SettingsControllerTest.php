<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Modules\Notifications\Models\Webhook;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SettingsControllerTest verifies all settings functionality including
 * profile updates, password changes, webhook management, and test features.
 */
class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that settings index page loads successfully.
     */
    public function test_settings_index_displays_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('settings')
            ->has('settings.name')
            ->has('settings.email')
        );
    }

    /**
     * Test that profile can be updated successfully.
     */
    public function test_settings_update_profile_saves_changes(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.profile'), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'company' => 'Updated Company',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('updated@example.com', $this->user->email);
        $this->assertEquals('Updated Company', $this->user->company);
    }

    /**
     * Test that profile update validates email uniqueness.
     */
    public function test_settings_update_profile_validates_unique_email(): void
    {
        $otherUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->user)
            ->post(route('settings.profile'), [
                'name' => 'Test User',
                'email' => 'existing@example.com',
            ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that preferences can be updated.
     */
    public function test_settings_update_preferences_saves_changes(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.preferences'), [
                'timezone' => 'America/New_York',
                'language' => 'en',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals('America/New_York', $this->user->timezone);
        $this->assertEquals('en', $this->user->language);
    }

    /**
     * Test that password can be updated with correct current password.
     */
    public function test_settings_update_password_with_correct_current_password(): void
    {
        $this->user->update(['password' => Hash::make('oldpassword')]);

        $response = $this->actingAs($this->user)
            ->post(route('settings.password'), [
                'currentPassword' => 'oldpassword',
                'newPassword' => 'newpassword123',
                'newPassword_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /**
     * Test that password update fails with incorrect current password.
     */
    public function test_settings_update_password_fails_with_incorrect_current_password(): void
    {
        $this->user->update(['password' => Hash::make('oldpassword')]);

        $response = $this->actingAs($this->user)
            ->post(route('settings.password'), [
                'currentPassword' => 'wrongpassword',
                'newPassword' => 'newpassword123',
                'newPassword_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $session = session();
        $this->assertStringContainsString('incorrect', $session->get('error'));
    }

    /**
     * Test that webhooks can be created and updated.
     */
    public function test_settings_update_webhooks_creates_webhooks(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.webhooks'), [
                'webhooks' => [
                    [
                        'name' => 'Test Webhook',
                        'url' => 'https://example.com/webhook',
                        'events' => ['alert.created', 'alert.resolved'],
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $webhook = Webhook::where('user_id', $this->user->id)->first();
        $this->assertNotNull($webhook);
        $this->assertEquals('Test Webhook', $webhook->name);
        $this->assertEquals('https://example.com/webhook', $webhook->url);
        $this->assertEquals(['alert.created', 'alert.resolved'], $webhook->events);
    }

    /**
     * Test that webhook update replaces existing webhooks.
     */
    public function test_settings_update_webhooks_replaces_existing(): void
    {
        // Create existing webhook
        Webhook::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Webhook',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('settings.webhooks'), [
                'webhooks' => [
                    [
                        'name' => 'New Webhook',
                        'url' => 'https://example.com/new',
                        'events' => [],
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $webhooks = Webhook::where('user_id', $this->user->id)->get();
        $this->assertCount(1, $webhooks);
        $this->assertEquals('New Webhook', $webhooks->first()->name);
    }

    /**
     * Test that monitoring settings can be updated.
     */
    public function test_settings_update_monitoring_saves_changes(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.monitoring'), [
                'checkInterval' => 10,
                'timeout' => 15,
                'uptimeThreshold' => 99.5,
                'responseTimeThreshold' => 3000,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $settings = $this->user->monitoring_settings;
        $this->assertEquals(10, $settings['checkInterval']);
        $this->assertEquals(15, $settings['timeout']);
        $this->assertEquals(99.5, $settings['uptimeThreshold']);
        $this->assertEquals(3000, $settings['responseTimeThreshold']);
    }

    /**
     * Test that test email sends successfully.
     */
    public function test_settings_test_email_sends_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.test-email'), [
                'template' => 'alert-created',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * Test that test email validates template.
     */
    public function test_settings_test_email_validates_template(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.test-email'), [
                'template' => 'invalid-template',
            ]);

        $response->assertSessionHasErrors('template');
    }

    /**
     * Test that two-factor toggle works.
     */
    public function test_settings_toggle_two_factor_updates_setting(): void
    {
        $this->user->update(['notification_settings' => ['twoFactorEnabled' => false]]);

        $response = $this->actingAs($this->user)
            ->post(route('settings.2fa'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertTrue($this->user->notification_settings['twoFactorEnabled'] ?? false);
    }

    public function test_settings_test_webhook_delivers_payload(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['success' => true], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.test-webhook'), [
                'webhook_url' => 'https://example.com/webhook',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'status_code']);
    }

    public function test_settings_revoke_session_deletes_session(): void
    {
        $sessionId = 'test-session-id';
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $this->user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('settings.sessions.destroy', $sessionId));

        $response->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
    }

    public function test_settings_update_thresholds_saves_monitoring_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.thresholds'), [
                'checkInterval' => 10,
                'timeout' => 15,
                'uptimeThreshold' => 99.5,
                'responseTimeThreshold' => 3000,
            ]);

        $response->assertRedirect();
        $this->user->refresh();
        $settings = $this->user->monitoring_settings;
        $this->assertEquals(10, $settings['checkInterval']);
    }
}


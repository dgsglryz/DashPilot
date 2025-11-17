<?php
declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendErrorLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_error_log_does_not_require_authentication(): void
    {
        $response = $this->postJson('/api/log-frontend-error', [
            'message' => 'Test error',
            'url' => 'https://example.com',
            'userAgent' => 'Test Agent',
            'timestamp' => now()->toIso8601String(),
            'errorType' => 'javascript',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_frontend_error_log_stores_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/log-frontend-error', [
            'message' => 'Test error',
            'url' => 'https://example.com',
            'userAgent' => 'Test Agent',
            'timestamp' => now()->toIso8601String(),
            'errorType' => 'javascript',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_frontend_error_log_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/log-frontend-error', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message', 'url', 'userAgent', 'timestamp', 'errorType']);
    }
}


<?php
declare(strict_types=1);

namespace Tests\Feature\Shopify;

use App\Modules\Clients\Models\Client;
use App\Modules\Shopify\Models\LiquidSnippet;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LiquidEditorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_liquid_editor_index_requires_authentication(): void
    {
        $response = $this->get(route('shopify.editor'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_liquid_editor_index_displays_shopify_sites(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Site::factory()->count(3)->create(['type' => 'shopify', 'client_id' => $client->id]);
        Site::factory()->count(2)->create(['type' => 'wordpress', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('shopify.editor'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('shopifySites', 3)
            ->has('snippetLibrary')
        );
    }

    public function test_liquid_editor_files_returns_file_tree(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['type' => 'shopify', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->getJson(route('shopify.editor.files', $site));

        $response->assertOk()
            ->assertJsonStructure(['files']);
        
        $data = $response->json();
        $this->assertIsArray($data['files']);
    }

    public function test_liquid_editor_files_rejects_non_shopify_site(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['type' => 'wordpress', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->getJson(route('shopify.editor.files', $site));

        $response->assertNotFound();
    }

    public function test_liquid_editor_file_returns_file_content(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['type' => 'shopify', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->getJson(route('shopify.editor.file', $site) . '?path=templates/index.liquid');

        $response->assertOk()
            ->assertJsonStructure(['content']);
    }

    public function test_liquid_editor_file_validates_path(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['type' => 'shopify', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->getJson(route('shopify.editor.file', $site));

        $response->assertJsonValidationErrors(['path']);
    }

    public function test_liquid_editor_save_persists_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $site = Site::factory()->create(['type' => 'shopify', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->postJson(route('shopify.editor.save', $site), [
            'path' => 'templates/index.liquid',
            'content' => '<div>Test content</div>',
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'saved']);
    }

    public function test_liquid_editor_store_snippet_creates_snippet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('shopify.snippets.store'), [
            'title' => 'Test Snippet',
            'category' => 'product',
            'code' => '{% for product in products %}{{ product.title }}{% endfor %}',
            'description' => 'Test description',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['status', 'snippet']);

        $this->assertDatabaseHas('liquid_snippets', [
            'title' => 'Test Snippet',
            'user_id' => $user->id,
        ]);
    }

    public function test_liquid_editor_store_snippet_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('shopify.snippets.store'), []);

        $response->assertJsonValidationErrors(['title', 'category', 'code']);
    }
}


<?php
declare(strict_types=1);

namespace Tests\Unit\Shopify\Services;

use App\Modules\Shopify\Exceptions\ShopifyApiException;
use App\Modules\Shopify\Models\LiquidSnippet;
use App\Modules\Shopify\Services\LiquidEditorService;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for LiquidEditorService.
 */
class LiquidEditorServiceTest extends TestCase
{
    use RefreshDatabase;

    private LiquidEditorService $service;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LiquidEditorService();
        Cache::flush();
    }

    /**
     * Test getting theme files for a Shopify site.
     */
    public function test_get_theme_files_returns_structure(): void
    {
        $site = Site::factory()->create([
            'shopify_store_url' => 'https://test.myshopify.com',
        ]);

        $files = $this->service->getThemeFiles($site);

        $this->assertIsArray($files);
        $this->assertNotEmpty($files);
        $this->assertArrayHasKey('name', $files[0]);
        $this->assertArrayHasKey('type', $files[0]);
    }

    /**
     * Test getting theme files returns empty array when store URL is missing.
     */
    public function test_get_theme_files_returns_empty_when_no_store_url(): void
    {
        $site = Site::factory()->create([
            'shopify_store_url' => null,
        ]);

        $files = $this->service->getThemeFiles($site);

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    /**
     * Test getting theme files is cached.
     */
    public function test_get_theme_files_is_cached(): void
    {
        $site = Site::factory()->create([
            'shopify_store_url' => 'https://test.myshopify.com',
        ]);

        $first = $this->service->getThemeFiles($site);
        $second = $this->service->getThemeFiles($site);

        $this->assertSame($first, $second);
    }

    /**
     * Test getting file content.
     */
    public function test_get_file_content_returns_content(): void
    {
        $site = Site::factory()->create([
            'shopify_store_url' => 'https://test.myshopify.com',
        ]);

        $content = $this->service->getFileContent($site, 'templates/index.liquid');

        $this->assertIsString($content);
        $this->assertNotEmpty($content);
    }

    /**
     * Test getting file content throws exception when store URL is missing.
     */
    public function test_get_file_content_throws_exception_when_no_store_url(): void
    {
        $this->expectException(ShopifyApiException::class);

        $site = Site::factory()->create([
            'shopify_store_url' => null,
        ]);

        $this->service->getFileContent($site, 'templates/index.liquid');
    }

    /**
     * Test getting file content is cached.
     */
    public function test_get_file_content_is_cached(): void
    {
        $site = Site::factory()->create([
            'shopify_store_url' => 'https://test.myshopify.com',
        ]);

        $first = $this->service->getFileContent($site, 'templates/index.liquid');
        $second = $this->service->getFileContent($site, 'templates/index.liquid');

        $this->assertSame($first, $second);
    }

    /**
     * Test saving file content.
     */
    public function test_save_file_content_stores_content(): void
    {
        $site = Site::factory()->create([
            'shopify_store_url' => 'https://test.myshopify.com',
        ]);

        $content = '{% comment %} Test content {% endcomment %}';
        $this->service->saveFileContent($site, 'templates/test.liquid', $content);

        // Verify content is cached
        $cached = Cache::get(sprintf('shopify.%d.file.%s', $site->id, hash('sha256', 'templates/test.liquid')));
        $this->assertEquals($content, $cached);
    }

    /**
     * Test saving file content throws exception when store URL is missing.
     */
    public function test_save_file_content_throws_exception_when_no_store_url(): void
    {
        $this->expectException(ShopifyApiException::class);

        $site = Site::factory()->create([
            'shopify_store_url' => null,
        ]);

        $this->service->saveFileContent($site, 'templates/test.liquid', 'content');
    }

    /**
     * Test getting snippets.
     */
    public function test_get_snippets_returns_collection(): void
    {
        LiquidSnippet::factory()->count(3)->create();

        $snippets = $this->service->getSnippets();

        $this->assertCount(3, $snippets);
    }

    /**
     * Test saving snippet creates new snippet.
     */
    public function test_save_snippet_creates_new(): void
    {
        $user = \App\Modules\Users\Models\User::factory()->create();
        
        $data = [
            'name' => 'test-snippet',
            'description' => 'Test description',
            'code' => '{% comment %} Test {% endcomment %}',
            'category' => 'general',
            'user_id' => $user->id,
        ];

        $snippet = $this->service->saveSnippet($data);

        $this->assertInstanceOf(LiquidSnippet::class, $snippet);
        $this->assertEquals('test-snippet', $snippet->title);
        $this->assertEquals('Test description', $snippet->description);
    }

    /**
     * Test saving snippet updates existing snippet.
     */
    public function test_save_snippet_updates_existing(): void
    {
        $user = \App\Modules\Users\Models\User::factory()->create();
        $existing = LiquidSnippet::factory()->create([
            'title' => 'test-snippet',
            'code' => 'old code',
            'user_id' => $user->id,
        ]);

        $data = [
            'name' => 'test-snippet',
            'code' => 'new code',
            'user_id' => $user->id,
        ];

        $snippet = $this->service->saveSnippet($data);

        $this->assertEquals($existing->id, $snippet->id);
        $this->assertEquals('new code', $snippet->code);
    }

    /**
     * Test deleting snippet.
     */
    public function test_delete_snippet_removes_snippet(): void
    {
        $snippet = LiquidSnippet::factory()->create();

        $this->service->deleteSnippet($snippet);

        $this->assertDatabaseMissing('liquid_snippets', ['id' => $snippet->id]);
    }
}


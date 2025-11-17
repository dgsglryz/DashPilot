<?php
declare(strict_types=1);

namespace App\Modules\Shopify\Services;

use App\Modules\Shopify\Models\LiquidSnippet;
use App\Modules\Sites\Models\Site;
use App\Shared\Services\LoggingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * LiquidEditorService manages Shopify Liquid theme file operations and snippets.
 */
class LiquidEditorService
{
    /**
     * Get theme files structure for a Shopify site.
     * This is a mock implementation that returns a file tree structure.
     *
     * @param Site $site The Shopify site
     * @return array<string, mixed> File tree structure
     */
    public function getThemeFiles(Site $site): array
    {
        if (empty($site->shopify_store_url)) {
            return [];
        }

        $cacheKey = sprintf('shopify.%d.theme_files', $site->id);

        return Cache::remember($cacheKey, 3600, function () use ($site) {
            $logger = app(LoggingService::class);
            $logger->logServiceMethod(LiquidEditorService::class, 'getThemeFiles', [
                'site_id' => $site->id,
            ]);

            // Mock theme structure for demo purposes
            // In production, this would call Shopify Admin API to get actual theme files
            return [
                [
                    'name' => 'templates',
                    'type' => 'directory',
                    'path' => 'templates',
                    'children' => [
                        [
                            'name' => 'index.liquid',
                            'type' => 'file',
                            'path' => 'templates/index.liquid',
                        ],
                        [
                            'name' => 'product.liquid',
                            'type' => 'file',
                            'path' => 'templates/product.liquid',
                        ],
                        [
                            'name' => 'collection.liquid',
                            'type' => 'file',
                            'path' => 'templates/collection.liquid',
                        ],
                    ],
                ],
                [
                    'name' => 'sections',
                    'type' => 'directory',
                    'path' => 'sections',
                    'children' => [
                        [
                            'name' => 'header.liquid',
                            'type' => 'file',
                            'path' => 'sections/header.liquid',
                        ],
                        [
                            'name' => 'footer.liquid',
                            'type' => 'file',
                            'path' => 'sections/footer.liquid',
                        ],
                    ],
                ],
                [
                    'name' => 'snippets',
                    'type' => 'directory',
                    'path' => 'snippets',
                    'children' => [
                        [
                            'name' => 'product-card.liquid',
                            'type' => 'file',
                            'path' => 'snippets/product-card.liquid',
                        ],
                    ],
                ],
                [
                    'name' => 'layout',
                    'type' => 'directory',
                    'path' => 'layout',
                    'children' => [
                        [
                            'name' => 'theme.liquid',
                            'type' => 'file',
                            'path' => 'layout/theme.liquid',
                        ],
                    ],
                ],
            ];
        });
    }

    /**
     * Get file content from theme.
     *
     * @param Site $site The Shopify site
     * @param string $filePath File path (e.g., 'templates/index.liquid')
     * @return string File content
     */
    public function getFileContent(Site $site, string $filePath): string
    {
        if (empty($site->shopify_store_url)) {
            throw new \RuntimeException('Shopify store URL is not configured for this site.');
        }

        // Using SHA256 instead of MD5 for cache key (better security practice)
        $cacheKey = sprintf('shopify.%d.file.%s', $site->id, hash('sha256', $filePath));

        return Cache::remember($cacheKey, 300, function () use ($site, $filePath) {
            $logger = app(LoggingService::class);
            $logger->logServiceMethod(LiquidEditorService::class, 'getFileContent', [
                'site_id' => $site->id,
                'file_path' => $filePath,
            ]);

            // Mock file content for demo purposes
            // In production, this would call Shopify Admin API to get actual file content
            return match (basename($filePath)) {
                'index.liquid' => $this->getDefaultIndexLiquid(),
                'product.liquid' => $this->getDefaultProductLiquid(),
                'product-card.liquid' => $this->getDefaultProductCardLiquid(),
                default => "<!-- File: {$filePath} -->\n{% comment %} Edit this file {% endcomment %}",
            };
        });
    }

    /**
     * Save file content to theme.
     * This is a mock implementation that stores changes in cache.
     *
     * @param Site $site The Shopify site
     * @param string $filePath File path
     * @param string $content File content
     * @return void
     */
    public function saveFileContent(Site $site, string $filePath, string $content): void
    {
        if (empty($site->shopify_store_url)) {
            throw new \RuntimeException('Shopify store URL is not configured for this site.');
        }

        $logger = app(LoggingService::class);
        $logger->logServiceMethod(LiquidEditorService::class, 'saveFileContent', [
            'site_id' => $site->id,
            'file_path' => $filePath,
        ]);

        // In production, this would call Shopify Admin API to update the file
        // For demo, we just cache it
        // Using SHA256 instead of MD5 for cache key (better security practice)
        $cacheKey = sprintf('shopify.%d.file.%s', $site->id, hash('sha256', $filePath));
        Cache::put($cacheKey, $content, 3600);

            $logger->logServiceMethod(LiquidEditorService::class, 'saveFileContent', [
                'site_id' => $site->id,
                'file_path' => $filePath,
                'action' => 'file_saved',
            ]);
    }

    /**
     * Get all saved Liquid snippets.
     *
     * @return Collection<int, LiquidSnippet>
     */
    public function getSnippets(): Collection
    {
        return LiquidSnippet::orderBy('name')->get();
    }

    /**
     * Create or update a Liquid snippet.
     *
     * @param array<string, mixed> $data Snippet data (name, description, code)
     * @return LiquidSnippet The created/updated snippet
     */
    public function saveSnippet(array $data): LiquidSnippet
    {
        return LiquidSnippet::updateOrCreate(
            ['name' => $data['name']],
            [
                'description' => $data['description'] ?? null,
                'code' => $data['code'],
                'category' => $data['category'] ?? 'general',
            ]
        );
    }

    /**
     * Delete a Liquid snippet.
     *
     * @param LiquidSnippet $snippet The snippet to delete
     * @return void
     */
    public function deleteSnippet(LiquidSnippet $snippet): void
    {
        $snippet->delete();
    }

    /**
     * Get default index.liquid template content.
     *
     * @return string Default template code
     */
    private function getDefaultIndexLiquid(): string
    {
        return <<<'LIQUID'
{% comment %}
  The main index template file
{% endcomment %}

<div class="collection-page">
  {% if collection.products.size > 0 %}
    {% for product in collection.products %}
      {% render 'product-card', product: product %}
    {% endfor %}
  {% else %}
    <p>No products found.</p>
  {% endif %}
</div>
LIQUID;
    }

    /**
     * Get default product.liquid template content.
     *
     * @return string Default template code
     */
    private function getDefaultProductLiquid(): string
    {
        return <<<'LIQUID'
{% comment %}
  Product template
{% endcomment %}

<div class="product-page">
  <h1>{{ product.title }}</h1>
  
  {% if product.available %}
    <p class="price">{{ product.price | money }}</p>
    <button>Add to Cart</button>
  {% else %}
    <p>Out of Stock</p>
  {% endif %}
  
  <div class="description">
    {{ product.description }}
  </div>
</div>
LIQUID;
    }

    /**
     * Get default product-card.liquid snippet content.
     *
     * @return string Default snippet code
     */
    private function getDefaultProductCardLiquid(): string
    {
        return <<<'LIQUID'
{% comment %}
  Product card snippet
{% endcomment %}

<div class="product-card">
  <a href="{{ product.url }}">
    <img src="{{ product.featured_image | img_url: 'medium' }}" alt="{{ product.title }}">
    <h3>{{ product.title }}</h3>
    <p class="price">{{ product.price | money }}</p>
  </a>
</div>
LIQUID;
    }
}


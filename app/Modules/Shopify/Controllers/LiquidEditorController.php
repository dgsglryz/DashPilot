<?php
declare(strict_types=1);

namespace App\Modules\Shopify\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shopify\Models\LiquidSnippet;
use App\Modules\Sites\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LiquidEditorController emulates Shopify theme editing with local storage.
 */
class LiquidEditorController extends Controller
{
    /**
     * Render the editor shell.
     */
    public function index(): Response
    {
        $sites = Site::where('type', 'shopify')
            ->orderBy('name')
            ->get(['id', 'name', 'url']);

        return Inertia::render('Shopify/Pages/LiquidEditor', [
            'shopifySites' => $sites,
            'snippetLibrary' => LiquidSnippet::latest()->take(25)->get(),
        ]);
    }

    /**
     * Return the file tree for the selected site.
     */
    public function files(Site $site): JsonResponse
    {
        $this->ensureShopifySite($site);

        return response()->json([
            'files' => $this->fileTree($site),
        ]);
    }

    /**
     * Return the file contents for editing.
     */
    public function file(Request $request, Site $site): JsonResponse
    {
        $this->ensureShopifySite($site);

        $request->validate([
            'path' => ['required', 'string'],
        ]);

        return response()->json([
            'content' => $this->readFile($site, $request->string('path')->toString()),
        ]);
    }

    /**
     * Persist editor changes to disk.
     */
    public function save(Request $request, Site $site): JsonResponse
    {
        $this->ensureShopifySite($site);

        $data = $request->validate([
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
        ]);

        $this->writeFile($site, $data['path'], $data['content']);

        return response()->json(['status' => 'saved']);
    }

    /**
     * Store a custom reusable snippet.
     */
    public function storeSnippet(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $snippet = LiquidSnippet::create([
            ...$data,
            'user_id' => Auth::id(),
            'is_public' => false,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'saved', 'snippet' => $snippet]);
        }

        return back()->with('success', 'Snippet saved.');
    }

    /**
     * Build a synthetic file tree for the UI.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fileTree(Site $site): array
    {
        $files = collect([
            'layout/theme.liquid',
            'templates/index.liquid',
            'templates/product.json',
            'sections/featured-products.liquid',
            'sections/header.liquid',
            'snippets/product-card.liquid',
            'snippets/price.liquid',
            'assets/theme.js',
            'assets/theme.css',
        ]);

        return $files
            ->map(function (string $path) {
                [$folder, $name] = explode('/', $path, 2);

                return [
                    'folder' => $folder,
                    'name' => $name,
                    'path' => $path,
                    'type' => 'file',
                ];
            })
            ->groupBy('folder')
            ->map(function ($items, $folder) {
                return [
                    'name' => $folder,
                    'path' => $folder,
                    'type' => 'folder',
                    'children' => $items->map(fn ($item) => [
                        'name' => $item['name'],
                        'path' => $item['path'],
                        'type' => 'file',
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function storagePath(Site $site, string $path): string
    {
        $safePath = str_replace(['/', '\\'], '_', $path);

        return "shopify/{$site->id}/{$safePath}";
    }

    private function readFile(Site $site, string $path): string
    {
        $storagePath = $this->storagePath($site, $path);

        if (Storage::disk('local')->missing($storagePath)) {
            $content = $this->stubFile($path, $site);
            Storage::disk('local')->put($storagePath, $content);

            return $content;
        }

        return Storage::disk('local')->get($storagePath);
    }

    private function writeFile(Site $site, string $path, string $content): void
    {
        Storage::disk('local')->put($this->storagePath($site, $path), $content);
    }

    private function stubFile(string $path, Site $site): string
    {
        $comment = sprintf(
            '{%% comment %%} Draft generated for %s (%s) {%% endcomment %%}',
            $site->name,
            $path
        );

        return $comment.PHP_EOL.PHP_EOL.'<div class="placeholder">Update content here</div>';
    }

    private function ensureShopifySite(Site $site): void
    {
        abort_unless($site->type === 'shopify', 404);
    }
}


<?php
declare(strict_types=1);

namespace Tests\Unit\Sites;

use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Ensure sensitive site credentials are encrypted while degrading gracefully for legacy data.
 */
class SiteCredentialEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_and_decrypted_transparently(): void
    {
        $site = Site::factory()->create([
            'wp_api_key' => 'plain-token',
            'shopify_access_token' => 'shopify-token',
        ]);

        $this->assertNotSame('plain-token', $site->getRawOriginal('wp_api_key'));
        $this->assertNotSame('shopify-token', $site->getRawOriginal('shopify_access_token'));
        $this->assertSame('plain-token', $site->wp_api_key);
        $this->assertSame('shopify-token', $site->shopify_access_token);
    }

    public function test_invalid_cipher_is_ignored_and_logged(): void
    {
        $site = Site::factory()->create([
            'wp_api_key' => 'plain-token',
        ]);

        // Simulate legacy data written with a different APP_KEY or without encryption.
        Site::query()->whereKey($site->id)->update(['wp_api_key' => 'legacy-value']);

        Log::spy();

        $fresh = $site->fresh();

        $this->assertNull($fresh->wp_api_key);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($site): bool {
                return $message === 'Failed to decrypt site credential.'
                    && $context['site_id'] === $site->id
                    && $context['column'] === 'wp_api_key';
            });
    }
}




<?php
declare(strict_types=1);

namespace App\Shared\Helpers;

use Illuminate\Support\Str;

/**
 * SiteMediaHelper centralizes placeholder thumbnail and logo generation.
 */
final class SiteMediaHelper
{
    /**
     * Build a deterministic placeholder thumbnail URL.
     *
     * @param int $siteId
     * @param string $context
     *
     * @return string
     */
    public static function thumbnail(int $siteId, string $context = 'site'): string
    {
        return "https://picsum.photos/seed/{$context}-{$siteId}/640/360";
    }

    /**
     * Build a deterministic placeholder logo using DiceBear initials.
     *
     * @param string $name
     * @param string $background
     *
     * @return string
     */
    public static function logo(string $name, string $background = '111827,1c1f2b'): string
    {
        $seed = Str::slug($name);

        return "https://api.dicebear.com/7.x/initials/svg?seed={$seed}&backgroundColor={$background}&fontSize=60";
    }
}


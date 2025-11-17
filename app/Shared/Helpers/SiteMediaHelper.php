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
        $variants = [
            '/images/placeholders/site-card-1.svg',
            '/images/placeholders/site-card-2.svg',
            '/images/placeholders/site-card-3.svg',
            '/images/placeholders/site-card-4.svg',
        ];

        $index = abs($siteId + strlen($context)) % count($variants);

        return asset($variants[$index]);
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
        $initials = collect(explode(' ', trim($name)))
            ->filter()
            ->map(fn (string $segment) => Str::upper(Str::substr($segment, 0, 1)))
            ->take(2)
            ->implode('') ?: 'DP';

        $palette = array_filter(explode(',', $background)) ?: ['111827', '1f2937'];
        $accent = $palette[abs(crc32($name)) % count($palette)];

        $svg = <<<SVG
<svg width="160" height="160" viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
  <rect width="160" height="160" rx="32" fill="#{$accent}"/>
  <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="72" font-weight="600" fill="#f8fafc">{$initials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}


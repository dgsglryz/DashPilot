<?php
declare(strict_types=1);

namespace App\Modules\Shopify\Exceptions;

use RuntimeException;

/**
 * ShopifyApiException surfaces upstream REST/GraphQL failures with context.
 */
class ShopifyApiException extends RuntimeException
{
}


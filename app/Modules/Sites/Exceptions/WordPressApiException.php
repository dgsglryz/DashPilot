<?php
declare(strict_types=1);

namespace App\Modules\Sites\Exceptions;

use RuntimeException;

/**
 * WordPressApiException wraps upstream WordPress REST failures with domain context.
 */
class WordPressApiException extends RuntimeException
{
}


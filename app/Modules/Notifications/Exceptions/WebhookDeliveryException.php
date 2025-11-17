<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Exceptions;

use RuntimeException;

/**
 * WebhookDeliveryException is thrown when webhook delivery fails.
 */
class WebhookDeliveryException extends RuntimeException
{
}


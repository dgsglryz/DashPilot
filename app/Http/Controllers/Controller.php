<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller class providing common functionality for all application controllers.
 */
abstract class Controller
{
    use AuthorizesRequests;
}

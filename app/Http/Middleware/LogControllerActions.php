<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Shared\Services\LoggingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LogControllerActions middleware logs all controller actions for debugging.
 */
class LogControllerActions
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000;

        // Only log in non-production or when explicitly enabled
        if (config('app.debug') || env('LOG_CONTROLLER_ACTIONS', false)) {
            $route = $request->route();

            if ($route && $route->getActionName() !== 'Closure') {
                $controller = $route->getController();
                $action = $route->getActionMethod();

                if ($controller) {
                    app(LoggingService::class)->logControllerAction(
                        get_class($controller),
                        $action,
                        [
                            'route' => $route->getName(),
                            'method' => $request->method(),
                            'url' => $request->fullUrl(),
                            'status_code' => $response->getStatusCode(),
                            'duration_ms' => round($duration, 2),
                        ]
                    );
                }
            }
        }

        return $response;
    }
}


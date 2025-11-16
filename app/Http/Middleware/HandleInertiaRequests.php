<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Shared\Services\LoggingService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Inertia;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
        ];
    }

    /**
     * Handle Inertia errors.
     *
     * @param \Throwable $e
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleError(\Throwable $e, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        // Log Inertia errors
        if (app()->bound(LoggingService::class)) {
            app(LoggingService::class)->logException($e, [
                'inertia_error' => true,
                'url' => $request->fullUrl(),
                'component' => $request->header('X-Inertia-Partial-Component'),
            ]);
        }

        return parent::handleError($e, $request);
    }
}

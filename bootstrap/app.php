<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\LogControllerActions::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log all exceptions
        $exceptions->report(function (\Throwable $e): void {
            if (app()->bound(\App\Shared\Services\LoggingService::class)) {
                app(\App\Shared\Services\LoggingService::class)->logException($e);
            }
        });
    })->create();

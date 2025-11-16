<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Shared\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FrontendErrorLogController handles frontend error logging from JavaScript/Vue.
 */
class FrontendErrorLogController
{
    /**
     * Log a frontend error.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'stack' => 'nullable|string|max:5000',
            'component' => 'nullable|string|max:255',
            'props' => 'nullable|array',
            'url' => 'required|string|max:500',
            'userAgent' => 'required|string|max:500',
            'timestamp' => 'required|string',
            'errorType' => 'required|string|in:javascript,vue,inertia,unhandled_rejection',
        ]);

        $logger = app(LoggingService::class);

        // Log as error with full context
        Log::error("Frontend Error: {$validated['errorType']}", [
            'message' => $validated['message'],
            'stack' => $validated['stack'] ?? null,
            'component' => $validated['component'] ?? null,
            'url' => $validated['url'],
            'user_agent' => $validated['userAgent'],
            'timestamp' => $validated['timestamp'],
            'error_type' => $validated['errorType'],
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'props' => $validated['props'] ?? null,
        ]);

        // Also log via LoggingService for structured logging
        $logger->logException(
            new \RuntimeException("Frontend {$validated['errorType']} error: {$validated['message']}"),
            [
                'frontend_error' => true,
                'error_type' => $validated['errorType'],
                'component' => $validated['component'] ?? null,
                'stack' => $validated['stack'] ?? null,
                'url' => $validated['url'],
                'user_agent' => $validated['userAgent'],
                'props' => $validated['props'] ?? null,
            ]
        );

        return response()->json(['success' => true], 201);
    }
}


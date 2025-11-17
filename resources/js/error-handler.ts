/**
 * Global error handler for frontend errors.
 * Logs all JavaScript errors, Vue errors, and Inertia errors to the backend.
 */

interface ErrorLogPayload {
    message: string;
    stack?: string;
    component?: string;
    props?: Record<string, unknown>;
    url: string;
    userAgent: string;
    timestamp: string;
    errorType: 'javascript' | 'vue' | 'inertia' | 'unhandled_rejection';
}

/**
 * Send error to backend logging endpoint.
 */
async function logErrorToBackend(payload: ErrorLogPayload): Promise<void> {
    try {
        await globalThis.axios.post('/api/log-frontend-error', payload, {
            timeout: 5000,
        });
    } catch (e) {
        // Silently fail - don't log logging errors
        console.error('Failed to log error to backend:', e);
    }
}

/**
 * Handle JavaScript errors.
 */
function handleJavaScriptError(event: ErrorEvent): void {
    const payload: ErrorLogPayload = {
        message: event.message || 'Unknown JavaScript error',
        stack: event.error?.stack,
        url: globalThis.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
        errorType: 'javascript',
    };

    logErrorToBackend(payload);
}

/**
 * Handle unhandled promise rejections.
 */
function handleUnhandledRejection(event: PromiseRejectionEvent): void {
    const payload: ErrorLogPayload = {
        message: event.reason?.message || String(event.reason) || 'Unhandled promise rejection',
        stack: event.reason?.stack,
        url: globalThis.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
        errorType: 'unhandled_rejection',
    };

    logErrorToBackend(payload);
}

/**
 * Vue error handler.
 */
export function handleVueError(err: Error, instance: unknown, info: string): void {
    // Type-safe component name extraction
    const componentName = (instance && typeof instance === 'object' && '$options' in instance)
        ? ((instance as { $options?: { name?: string; __name?: string } }).$options?.name 
            || (instance as { $options?: { __name?: string } }).$options?.__name)
        : 'Unknown Component';

    const payload: ErrorLogPayload = {
        message: err.message || 'Unknown Vue error',
        stack: err.stack,
        component: componentName || 'Unknown Component',
        props: (instance && typeof instance === 'object' && '$props' in instance)
            ? (instance as { $props?: Record<string, unknown> }).$props as Record<string, unknown>
            : undefined,
        url: globalThis.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
        errorType: 'vue',
    };

    // Add Vue error info
    if (info) {
        payload.message = `${info}: ${payload.message}`;
    }

    logErrorToBackend(payload);
}

/**
 * Inertia error handler.
 */
export function handleInertiaError(err: Error, page: { component?: string; props?: Record<string, unknown> } | null): void {
    const payload: ErrorLogPayload = {
        message: err.message || 'Unknown Inertia error',
        stack: err.stack,
        component: page?.component,
        props: page?.props,
        url: globalThis.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
        errorType: 'inertia',
    };

    logErrorToBackend(payload);
}

/**
 * Initialize global error handlers.
 */
export function initializeErrorHandlers(): void {
    // JavaScript errors
    globalThis.addEventListener('error', handleJavaScriptError);

    // Unhandled promise rejections
    globalThis.addEventListener('unhandledrejection', handleUnhandledRejection);

    // Console error override (for debugging)
    if (import.meta.env.DEV) {
        const originalError = console.error;
        console.error = (...args: unknown[]) => {
            originalError.apply(console, args);
            // Log console errors in development
            if (args[0] instanceof Error) {
                handleJavaScriptError({
                    message: args[0].message,
                    error: args[0],
                } as ErrorEvent);
            }
        };
    }
}


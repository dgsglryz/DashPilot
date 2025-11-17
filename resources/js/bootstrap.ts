import axios, { AxiosResponse, AxiosError } from 'axios';
globalThis.axios = axios;

globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Axios response interceptor for error logging
globalThis.axios.interceptors.response.use(
    (response: AxiosResponse) => response,
    async (error: AxiosError) => {
        // Log HTTP errors to backend
        if (error.response) {
            const status = error.response.status;
            
            // Only log 4xx and 5xx errors (not 401/403 which are expected auth errors)
            if (status >= 400 && status !== 401 && status !== 403) {
                try {
                    await globalThis.axios.post('/api/log-frontend-error', {
                        message: `HTTP ${status}: ${error.response.statusText}`,
                        stack: error.stack,
                        url: error.config?.url || globalThis.location.href,
                        userAgent: navigator.userAgent,
                        timestamp: new Date().toISOString(),
                        errorType: 'javascript',
                        props: {
                            status,
                            statusText: error.response.statusText,
                            data: error.response.data,
                            method: error.config?.method,
                            headers: error.config?.headers,
                        },
                    });
                } catch (e) {
                    // Silently fail - don't log logging errors
                    if (import.meta.env.DEV) {
                        console.error('Failed to log HTTP error:', e);
                    }
                }
            }
        }
        
        throw error;
    }
);

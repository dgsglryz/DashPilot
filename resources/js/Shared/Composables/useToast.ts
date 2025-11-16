/**
 * useToast composable provides a convenient wrapper for vue-toastification
 * to show success, error, info, and warning notifications throughout the app.
 */
import { useToast as useVueToast } from 'vue-toastification';
import type { ToastOptions } from 'vue-toastification';

/**
 * Toast notification composable
 * @returns Toast instance with helper methods
 */
export function useToast() {
    const toast = useVueToast();

    return {
        /**
         * Show success notification
         * @param message - Success message
         * @param options - Optional toast options
         */
        success: (message: string, options?: ToastOptions) => {
            return toast.success(message, {
                timeout: 3000,
                ...options,
            });
        },

        /**
         * Show error notification
         * @param message - Error message
         * @param options - Optional toast options
         */
        error: (message: string, options?: ToastOptions) => {
            return toast.error(message, {
                timeout: 5000,
                ...options,
            });
        },

        /**
         * Show info notification
         * @param message - Info message
         * @param options - Optional toast options
         */
        info: (message: string, options?: ToastOptions) => {
            return toast.info(message, {
                timeout: 3000,
                ...options,
            });
        },

        /**
         * Show warning notification
         * @param message - Warning message
         * @param options - Optional toast options
         */
        warning: (message: string, options?: ToastOptions) => {
            return toast.warning(message, {
                timeout: 4000,
                ...options,
            });
        },
    };
}


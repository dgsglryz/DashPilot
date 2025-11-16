import '../css/app.css';
import '../scss/app.scss';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import { InertiaProgress } from '@inertiajs/progress';
import { initializeErrorHandlers, handleVueError } from './error-handler';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pages = import.meta.glob<DefineComponent>('./Modules/**/*.vue');

// Initialize global error handlers (JavaScript errors, unhandled rejections)
initializeErrorHandlers();

// Configure Inertia Progress Bar
InertiaProgress.init({
    color: '#3B82F6',
    includeCSS: true,
    showSpinner: true,
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(resolveModulePath(name), pages),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Register Vue error handler
        app.config.errorHandler = (err, instance, info) => {
            handleVueError(err as Error, instance, info);
        };
        
        // Register Toast notifications
        app.use(Toast, {
            transition: 'Vue-Toastification__bounce',
            maxToasts: 5,
            newestOnTop: true,
            position: 'top-right',
            timeout: 3000,
            closeOnClick: true,
            pauseOnFocusLoss: true,
            pauseOnHover: true,
            draggable: true,
            draggablePercent: 0.6,
            showCloseButtonOnHover: false,
            hideProgressBar: false,
            closeButton: 'button',
            icon: true,
            rtl: false,
        });
        
        app.use(plugin);
        app.use(ZiggyVue);
        app.mount(el);
    },
    progress: {
        color: '#3B82F6',
    },
});

/**
 * Resolve the module path for an Inertia page component.
 * Tries multiple common paths to locate the Vue component file.
 *
 * @param name - The page component name
 * @returns The resolved path to the component
 * @throws Error if the page component is not found
 */
function resolveModulePath(name: string): string {
    const guesses = [
        `./Modules/${name}.vue`,
        `./Modules/Core/Pages/${name}.vue`,
    ];

    const match = guesses.find((path) => pages[path] !== undefined);

    if (!match) {
        throw new Error(`Page not found: ${name}`);
    }

    return match;
}

import '../css/app.css';
import '../scss/app.scss';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pages = import.meta.glob<DefineComponent>('./Modules/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(resolveModulePath(name), pages),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

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

/**
 * useDarkMode composable provides dark mode toggle functionality with localStorage persistence.
 * Manages dark mode state and applies 'dark' class to document.documentElement.
 * Uses singleton pattern to ensure consistent state across all components.
 */
import { ref, watch, onMounted } from 'vue';

const DARK_MODE_CLASS = 'dark';
const THEME_STORAGE_KEY = 'dashpilot.theme';

// Singleton state - shared across all instances
const isDark = ref<boolean>(false);
let initialized = false;

/**
 * Initialize dark mode by reading persisted preference or system preference.
 */
const initDarkMode = (): void => {
    if (initialized) {
        return;
    }

    if (typeof window === 'undefined' || typeof document === 'undefined') {
        // In SSR environments we simply mark initialization to avoid re-running.
        initialized = true;
        return;
    }

    try {
        const storedPreference = window.localStorage?.getItem(THEME_STORAGE_KEY);

        if (storedPreference === 'dark') {
            isDark.value = true;
        } else if (storedPreference === 'light') {
            isDark.value = false;
        } else {
            // Default to dark mode when no preference has been saved yet.
            isDark.value = true;
        }
    } catch {
        // If localStorage is unavailable we default to dark mode to preserve app theme.
        isDark.value = true;
    }

    initialized = true;
    applyDarkMode();
};

/**
 * Apply the current dark mode state to the DOM and persist the preference.
 */
const applyDarkMode = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    const body = document.body;

    if (isDark.value) {
        root.classList.add(DARK_MODE_CLASS);
        root.style.colorScheme = 'dark';
        body?.classList.add('bg-gray-950');
        body?.classList.remove('bg-white');
    } else {
        root.classList.remove(DARK_MODE_CLASS);
        root.style.colorScheme = 'light';
        body?.classList.remove('bg-gray-950');
        body?.classList.add('bg-white');
    }

    if (typeof window !== 'undefined' && window.localStorage) {
        try {
            window.localStorage.setItem(THEME_STORAGE_KEY, isDark.value ? 'dark' : 'light');
        } catch {
            // Silently ignore storage issues to avoid breaking the UI.
        }
    }
};

/**
 * Toggle dark mode.
 */
const toggleDarkMode = (): void => {
    isDark.value = !isDark.value;
    applyDarkMode();
};

/**
 * Set dark mode state explicitly.
 */
const setDarkMode = (value: boolean): void => {
    isDark.value = value;
    applyDarkMode();
};

export function useDarkMode() {
    // Initialize immediately if in browser
    if (typeof window !== 'undefined' && !initialized) {
        initDarkMode();
    }

    // Initialize on mount as well (for SSR safety)
    onMounted(() => {
        if (!initialized) {
            initDarkMode();
        }
    });

    // Watch for changes and apply immediately
    watch(isDark, () => {
        applyDarkMode();
    }, { immediate: true });

    return {
        isDark,
        toggleDarkMode,
        setDarkMode,
    };
}


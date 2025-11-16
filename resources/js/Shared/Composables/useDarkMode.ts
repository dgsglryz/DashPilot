/**
 * useDarkMode composable provides dark mode toggle functionality with localStorage persistence.
 * Manages dark mode state and applies 'dark' class to document.documentElement.
 * Uses singleton pattern to ensure consistent state across all components.
 */
import { ref, watch, onMounted } from 'vue';

// Singleton state - shared across all instances
const isDark = ref<boolean>(false);
let initialized = false;

/**
 * Initialize dark mode - always dark mode for this app
 */
const initDarkMode = (): void => {
    if (typeof window === 'undefined' || initialized) return;
    
    initialized = true;
    // Always dark mode - no toggle needed
    isDark.value = true;
    
    applyDarkMode();
};

/**
 * Apply dark mode class to document element - always dark
 */
const applyDarkMode = (): void => {
    if (typeof document === 'undefined') return;
    
    // Always apply dark mode
    document.documentElement.classList.add('dark');
    document.documentElement.style.colorScheme = 'dark';
    document.body.classList.remove('bg-white');
    document.body.classList.add('bg-gray-950');
};

/**
 * Toggle dark mode
 */
const toggleDarkMode = (): void => {
    isDark.value = !isDark.value;
    applyDarkMode();
};

/**
 * Set dark mode state
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
    
    // Watch for changes and apply
    watch(isDark, () => {
        applyDarkMode();
    }, { immediate: true });
    
    return {
        isDark,
        toggleDarkMode,
        setDarkMode,
    };
}


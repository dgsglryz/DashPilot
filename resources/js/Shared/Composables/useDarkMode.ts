/**
 * useDarkMode composable provides dark mode toggle functionality with localStorage persistence.
 * Manages dark mode state and applies 'dark' class to document.documentElement.
 */
import { ref, watch, onMounted } from 'vue';

const isDark = ref<boolean>(false);

/**
 * Initialize dark mode from localStorage or system preference
 */
const initDarkMode = (): void => {
    if (typeof window === 'undefined') return;
    
    const stored = localStorage.getItem('darkMode');
    
    if (stored !== null) {
        isDark.value = stored === 'true';
    } else {
        // Check system preference
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    
    applyDarkMode();
};

/**
 * Apply dark mode class to document element
 */
const applyDarkMode = (): void => {
    if (typeof document === 'undefined') return;
    
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem('darkMode', String(isDark.value));
    }
};

/**
 * Toggle dark mode
 */
const toggleDarkMode = (): void => {
    isDark.value = !isDark.value;
};

/**
 * Set dark mode state
 */
const setDarkMode = (value: boolean): void => {
    isDark.value = value;
};

export function useDarkMode() {
    // Initialize on mount
    onMounted(() => {
        initDarkMode();
    });
    
    // Initialize immediately if in browser
    if (typeof window !== 'undefined') {
        initDarkMode();
    }
    
    // Watch for changes and apply
    watch(isDark, () => {
        applyDarkMode();
    });
    
    return {
        isDark,
        toggleDarkMode,
        setDarkMode,
    };
}


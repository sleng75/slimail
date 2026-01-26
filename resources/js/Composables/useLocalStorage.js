/**
 * useLocalStorage Composable
 * Reactive localStorage with automatic JSON serialization
 */

import { ref, watch } from 'vue';

/**
 * Create a reactive reference that syncs with localStorage
 * @param {string} key - The localStorage key
 * @param {*} defaultValue - Default value if key doesn't exist
 * @returns {import('vue').Ref} Reactive reference
 */
export function useLocalStorage(key, defaultValue) {
    // Get initial value from localStorage or use default
    const getStoredValue = () => {
        if (typeof window === 'undefined') {
            return defaultValue;
        }

        try {
            const stored = localStorage.getItem(key);
            if (stored === null) {
                return defaultValue;
            }
            return JSON.parse(stored);
        } catch (error) {
            console.warn(`Error reading localStorage key "${key}":`, error);
            return defaultValue;
        }
    };

    const data = ref(getStoredValue());

    // Watch for changes and sync to localStorage
    watch(
        data,
        (newValue) => {
            if (typeof window === 'undefined') {
                return;
            }

            try {
                if (newValue === null || newValue === undefined) {
                    localStorage.removeItem(key);
                } else {
                    localStorage.setItem(key, JSON.stringify(newValue));
                }
            } catch (error) {
                console.warn(`Error writing to localStorage key "${key}":`, error);
            }
        },
        { deep: true }
    );

    // Listen for storage events from other tabs
    if (typeof window !== 'undefined') {
        window.addEventListener('storage', (event) => {
            if (event.key === key) {
                try {
                    data.value = event.newValue ? JSON.parse(event.newValue) : defaultValue;
                } catch (error) {
                    data.value = defaultValue;
                }
            }
        });
    }

    return data;
}

export default useLocalStorage;

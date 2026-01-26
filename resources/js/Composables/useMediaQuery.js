/**
 * useMediaQuery Composable
 * Reactive media query matching
 */

import { ref, onUnmounted } from 'vue';

// Cache for media query listeners to avoid duplicates
const mediaQueryCache = new Map();

/**
 * Create a reactive boolean that tracks a media query
 * @param {string} query - CSS media query string
 * @returns {import('vue').Ref<boolean>} Reactive boolean
 */
export function useMediaQuery(query) {
    // Check cache first
    if (mediaQueryCache.has(query)) {
        return mediaQueryCache.get(query);
    }

    const matches = ref(false);

    // Initialize immediately (SSR-safe)
    if (typeof window !== 'undefined' && window.matchMedia) {
        const mediaQuery = window.matchMedia(query);
        matches.value = mediaQuery.matches;

        const updateMatches = (event) => {
            matches.value = event.matches;
        };

        // Modern browsers
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', updateMatches);
        } else {
            // Legacy support
            mediaQuery.addListener(updateMatches);
        }

        // Note: We don't remove listeners because this is module-level cached
        // The listeners will persist for the lifetime of the application
    }

    // Cache the result
    mediaQueryCache.set(query, matches);

    return matches;
}

/**
 * Preset media queries for common breakpoints (Tailwind defaults)
 */
export function useBreakpoints() {
    const isMobile = useMediaQuery('(max-width: 639px)');
    const isSm = useMediaQuery('(min-width: 640px)');
    const isMd = useMediaQuery('(min-width: 768px)');
    const isLg = useMediaQuery('(min-width: 1024px)');
    const isXl = useMediaQuery('(min-width: 1280px)');
    const is2xl = useMediaQuery('(min-width: 1536px)');

    return {
        isMobile,
        isSm,
        isMd,
        isLg,
        isXl,
        is2xl,
    };
}

export default useMediaQuery;

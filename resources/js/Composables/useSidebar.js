/**
 * useSidebar Composable
 * Manages sidebar state with three modes: normal, collapsed, hidden
 */

import { ref, computed, watch } from 'vue';
import { useLocalStorage } from './useLocalStorage';
import { useMediaQuery } from './useMediaQuery';

// Sidebar modes enum
export const SIDEBAR_MODES = {
    NORMAL: 'normal',
    COLLAPSED: 'collapsed',
    HIDDEN: 'hidden',
};

// Shared reactive state (module-level)
const userPreference = ref(SIDEBAR_MODES.NORMAL);
const currentMode = ref(SIDEBAR_MODES.NORMAL);
const mobileOpen = ref(false);
const toggleButtonVisible = ref(false);
let hideToggleTimeout = null;
let initialized = false;

/**
 * Initialize sidebar state from localStorage
 */
function initializeSidebar() {
    if (initialized) return;

    // Read from localStorage
    try {
        const stored = localStorage.getItem('slimail-sidebar-mode');
        if (stored && Object.values(SIDEBAR_MODES).includes(stored)) {
            userPreference.value = stored;
            currentMode.value = stored;
        }
    } catch (e) {
        // localStorage not available
    }

    initialized = true;
}

/**
 * Save preference to localStorage
 */
function savePreference(mode) {
    try {
        localStorage.setItem('slimail-sidebar-mode', mode);
        userPreference.value = mode;
    } catch (e) {
        // localStorage not available
    }
}

/**
 * useSidebar Composable
 * @returns {Object} Sidebar state and methods
 */
export function useSidebar() {
    // Initialize on first call
    initializeSidebar();

    // Media queries (reactive)
    const isDesktop = useMediaQuery('(min-width: 1024px)');
    const isTablet = useMediaQuery('(min-width: 768px)');

    // Computed states
    const isNormal = computed(() => currentMode.value === SIDEBAR_MODES.NORMAL);
    const isCollapsed = computed(() => currentMode.value === SIDEBAR_MODES.COLLAPSED);
    const isHidden = computed(() => currentMode.value === SIDEBAR_MODES.HIDDEN);

    // Computed sidebar width based on mode
    const sidebarWidth = computed(() => {
        if (isHidden.value) return '0rem';
        if (isCollapsed.value) return '4.5rem'; // 72px
        return '16rem'; // 256px
    });

    // Computed CSS variable for sidebar width
    const sidebarWidthVar = computed(() => {
        if (isHidden.value) return 'var(--sidebar-width-hidden)';
        if (isCollapsed.value) return 'var(--sidebar-width-collapsed)';
        return 'var(--sidebar-width-normal)';
    });

    // Computed margin-left for main content
    const contentMargin = computed(() => {
        if (!isDesktop.value) return '0';
        return sidebarWidth.value;
    });

    /**
     * Set sidebar mode
     * @param {string} mode - One of SIDEBAR_MODES values
     */
    function setMode(mode) {
        if (!Object.values(SIDEBAR_MODES).includes(mode)) {
            console.warn(`Invalid sidebar mode: ${mode}`);
            return;
        }

        currentMode.value = mode;

        // Save preference only on desktop
        if (isDesktop.value) {
            savePreference(mode);
        }
    }

    /**
     * Toggle between all three modes
     */
    function toggleMode() {
        const modes = [SIDEBAR_MODES.NORMAL, SIDEBAR_MODES.COLLAPSED, SIDEBAR_MODES.HIDDEN];
        const currentIndex = modes.indexOf(currentMode.value);
        const nextIndex = (currentIndex + 1) % modes.length;
        setMode(modes[nextIndex]);
    }

    /**
     * Toggle between normal and collapsed (skip hidden)
     */
    function cycleCollapse() {
        if (isCollapsed.value) {
            setMode(SIDEBAR_MODES.NORMAL);
        } else {
            setMode(SIDEBAR_MODES.COLLAPSED);
        }
    }

    /**
     * Expand sidebar (go to normal mode)
     */
    function expand() {
        setMode(SIDEBAR_MODES.NORMAL);
    }

    /**
     * Collapse sidebar (go to collapsed mode)
     */
    function collapse() {
        setMode(SIDEBAR_MODES.COLLAPSED);
    }

    /**
     * Hide sidebar completely
     */
    function hide() {
        setMode(SIDEBAR_MODES.HIDDEN);
    }

    /**
     * Toggle mobile menu drawer
     */
    function toggleMobileMenu() {
        mobileOpen.value = !mobileOpen.value;
    }

    /**
     * Open mobile menu
     */
    function openMobileMenu() {
        mobileOpen.value = true;
    }

    /**
     * Close mobile menu
     */
    function closeMobileMenu() {
        mobileOpen.value = false;
    }

    /**
     * Show toggle button (called on hover)
     */
    function showToggleButton() {
        if (hideToggleTimeout) {
            clearTimeout(hideToggleTimeout);
            hideToggleTimeout = null;
        }
        toggleButtonVisible.value = true;
    }

    /**
     * Hide toggle button with delay (alias: startHideTimeout)
     * @param {number} delay - Delay in milliseconds (default 2000)
     */
    function hideToggleButton(delay = 2000) {
        if (hideToggleTimeout) {
            clearTimeout(hideToggleTimeout);
        }
        hideToggleTimeout = setTimeout(() => {
            toggleButtonVisible.value = false;
        }, delay);
    }

    /**
     * Alias for hideToggleButton (for backward compatibility)
     */
    function startHideTimeout(delay = 2000) {
        hideToggleButton(delay);
    }

    /**
     * Cancel hide toggle button timeout
     */
    function cancelHideToggle() {
        if (hideToggleTimeout) {
            clearTimeout(hideToggleTimeout);
            hideToggleTimeout = null;
        }
    }

    // Watch for breakpoint changes
    watch(isDesktop, (desktop) => {
        if (desktop) {
            // Restore user preference on desktop
            currentMode.value = userPreference.value;
            mobileOpen.value = false;
        } else if (!isTablet.value) {
            // On mobile, always use hidden mode (sidebar is overlay)
            currentMode.value = SIDEBAR_MODES.HIDDEN;
        }
    }, { immediate: true });

    return {
        // State
        currentMode,
        mobileOpen,
        toggleButtonVisible,
        userPreference,

        // Computed
        isNormal,
        isCollapsed,
        isHidden,
        isDesktop,
        isTablet,
        sidebarWidth,
        sidebarWidthVar,
        contentMargin,

        // Methods
        setMode,
        toggleMode,
        cycleCollapse,
        expand,
        collapse,
        hide,
        toggleMobileMenu,
        openMobileMenu,
        closeMobileMenu,
        showToggleButton,
        hideToggleButton,
        startHideTimeout, // Alias for hideToggleButton
        cancelHideToggle,

        // Constants
        SIDEBAR_MODES,
    };
}

/**
 * Reset sidebar state (useful for testing)
 */
export function resetSidebarState() {
    currentMode.value = SIDEBAR_MODES.NORMAL;
    mobileOpen.value = false;
    toggleButtonVisible.value = false;
    initialized = false;
}

export default useSidebar;

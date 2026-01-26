<script setup>
/**
 * AppLayout Component
 * Main application layout with sidebar, header, content area, and footer
 */

import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useSidebar, SIDEBAR_MODES } from '@/Composables/useSidebar';

// Layout components
import AppSidebar from '@/Components/Layout/AppSidebar.vue';
import SidebarToggleButton from '@/Components/Layout/SidebarToggleButton.vue';
import AppHeader from '@/Components/Layout/AppHeader.vue';
import AppFooter from '@/Components/Layout/AppFooter.vue';

// Sidebar composable
const sidebar = useSidebar();

// Navigation progress state
const isNavigating = ref(false);
const navigationProgress = ref(0);
let progressInterval = null;

const startProgress = () => {
    isNavigating.value = true;
    navigationProgress.value = 0;

    progressInterval = setInterval(() => {
        if (navigationProgress.value < 90) {
            const increment = (90 - navigationProgress.value) / 10;
            navigationProgress.value += Math.max(increment, 0.5);
        }
    }, 100);
};

const finishProgress = () => {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
    navigationProgress.value = 100;

    setTimeout(() => {
        isNavigating.value = false;
        navigationProgress.value = 0;
    }, 300);
};

// Computed values to properly unwrap refs
const isDesktop = computed(() => sidebar.isDesktop.value);
const currentMode = computed(() => sidebar.currentMode.value);

// Main content offset based on sidebar mode
const mainContentStyle = computed(() => {
    if (!isDesktop.value) {
        return { marginLeft: '0' };
    }

    switch (currentMode.value) {
        case SIDEBAR_MODES.NORMAL:
            return { marginLeft: 'var(--sidebar-width-normal)' };
        case SIDEBAR_MODES.COLLAPSED:
            return { marginLeft: 'var(--sidebar-width-collapsed)' };
        case SIDEBAR_MODES.HIDDEN:
            return { marginLeft: '0' };
        default:
            return { marginLeft: 'var(--sidebar-width-normal)' };
    }
});

// Toggle button position based on sidebar mode
const toggleButtonLeft = computed(() => {
    switch (currentMode.value) {
        case SIDEBAR_MODES.NORMAL:
            return 'calc(var(--sidebar-width-normal) - 0.875rem)';
        case SIDEBAR_MODES.COLLAPSED:
            return 'calc(var(--sidebar-width-collapsed) - 0.875rem)';
        case SIDEBAR_MODES.HIDDEN:
            return '0';
        default:
            return 'calc(var(--sidebar-width-normal) - 0.875rem)';
    }
});

// Footer offset based on sidebar mode (for fixed footer)
const footerStyle = computed(() => {
    if (!isDesktop.value) {
        return { left: '0' };
    }

    switch (currentMode.value) {
        case SIDEBAR_MODES.NORMAL:
            return { left: 'var(--sidebar-width-normal)' };
        case SIDEBAR_MODES.COLLAPSED:
            return { left: 'var(--sidebar-width-collapsed)' };
        case SIDEBAR_MODES.HIDDEN:
            return { left: '0' };
        default:
            return { left: 'var(--sidebar-width-normal)' };
    }
});

onMounted(() => {
    router.on('start', startProgress);
    router.on('finish', finishProgress);
});

onUnmounted(() => {
    router.off('start', startProgress);
    router.off('finish', finishProgress);
    if (progressInterval) {
        clearInterval(progressInterval);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Global Navigation Progress Bar -->
        <div
            v-if="isNavigating"
            class="fixed top-0 left-0 right-0 z-[100] h-1"
        >
            <div
                class="h-full bg-gradient-to-r from-primary-500 via-primary-400 to-primary-600 transition-all duration-300 ease-out shadow-lg shadow-primary-500/50"
                :style="{ width: `${navigationProgress}%` }"
            >
                <div class="absolute right-0 top-0 h-full w-20 bg-gradient-to-r from-transparent to-white/30 animate-shimmer"></div>
            </div>
        </div>

        <!-- Sidebar -->
        <AppSidebar />

        <!-- Toggle Button (at sidebar edge, only on desktop) -->
        <div
            v-if="isDesktop"
            class="fixed top-1/2 -translate-y-1/2 z-[45] transition-all duration-300"
            :style="{ left: toggleButtonLeft }"
        >
            <SidebarToggleButton />
        </div>

        <!-- Main content wrapper -->
        <div
            class="min-h-screen flex flex-col transition-all duration-300 pb-footer"
            :style="mainContentStyle"
        >
            <!-- Header -->
            <AppHeader />

            <!-- Page content -->
            <main class="flex-1 relative">
                <slot />
            </main>
        </div>

        <!-- Fixed Footer -->
        <AppFooter :style="footerStyle" />
    </div>
</template>

<style scoped>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}

.animate-shimmer {
    animation: shimmer 1.5s infinite;
}
</style>

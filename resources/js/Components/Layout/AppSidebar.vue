<script setup>
/**
 * AppSidebar Component
 * Main sidebar with three display modes: normal, collapsed, hidden
 */

import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useSidebar } from '@/Composables/useSidebar';
import SliMailLogo from '@/Components/Common/SliMailLogo.vue';
import SidebarNavItem from './SidebarNavItem.vue';
import SidebarUserSection from './SidebarUserSection.vue';

const sidebar = useSidebar();

// Check if we're in development mode
const isDevelopment = import.meta.env.DEV || import.meta.env.MODE === 'development';

// Navigation items
const navigation = computed(() => {
    const items = [
        { name: 'Dashboard', href: '/dashboard', icon: 'home' },
        { name: 'Contacts', href: '/contacts', icon: 'users' },
        { name: 'Campagnes', href: '/campaigns', icon: 'mail' },
        { name: 'Templates', href: '/templates', icon: 'template' },
        { name: 'Automatisations', href: '/automations', icon: 'zap' },
        { name: 'Statistiques', href: '/statistics', icon: 'chart' },
        { name: 'Facturation', href: '/billing', icon: 'credit-card' },
        { name: 'API', href: '/api-settings', icon: 'code' },
    ];

    // Add dev tools in development mode
    if (isDevelopment) {
        items.push({ name: 'Dev Tools', href: '/dev/mock-emails', icon: 'dev', badge: 'DEV' });
    }

    return items;
});

// Unwrap refs for template use
const mobileOpen = computed(() => sidebar.mobileOpen.value);
const isCollapsed = computed(() => sidebar.isCollapsed.value);
const isHidden = computed(() => sidebar.isHidden.value);

// Sidebar width class based on mode
const sidebarWidthClass = computed(() => {
    if (isHidden.value) return 'w-0';
    if (isCollapsed.value) return 'w-sidebar-collapsed';
    return 'w-sidebar';
});

// Is sidebar content collapsed (collapsed mode AND not mobile open)
const isContentCollapsed = computed(() => isCollapsed.value && !mobileOpen.value);

// Close mobile menu
const closeMobileMenu = () => {
    sidebar.closeMobileMenu();
};
</script>

<template>
    <!-- Mobile Overlay -->
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-[50] lg:hidden"
            @click="closeMobileMenu"
        >
            <div class="fixed inset-0 bg-secondary-900/60 backdrop-blur-sm" />
        </div>
    </Transition>

    <!-- Sidebar -->
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-[40] flex flex-col',
            'bg-gradient-to-b from-secondary-900 via-secondary-900 to-secondary-950',
            'transition-all duration-300 shadow-sidebar',
            'lg:translate-x-0',
            sidebarWidthClass,
            mobileOpen ? 'translate-x-0 w-sidebar' : '-translate-x-full lg:translate-x-0',
            isHidden && !mobileOpen ? 'lg:-translate-x-full' : '',
        ]"
    >
        <div class="flex flex-col h-full overflow-hidden">
            <!-- Logo Header -->
            <div
                :class="[
                    'flex items-center h-header border-b border-secondary-800/50 flex-shrink-0',
                    isContentCollapsed ? 'justify-center px-3' : 'px-6',
                ]"
            >
                <Link href="/dashboard" class="flex items-center group">
                    <SliMailLogo
                        :show-text="!isContentCollapsed"
                        :size="isContentCollapsed ? 'sm' : 'md'"
                        background="dark"
                    />
                </Link>

                <!-- Close button for mobile -->
                <button
                    v-if="mobileOpen"
                    @click="closeMobileMenu"
                    class="lg:hidden ml-auto p-1.5 text-secondary-400 hover:text-white rounded-lg hover:bg-secondary-800 transition-colors"
                    aria-label="Fermer le menu"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav
                :class="[
                    'flex-1 py-6 overflow-y-auto custom-scrollbar dark-scrollbar',
                    isContentCollapsed ? 'px-2' : 'px-3',
                ]"
            >
                <div class="space-y-1.5">
                    <SidebarNavItem
                        v-for="item in navigation"
                        :key="item.name"
                        :item="item"
                        :collapsed="isContentCollapsed"
                    />
                </div>
            </nav>

            <!-- User Section -->
            <SidebarUserSection :collapsed="isContentCollapsed" />
        </div>
    </aside>
</template>

<style scoped>
aside {
    will-change: width, transform;
}

.custom-scrollbar {
    scrollbar-width: thin;
}

.dark-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.dark-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.dark-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 9999px;
}

.dark-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>

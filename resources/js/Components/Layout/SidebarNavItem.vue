<script setup>
/**
 * SidebarNavItem Component
 * Navigation item for the sidebar with support for collapsed mode
 */

import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    /**
     * Navigation item data
     */
    item: {
        type: Object,
        required: true,
        validator: (v) => v.name && v.href && v.icon,
    },

    /**
     * Whether sidebar is collapsed
     */
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

// Check if this item is active
const isActive = computed(() => {
    return page.url.startsWith(props.item.href);
});

// Tooltip visibility for collapsed mode
const showTooltip = ref(false);
let tooltipTimeout = null;

const onMouseEnter = () => {
    if (props.collapsed) {
        tooltipTimeout = setTimeout(() => {
            showTooltip.value = true;
        }, 200);
    }
};

const onMouseLeave = () => {
    if (tooltipTimeout) {
        clearTimeout(tooltipTimeout);
    }
    showTooltip.value = false;
};
</script>

<template>
    <Link
        :href="item.href"
        :class="[
            'group relative flex items-center rounded-xl transition-all duration-200 overflow-hidden',
            collapsed ? 'justify-center px-3 py-3' : 'px-4 py-3',
            isActive
                ? 'bg-gradient-to-r from-primary-600 to-primary-500 text-white shadow-lg shadow-primary-600/30'
                : 'text-secondary-300 hover:bg-secondary-800/70 hover:text-white'
        ]"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
    >
        <!-- Active indicator glow -->
        <div
            v-if="isActive"
            class="absolute inset-0 bg-gradient-to-r from-primary-400/20 to-transparent"
        />

        <!-- Icon -->
        <span :class="['relative z-10 flex-shrink-0', collapsed ? '' : 'mr-3']">
            <!-- Home icon -->
            <svg v-if="item.icon === 'home'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>

            <!-- Users icon -->
            <svg v-else-if="item.icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>

            <!-- Mail icon -->
            <svg v-else-if="item.icon === 'mail'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>

            <!-- Template icon -->
            <svg v-else-if="item.icon === 'template'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
            </svg>

            <!-- Zap icon -->
            <svg v-else-if="item.icon === 'zap'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>

            <!-- Chart icon -->
            <svg v-else-if="item.icon === 'chart'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>

            <!-- Code icon -->
            <svg v-else-if="item.icon === 'code'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>

            <!-- Dev Tools icon -->
            <svg v-else-if="item.icon === 'dev'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>

            <!-- Credit Card / Billing icon -->
            <svg v-else-if="item.icon === 'credit-card'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>

            <!-- Settings icon -->
            <svg v-else-if="item.icon === 'settings'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>

            <!-- Default icon -->
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </span>

        <!-- Text (hidden when collapsed) -->
        <span
            v-if="!collapsed"
            class="truncate relative z-10 text-sm font-medium"
        >
            {{ item.name }}
        </span>

        <!-- Badge (e.g., DEV) -->
        <span
            v-if="item.badge && !collapsed"
            class="ml-2 px-1.5 py-0.5 text-[10px] font-bold rounded bg-yellow-500 text-yellow-900"
        >
            {{ item.badge }}
        </span>

        <!-- Hover arrow indicator (only when not active and not collapsed) -->
        <svg
            v-if="!isActive && !collapsed"
            class="w-4 h-4 ml-auto opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 text-secondary-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>

        <!-- Tooltip for collapsed mode -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="collapsed && showTooltip"
                class="absolute left-full ml-3 px-3 py-1.5 bg-secondary-900 text-white text-sm font-medium rounded-lg whitespace-nowrap z-tooltip shadow-lg"
            >
                {{ item.name }}
                <!-- Arrow -->
                <div class="absolute right-full top-1/2 -translate-y-1/2 border-8 border-transparent border-r-secondary-900" />
            </div>
        </Transition>
    </Link>
</template>

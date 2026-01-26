<script setup>
/**
 * SidebarUserSection Component
 * User profile section in the sidebar with collapsed mode support
 */

import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    /**
     * Whether sidebar is collapsed
     */
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

// User data
const user = computed(() => page.props.auth?.user || {});

// User initials
const initials = computed(() => {
    if (user.value.initials) return user.value.initials;
    if (user.value.name) {
        const names = user.value.name.split(' ');
        if (names.length >= 2) {
            return (names[0][0] + names[names.length - 1][0]).toUpperCase();
        }
        return user.value.name.charAt(0).toUpperCase();
    }
    return 'U';
});

// Tooltip for collapsed mode
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
    <div class="p-3 border-t border-secondary-800/50">
        <!-- Collapsed mode: Just avatar -->
        <div
            v-if="collapsed"
            class="relative flex justify-center"
            @mouseenter="onMouseEnter"
            @mouseleave="onMouseLeave"
        >
            <Link
                href="/settings"
                class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-primary-600/30 hover:scale-105 transition-transform"
            >
                <span class="text-white font-semibold text-sm">{{ initials }}</span>
            </Link>

            <!-- Tooltip -->
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showTooltip"
                    class="absolute left-full ml-3 px-3 py-2 bg-secondary-900 text-white rounded-lg whitespace-nowrap z-tooltip shadow-lg"
                >
                    <div class="text-sm font-medium">{{ user.name || 'Utilisateur' }}</div>
                    <div class="text-xs text-secondary-400">{{ user.role_label || '' }}</div>
                    <!-- Arrow -->
                    <div class="absolute right-full top-1/2 -translate-y-1/2 border-8 border-transparent border-r-secondary-900" />
                </div>
            </Transition>
        </div>

        <!-- Normal mode: Full user card -->
        <div
            v-else
            class="flex items-center p-3 bg-gradient-to-r from-secondary-800/80 to-secondary-800/40 rounded-xl backdrop-blur"
        >
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-primary-600/30">
                    <span class="text-white font-semibold text-sm">{{ initials }}</span>
                </div>
            </div>

            <!-- User info -->
            <div class="ml-3 min-w-0 flex-1">
                <p class="text-sm font-medium text-white truncate">
                    {{ user.name || 'Utilisateur' }}
                </p>
                <p class="text-xs text-secondary-400 truncate">
                    {{ user.role_label || '' }}
                </p>
            </div>

            <!-- Settings button -->
            <div class="flex items-center gap-1">
                <Link
                    href="/settings"
                    class="p-1.5 text-secondary-400 hover:text-white rounded-lg hover:bg-secondary-700/50 transition-colors"
                    title="Paramètres"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </Link>
            </div>
        </div>
    </div>
</template>

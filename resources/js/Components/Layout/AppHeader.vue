<script setup>
/**
 * AppHeader Component
 * Main application header with search, notifications, and user menu
 */

import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useSidebar } from '@/Composables/useSidebar';
import HeaderSearch from './HeaderSearch.vue';
import HeaderNotifications from './HeaderNotifications.vue';
import HeaderUserMenu from './HeaderUserMenu.vue';

const sidebar = useSidebar();
const page = usePage();

// Tenant info
const tenant = computed(() => page.props.tenant || null);
</script>

<template>
    <header class="sticky top-0 z-header bg-white/80 backdrop-blur-lg border-b border-gray-100/80 shadow-header h-header">
        <div class="flex items-center justify-between h-full px-page">
            <!-- Left section -->
            <div class="flex items-center gap-4">
                <!-- Mobile menu button -->
                <button
                    @click="sidebar.openMobileMenu()"
                    class="lg:hidden p-2 -ml-2 text-secondary-500 hover:text-secondary-700 rounded-xl hover:bg-gray-100 transition-colors"
                    aria-label="Ouvrir le menu"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Tenant badge (desktop) -->
                <div
                    v-if="tenant"
                    class="hidden lg:flex items-center gap-2 px-3 py-1.5 bg-secondary-50 rounded-lg"
                >
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
                    <span class="text-sm font-medium text-secondary-700">
                        {{ tenant.name }}
                    </span>
                </div>
            </div>

            <!-- Center section - Search -->
            <div class="hidden sm:flex flex-1 justify-center max-w-md mx-4 lg:mx-8">
                <HeaderSearch />
            </div>

            <!-- Right section -->
            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Mobile search button -->
                <button
                    class="sm:hidden p-2.5 text-secondary-400 hover:text-secondary-600 rounded-xl hover:bg-gray-100 transition-colors"
                    title="Rechercher"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                <!-- Quick action buttons (desktop) -->
                <div class="hidden lg:flex items-center gap-1">
                    <Link
                        href="/campaigns/create"
                        class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-xl transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Campagne</span>
                    </Link>
                </div>

                <div class="hidden sm:block w-px h-6 bg-gray-200 mx-1" />

                <!-- Help button -->
                <a
                    href="https://docs.slimail.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hidden sm:flex p-2.5 text-secondary-400 hover:text-secondary-600 rounded-xl hover:bg-gray-100 transition-colors"
                    title="Aide"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </a>

                <!-- Notifications -->
                <HeaderNotifications />

                <div class="w-px h-6 bg-gray-200 mx-1" />

                <!-- User menu -->
                <HeaderUserMenu />
            </div>
        </div>
    </header>
</template>

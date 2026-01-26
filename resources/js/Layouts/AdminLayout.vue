<script setup>
/**
 * Admin Layout
 * Layout for super admin pages
 */

import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const navigation = [
    { name: 'Dashboard', href: route('admin.dashboard'), icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Tenants', href: route('admin.tenants.index'), icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { name: 'Forfaits', href: route('admin.plans.index'), icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
    { name: 'Monitoring', href: route('admin.monitoring.index'), icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
];

const isActive = (href) => {
    return window.location.pathname.startsWith(new URL(href).pathname);
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link :href="route('admin.dashboard')" class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-lg">S</span>
                            </div>
                            <span class="text-white font-bold text-xl">SliMail Admin</span>
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden md:flex items-center space-x-4">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            :class="[
                                isActive(item.href)
                                    ? 'bg-gray-800 text-white'
                                    : 'text-gray-300 hover:bg-gray-700 hover:text-white',
                                'px-3 py-2 rounded-md text-sm font-medium flex items-center gap-2'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </Link>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('dashboard')"
                            class="text-gray-300 hover:text-white text-sm flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour app
                        </Link>

                        <div class="flex items-center gap-2 text-white">
                            <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium">{{ user?.name?.charAt(0) }}</span>
                            </div>
                            <span class="text-sm">{{ user?.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Impersonation banner -->
        <div
            v-if="$page.props.impersonating"
            class="bg-yellow-500 text-yellow-900 px-4 py-2 text-center text-sm"
        >
            <span class="font-medium">Mode impersonation actif.</span>
            <Link
                :href="route('admin.stop-impersonating')"
                method="post"
                as="button"
                class="ml-4 underline hover:no-underline"
            >
                Revenir au compte admin
            </Link>
        </div>

        <!-- Main content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>

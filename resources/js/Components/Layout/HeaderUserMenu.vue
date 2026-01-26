<script setup>
/**
 * HeaderUserMenu Component
 * User dropdown menu with profile, settings, and logout
 */

import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const isOpen = ref(false);
const dropdownRef = ref(null);
const page = usePage();

// User data
const user = computed(() => page.props.auth?.user || {});
const tenant = computed(() => page.props.tenant || null);

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

// Toggle dropdown
const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Trigger Button -->
        <button
            @click="toggleDropdown"
            class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 transition-colors"
            :class="{ 'bg-gray-100': isOpen }"
        >
            <!-- Avatar -->
            <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center shadow-sm">
                <span class="text-white font-semibold text-sm">{{ initials }}</span>
            </div>
            <!-- Name (hidden on mobile) -->
            <span class="hidden sm:block text-sm font-medium text-secondary-700 max-w-[120px] truncate">
                {{ user.name || 'Utilisateur' }}
            </span>
            <!-- Chevron -->
            <svg
                :class="['w-4 h-4 text-secondary-400 transition-transform duration-200', isOpen ? 'rotate-180' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-dropdown border border-secondary-200/60 overflow-hidden z-dropdown"
            >
                <!-- User Info -->
                <div class="px-4 py-4 bg-gradient-to-r from-secondary-50 to-white border-b border-secondary-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-primary-600/20">
                            <span class="text-white font-semibold text-lg">{{ initials }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-secondary-900 truncate">
                                {{ user.name || 'Utilisateur' }}
                            </p>
                            <p class="text-xs text-secondary-500 truncate">
                                {{ user.email || '' }}
                            </p>
                            <span
                                v-if="user.role_label"
                                class="inline-block mt-1 px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 rounded-full"
                            >
                                {{ user.role_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Tenant info -->
                    <div v-if="tenant" class="mt-3 pt-3 border-t border-secondary-200/60">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-emerald-500 rounded-full" />
                            <span class="text-xs font-medium text-secondary-600">{{ tenant.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Menu Items -->
                <div class="py-2">
                    <Link
                        href="/profile"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 hover:bg-secondary-50 transition-colors"
                        @click="isOpen = false"
                    >
                        <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Mon profil
                    </Link>

                    <Link
                        href="/settings"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 hover:bg-secondary-50 transition-colors"
                        @click="isOpen = false"
                    >
                        <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Paramètres
                    </Link>

                    <Link
                        href="/billing"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 hover:bg-secondary-50 transition-colors"
                        @click="isOpen = false"
                    >
                        <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Facturation
                    </Link>

                    <a
                        href="https://docs.slimail.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 hover:bg-secondary-50 transition-colors"
                    >
                        <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Documentation
                        <svg class="w-3 h-3 ml-auto text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>

                <!-- Logout -->
                <div class="py-2 border-t border-secondary-100">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                        @click="isOpen = false"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Déconnexion
                    </Link>
                </div>
            </div>
        </Transition>
    </div>
</template>

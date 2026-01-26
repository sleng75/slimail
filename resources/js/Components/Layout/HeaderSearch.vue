<script setup>
/**
 * HeaderSearch Component
 * Global search with command palette style (Ctrl+K)
 */

import { ref, onMounted, onUnmounted } from 'vue';

const isOpen = ref(false);
const searchQuery = ref('');
const inputRef = ref(null);

// Open search modal
const openSearch = () => {
    isOpen.value = true;
    // Focus input after transition
    setTimeout(() => {
        inputRef.value?.focus();
    }, 100);
};

// Close search modal
const closeSearch = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

// Handle keyboard shortcut
const handleKeydown = (event) => {
    // Ctrl/Cmd + K to open
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        if (isOpen.value) {
            closeSearch();
        } else {
            openSearch();
        }
    }

    // Escape to close
    if (event.key === 'Escape' && isOpen.value) {
        closeSearch();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

// Handle search
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        // TODO: Implement actual search
        console.log('Search:', searchQuery.value);
    }
};
</script>

<template>
    <!-- Search Trigger Button -->
    <button
        @click="openSearch"
        class="flex items-center gap-2 px-3 py-2 text-sm text-secondary-500 bg-secondary-100/80 hover:bg-secondary-200/80 rounded-xl transition-colors w-full max-w-xs"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span class="flex-1 text-left truncate">Rechercher...</span>
        <kbd class="hidden sm:inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-medium text-secondary-400 bg-white rounded border border-secondary-200">
            <span class="text-[10px]">⌘</span>K
        </kbd>
    </button>

    <!-- Search Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-modal flex items-start justify-center pt-[15vh] px-4"
                @click.self="closeSearch"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-secondary-900/50 backdrop-blur-sm" @click="closeSearch" />

                <!-- Modal -->
                <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden animate-scale-in">
                    <!-- Search Input -->
                    <div class="flex items-center gap-3 px-4 py-4 border-b border-secondary-100">
                        <svg class="w-5 h-5 text-secondary-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="inputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher contacts, campagnes, templates..."
                            class="flex-1 text-base text-secondary-900 placeholder-secondary-400 bg-transparent border-0 outline-none focus:ring-0"
                            @keydown.enter="handleSearch"
                        />
                        <button
                            @click="closeSearch"
                            class="p-1.5 text-secondary-400 hover:text-secondary-600 rounded-lg hover:bg-secondary-100 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Quick Actions -->
                    <div class="p-2">
                        <div class="px-3 py-2 text-xs font-medium text-secondary-400 uppercase tracking-wider">
                            Actions rapides
                        </div>
                        <div class="space-y-1">
                            <a
                                href="/campaigns/create"
                                class="flex items-center gap-3 px-3 py-2.5 text-sm text-secondary-700 rounded-xl hover:bg-secondary-50 transition-colors"
                                @click="closeSearch"
                            >
                                <div class="w-8 h-8 flex items-center justify-center bg-primary-100 text-primary-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium">Nouvelle campagne</div>
                                    <div class="text-xs text-secondary-500">Créer une campagne email</div>
                                </div>
                            </a>
                            <a
                                href="/contacts/create"
                                class="flex items-center gap-3 px-3 py-2.5 text-sm text-secondary-700 rounded-xl hover:bg-secondary-50 transition-colors"
                                @click="closeSearch"
                            >
                                <div class="w-8 h-8 flex items-center justify-center bg-emerald-100 text-emerald-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium">Nouveau contact</div>
                                    <div class="text-xs text-secondary-500">Ajouter un contact</div>
                                </div>
                            </a>
                            <a
                                href="/templates/create"
                                class="flex items-center gap-3 px-3 py-2.5 text-sm text-secondary-700 rounded-xl hover:bg-secondary-50 transition-colors"
                                @click="closeSearch"
                            >
                                <div class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium">Nouveau template</div>
                                    <div class="text-xs text-secondary-500">Créer un template email</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 bg-secondary-50 border-t border-secondary-100 flex items-center justify-between text-xs text-secondary-500">
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 bg-white rounded border border-secondary-200 font-medium">↵</kbd>
                                pour sélectionner
                            </span>
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 bg-white rounded border border-secondary-200 font-medium">esc</kbd>
                                pour fermer
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

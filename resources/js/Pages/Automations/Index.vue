<script setup>
/**
 * Automations Index Page
 * List all automations with filtering and actions
 */

import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    automations: Object,
    filters: Object,
    triggerTypes: Object,
    statuses: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const triggerFilter = ref(props.filters?.trigger || '');

// Status badge colors
const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-700',
        active: 'bg-emerald-100 text-emerald-700',
        paused: 'bg-amber-100 text-amber-700',
        archived: 'bg-red-100 text-red-700',
    };
    return colors[status] || colors.draft;
};

// Trigger icon
const getTriggerIcon = (trigger) => {
    const icons = {
        list_subscription: '📋',
        tag_added: '🏷️',
        tag_removed: '🏷️',
        email_opened: '📧',
        link_clicked: '🔗',
        date_field: '📅',
        inactivity: '⏰',
        webhook: '🌐',
        manual: '👆',
    };
    return icons[trigger] || '⚡';
};

// Apply filters
const applyFilters = debounce(() => {
    router.get(route('automations.index'), {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        trigger: triggerFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch([search, statusFilter, triggerFilter], applyFilters);

// Actions
const activateAutomation = (automation) => {
    if (confirm('Activer cette automatisation ?')) {
        router.post(route('automations.activate', automation.id));
    }
};

const pauseAutomation = (automation) => {
    if (confirm('Mettre en pause cette automatisation ?')) {
        router.post(route('automations.pause', automation.id));
    }
};

const duplicateAutomation = (automation) => {
    router.post(route('automations.duplicate', automation.id));
};

const deleteAutomation = (automation) => {
    if (confirm('Supprimer cette automatisation ? Cette action est irréversible.')) {
        router.delete(route('automations.destroy', automation.id));
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Automatisations" />

        <PageContainer>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-secondary-900">Automatisations</h1>
                        <p class="text-secondary-500 mt-1">Créez des workflows automatisés pour vos contacts</p>
                    </div>
                    <Link
                        :href="route('automations.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouvelle automatisation
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <!-- Status filter -->
                        <select
                            v-model="statusFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="all">Tous les statuts</option>
                            <option v-for="(label, value) in statuses" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>

                        <!-- Trigger filter -->
                        <select
                            v-model="triggerFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">Tous les déclencheurs</option>
                            <option v-for="(label, value) in triggerTypes" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Automations list -->
                <div v-if="automations.data.length > 0" class="space-y-4">
                    <div
                        v-for="automation in automations.data"
                        :key="automation.id"
                        class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow"
                    >
                        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-2xl">{{ getTriggerIcon(automation.trigger_type) }}</span>
                                    <Link
                                        :href="route('automations.show', automation.id)"
                                        class="text-lg font-semibold text-secondary-900 hover:text-primary-600 truncate"
                                    >
                                        {{ automation.name }}
                                    </Link>
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            getStatusColor(automation.status)
                                        ]"
                                    >
                                        {{ automation.status_label }}
                                    </span>
                                </div>
                                <p v-if="automation.description" class="text-secondary-500 text-sm truncate">
                                    {{ automation.description }}
                                </p>
                                <div class="flex items-center gap-4 mt-2 text-sm text-secondary-400">
                                    <span>{{ automation.trigger_label }}</span>
                                    <span>•</span>
                                    <span>{{ automation.steps_count }} étapes</span>
                                    <span v-if="automation.activated_at">•</span>
                                    <span v-if="automation.activated_at">Activé le {{ automation.activated_at }}</span>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="flex items-center gap-6 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-secondary-900">{{ automation.total_enrolled }}</div>
                                    <div class="text-xs text-secondary-400">Inscrits</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-primary-600">{{ automation.currently_active }}</div>
                                    <div class="text-xs text-secondary-400">Actifs</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-emerald-600">{{ automation.completed }}</div>
                                    <div class="text-xs text-secondary-400">Terminés</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-secondary-600">{{ automation.conversion_rate }}%</div>
                                    <div class="text-xs text-secondary-400">Conversion</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="automation.status === 'draft' || automation.status === 'paused'"
                                    @click="activateAutomation(automation)"
                                    class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                    title="Activer"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                                <button
                                    v-if="automation.status === 'active'"
                                    @click="pauseAutomation(automation)"
                                    class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                    title="Pause"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                                <Link
                                    :href="route('automations.edit', automation.id)"
                                    class="p-2 text-secondary-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    title="Modifier"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </Link>
                                <button
                                    @click="duplicateAutomation(automation)"
                                    class="p-2 text-secondary-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    title="Dupliquer"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteAutomation(automation)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Supprimer"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="automations.links && automations.links.length > 3" class="flex justify-center gap-2">
                        <template v-for="link in automations.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-2 rounded-lg text-sm',
                                    link.active
                                        ? 'bg-primary-600 text-white'
                                        : 'bg-white border border-gray-300 text-secondary-700 hover:bg-gray-50'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-2 text-sm text-secondary-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-primary-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-secondary-900 mb-2">Aucune automatisation</h3>
                    <p class="text-secondary-500 mb-6">
                        Créez votre première automatisation pour engager vos contacts automatiquement.
                    </p>
                    <Link
                        :href="route('automations.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Créer une automatisation
                    </Link>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

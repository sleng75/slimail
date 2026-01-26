<script setup>
/**
 * Automation Logs Page
 * View activity logs for the automation
 */

import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    automation: Object,
    logs: Object,
    filters: Object,
    actionLabels: Object,
});

const actionFilter = ref(props.filters?.action || 'all');
const statusFilter = ref(props.filters?.status || 'all');

// Status colors
const getStatusColor = (status) => {
    const colors = {
        success: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-red-100 text-red-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

// Action icons
const getActionIcon = (action) => {
    const icons = {
        enrolled: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
        step_started: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z',
        step_completed: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        step_failed: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        email_sent: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        wait_started: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        wait_completed: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        tag_added: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        tag_removed: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        list_added: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        list_removed: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        condition_evaluated: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        goal_reached: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
        exited: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
        completed: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        failed: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    };
    return icons[action] || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
};

// Apply filters
const applyFilters = debounce(() => {
    router.get(route('automations.logs', props.automation.id), {
        action: actionFilter.value !== 'all' ? actionFilter.value : undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch([actionFilter, statusFilter], applyFilters);
</script>

<template>
    <AppLayout>
        <Head :title="`Logs: ${automation.name}`" />

        <PageContainer>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <Link
                                :href="route('automations.show', automation.id)"
                                class="text-secondary-400 hover:text-secondary-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                            </Link>
                            <h1 class="text-2xl font-bold text-secondary-900">Journal d'activité</h1>
                        </div>
                        <p class="text-secondary-500 mt-1">{{ automation.name }}</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Action filter -->
                        <select
                            v-model="actionFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="all">Toutes les actions</option>
                            <option v-for="(label, value) in actionLabels" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>

                        <!-- Status filter -->
                        <select
                            v-model="statusFilter"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="all">Tous les statuts</option>
                            <option value="success">Succès</option>
                            <option value="failed">Échec</option>
                        </select>
                    </div>
                </div>

                <!-- Logs list -->
                <div v-if="logs.data.length > 0" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="log in logs.data"
                            :key="log.id"
                            class="p-4 hover:bg-gray-50"
                        >
                            <div class="flex items-start gap-4">
                                <!-- Icon -->
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActionIcon(log.action)" />
                                    </svg>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-medium text-secondary-900">{{ log.action_label }}</span>
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded text-xs font-medium',
                                                getStatusColor(log.status)
                                            ]"
                                        >
                                            {{ log.status === 'success' ? 'Succès' : 'Échec' }}
                                        </span>
                                    </div>

                                    <div class="mt-1 text-sm text-secondary-500">
                                        <span v-if="log.contact_email">{{ log.contact_email }}</span>
                                        <span v-if="log.contact_email && log.step_name"> • </span>
                                        <span v-if="log.step_name">{{ log.step_name }}</span>
                                    </div>

                                    <p v-if="log.message" class="mt-1 text-sm text-red-600">
                                        {{ log.message }}
                                    </p>

                                    <!-- Additional data -->
                                    <div v-if="log.data && Object.keys(log.data).length > 0" class="mt-2">
                                        <details class="text-xs">
                                            <summary class="cursor-pointer text-secondary-400 hover:text-secondary-600">
                                                Voir les détails
                                            </summary>
                                            <pre class="mt-2 p-2 bg-gray-50 rounded text-secondary-600 overflow-x-auto">{{ JSON.stringify(log.data, null, 2) }}</pre>
                                        </details>
                                    </div>
                                </div>

                                <!-- Timestamp -->
                                <div class="flex-shrink-0 text-sm text-secondary-400">
                                    {{ log.created_at }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.links && logs.links.length > 3" class="px-6 py-4 border-t border-gray-200 flex justify-center gap-2">
                        <template v-for="link in logs.links" :key="link.label">
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
                        </template>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-secondary-900 mb-2">Aucune activité</h3>
                    <p class="text-secondary-500">
                        Le journal d'activité apparaîtra ici lorsque des actions seront effectuées.
                    </p>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

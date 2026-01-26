<script setup>
/**
 * Automation Enrollments Page
 * View and manage contacts in the automation
 */

import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    automation: Object,
    enrollments: Object,
    filters: Object,
    statuses: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');

// Status colors
const getStatusColor = (status) => {
    const colors = {
        active: 'bg-blue-100 text-blue-700',
        waiting: 'bg-amber-100 text-amber-700',
        completed: 'bg-emerald-100 text-emerald-700',
        exited: 'bg-gray-100 text-gray-700',
        failed: 'bg-red-100 text-red-700',
    };
    return colors[status] || colors.active;
};

// Apply filters
const applyFilters = debounce(() => {
    router.get(route('automations.enrollments', props.automation.id), {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch([search, statusFilter], applyFilters);

// Remove enrollment
const removeEnrollment = (enrollment) => {
    if (confirm('Retirer ce contact de l\'automatisation ?')) {
        router.delete(route('automations.remove-enrollment', [props.automation.id, enrollment.id]));
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="`Inscrits: ${automation.name}`" />

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
                            <h1 class="text-2xl font-bold text-secondary-900">Contacts inscrits</h1>
                        </div>
                        <p class="text-secondary-500 mt-1">{{ automation.name }}</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher un contact..."
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
                    </div>
                </div>

                <!-- Enrollments list -->
                <div v-if="enrollments.data.length > 0" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                    Étape actuelle
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                    Inscrit le
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                    Durée
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="enrollment in enrollments.data" :key="enrollment.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-secondary-900">
                                        {{ enrollment.contact.name || '-' }}
                                    </div>
                                    <div class="text-sm text-secondary-500">
                                        {{ enrollment.contact.email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-xs font-medium',
                                            getStatusColor(enrollment.status)
                                        ]"
                                    >
                                        {{ enrollment.status_label }}
                                    </span>
                                    <div v-if="enrollment.exit_reason" class="text-xs text-secondary-400 mt-1">
                                        {{ enrollment.exit_reason }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-secondary-600">
                                    {{ enrollment.current_step?.name || enrollment.current_step?.type || '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-secondary-600">
                                    {{ enrollment.enrolled_at }}
                                </td>
                                <td class="px-6 py-4 text-sm text-secondary-600">
                                    {{ enrollment.duration }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="enrollment.status === 'active' || enrollment.status === 'waiting'"
                                        @click="removeEnrollment(enrollment)"
                                        class="text-red-600 hover:text-red-700 text-sm"
                                    >
                                        Retirer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="enrollments.links && enrollments.links.length > 3" class="px-6 py-4 border-t border-gray-200 flex justify-center gap-2">
                        <template v-for="link in enrollments.links" :key="link.label">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-secondary-900 mb-2">Aucun contact inscrit</h3>
                    <p class="text-secondary-500">
                        Les contacts apparaîtront ici lorsqu'ils déclencheront cette automatisation.
                    </p>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

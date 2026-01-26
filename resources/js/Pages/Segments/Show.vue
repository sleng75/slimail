<script setup>
/**
 * Segment Show Page
 */

import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    segment: Object,
    sampleContacts: Array,
    availableFields: Object,
    operators: Object,
});

const refreshing = ref(false);

const formatNumber = (num) => new Intl.NumberFormat('fr-FR').format(num || 0);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const refreshCount = async () => {
    refreshing.value = true;
    try {
        await router.post(route('segments.refresh-count', props.segment.id), {}, {
            preserveScroll: true,
            onFinish: () => {
                refreshing.value = false;
            },
        });
    } catch {
        refreshing.value = false;
    }
};

const deleteSegment = () => {
    if (confirm(`Supprimer le segment "${props.segment.name}" ?`)) {
        router.delete(route('segments.destroy', props.segment.id));
    }
};

// Get field label
const getFieldLabel = (fieldValue) => {
    return props.availableFields[fieldValue]?.label || fieldValue;
};

// Get operator label
const getOperatorLabel = (op) => {
    return props.operators[op] || op;
};

// Format criterion value for display
const formatCriterionValue = (criterion) => {
    if (['is_empty', 'is_not_empty'].includes(criterion.operator)) {
        return '';
    }

    const field = props.availableFields[criterion.field];
    if (field?.options) {
        const option = field.options.find(o =>
            (typeof o === 'object' ? o.value : o) == criterion.value
        );
        if (option) {
            return typeof option === 'object' ? option.label : option;
        }
    }

    return criterion.value;
};
</script>

<template>
    <AppLayout>
        <Head :title="segment.name" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <Link
                            :href="route('segments.index')"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">{{ segment.name }}</h1>
                        <span
                            class="px-2 py-1 text-xs font-medium rounded-full"
                            :class="segment.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                        >
                            {{ segment.is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                    <p v-if="segment.description" class="text-gray-500">{{ segment.description }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="route('segments.export', segment.id)"
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Exporter CSV
                    </a>
                    <Link
                        :href="route('segments.edit', segment.id)"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                    >
                        Modifier
                    </Link>
                    <button
                        @click="deleteSegment"
                        class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                        title="Supprimer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900">{{ formatNumber(segment.contact_count) }}</p>
                            <p class="text-gray-500">contacts dans ce segment</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <button
                            @click="refreshCount"
                            :disabled="refreshing"
                            class="flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 disabled:opacity-50"
                        >
                            <svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Actualiser
                        </button>
                        <p v-if="segment.count_cached_at" class="text-xs text-gray-400 mt-1">
                            Mis à jour {{ formatDate(segment.count_cached_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Criteria -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Critères de segmentation</h2>

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-sm text-gray-600">Mode de correspondance :</span>
                    <span
                        class="px-3 py-1 text-sm font-medium rounded-full"
                        :class="segment.match_type === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                    >
                        {{ segment.match_type === 'all' ? 'Tous les critères (ET)' : 'Au moins un critère (OU)' }}
                    </span>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="(criterion, index) in segment.criteria"
                        :key="index"
                        class="flex items-center gap-3"
                    >
                        <span
                            v-if="index > 0"
                            class="px-2 py-0.5 text-xs font-medium rounded"
                            :class="segment.match_type === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                        >
                            {{ segment.match_type === 'all' ? 'ET' : 'OU' }}
                        </span>
                        <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-900">{{ getFieldLabel(criterion.field) }}</span>
                            <span class="text-gray-500">{{ getOperatorLabel(criterion.operator) }}</span>
                            <span v-if="formatCriterionValue(criterion)" class="font-medium text-primary-600">
                                "{{ formatCriterionValue(criterion) }}"
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sample Contacts -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Aperçu des contacts</h2>
                    <p class="text-sm text-gray-500">10 premiers contacts correspondant aux critères</p>
                </div>

                <div v-if="sampleContacts.length === 0" class="p-8 text-center text-gray-500">
                    Aucun contact ne correspond aux critères
                </div>

                <table v-else class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créé le</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="contact in sampleContacts" :key="contact.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <Link
                                    :href="route('contacts.show', contact.id)"
                                    class="text-primary-600 hover:text-primary-700"
                                >
                                    {{ contact.email }}
                                </Link>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ contact.first_name }} {{ contact.last_name }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-700': contact.status === 'subscribed',
                                        'bg-gray-100 text-gray-700': contact.status === 'unsubscribed',
                                        'bg-red-100 text-red-700': contact.status === 'bounced',
                                        'bg-yellow-100 text-yellow-700': contact.status === 'complained',
                                    }"
                                >
                                    {{ contact.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">
                                {{ formatDate(contact.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="segment.contact_count > 10" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <p class="text-sm text-gray-500">
                        + {{ formatNumber(segment.contact_count - 10) }} autres contacts
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

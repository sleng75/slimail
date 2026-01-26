<script setup>
/**
 * Duplicates Management Page
 */

import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    duplicates: Array,
    stats: Object,
});

const selectedGroup = ref(null);
const selectedKeep = ref(null);
const merging = ref(false);

const formatNumber = (num) => new Intl.NumberFormat('fr-FR').format(num || 0);

const openMergeModal = (group) => {
    selectedGroup.value = group;
    selectedKeep.value = group.suggested_keep?.id || group.contacts[0]?.id;
};

const closeMergeModal = () => {
    selectedGroup.value = null;
    selectedKeep.value = null;
};

const confirmMerge = () => {
    if (!selectedGroup.value || !selectedKeep.value) return;

    merging.value = true;

    const mergeIds = selectedGroup.value.contacts
        .filter(c => c.id !== selectedKeep.value)
        .map(c => c.id);

    router.post(route('contacts.duplicates.merge'), {
        keep_id: selectedKeep.value,
        merge_ids: mergeIds,
    }, {
        onFinish: () => {
            merging.value = false;
            closeMergeModal();
        },
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Doublons" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('contacts.index')" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">Gestion des doublons</h1>
                    </div>
                    <p class="text-gray-500">Identifiez et fusionnez les contacts en double</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.duplicate_groups) }}</p>
                            <p class="text-sm text-gray-500">Groupes de doublons</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.total_duplicate_contacts) }}</p>
                            <p class="text-sm text-gray-500">Contacts en double</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.potential_savings) }}</p>
                            <p class="text-sm text-gray-500">Contacts fusionnables</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Duplicates List -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div v-if="duplicates.length === 0" class="p-12 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun doublon</h3>
                    <p class="text-gray-500">Votre liste de contacts ne contient pas de doublons.</p>
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="(group, index) in duplicates"
                        :key="index"
                        class="p-6"
                    >
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                        :class="group.type === 'email' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'">
                                        {{ group.type === 'email' ? 'Email' : 'Téléphone' }}
                                    </span>
                                    <span class="font-medium text-gray-900">{{ group.match_value }}</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ group.count }} contacts en double</p>
                            </div>
                            <button
                                @click="openMergeModal(group)"
                                class="px-4 py-2 text-sm font-medium text-primary-600 border border-primary-300 rounded-lg hover:bg-primary-50 transition-colors"
                            >
                                Fusionner
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="contact in group.contacts"
                                :key="contact.id"
                                class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg"
                                :class="{ 'ring-2 ring-primary-500 bg-primary-50': contact.id === group.suggested_keep?.id }"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate">
                                        {{ contact.first_name }} {{ contact.last_name }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate">{{ contact.email }}</p>
                                    <p class="text-xs text-gray-400">Créé le {{ formatDate(contact.created_at) }}</p>
                                </div>
                                <span
                                    v-if="contact.id === group.suggested_keep?.id"
                                    class="px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 rounded"
                                >
                                    Suggéré
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Merge Modal -->
        <div
            v-if="selectedGroup"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeMergeModal"></div>

                <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Fusionner les contacts</h2>

                    <p class="text-gray-600 mb-4">
                        Sélectionnez le contact à conserver. Les données des autres contacts seront fusionnées puis ils seront supprimés.
                    </p>

                    <div class="space-y-3 mb-6">
                        <label
                            v-for="contact in selectedGroup.contacts"
                            :key="contact.id"
                            class="flex items-center gap-4 p-4 border rounded-lg cursor-pointer transition-colors"
                            :class="selectedKeep === contact.id ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="selectedKeep"
                                :value="contact.id"
                                class="text-primary-600 focus:ring-primary-500"
                            />
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ contact.first_name }} {{ contact.last_name }}</p>
                                <p class="text-sm text-gray-500">{{ contact.email }}</p>
                                <div class="flex items-center gap-4 mt-1 text-xs text-gray-400">
                                    <span>{{ contact.emails_sent || 0 }} emails envoyés</span>
                                    <span>Score: {{ contact.engagement_score || 0 }}%</span>
                                    <span>Créé le {{ formatDate(contact.created_at) }}</span>
                                </div>
                            </div>
                            <span
                                v-if="contact.id === selectedGroup.suggested_keep?.id"
                                class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded"
                            >
                                Recommandé
                            </span>
                        </label>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <p class="font-medium text-yellow-800">Cette action est irréversible</p>
                                <p class="text-sm text-yellow-700">
                                    {{ selectedGroup.count - 1 }} contact(s) seront supprimé(s) après la fusion.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button
                            @click="closeMergeModal"
                            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="confirmMerge"
                            :disabled="merging"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            <span v-if="merging">Fusion en cours...</span>
                            <span v-else>Fusionner</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

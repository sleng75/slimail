<script setup>
/**
 * Segment Edit Page
 */

import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SegmentBuilder from '@/Components/Segments/SegmentBuilder.vue';
import { ref } from 'vue';

const props = defineProps({
    segment: Object,
    availableFields: Array,
    operators: Object,
    matchTypes: Array,
});

const form = useForm({
    name: props.segment.name,
    description: props.segment.description || '',
    match_type: props.segment.match_type,
    criteria: props.segment.criteria || [{ field: '', operator: 'equals', value: '' }],
    is_active: props.segment.is_active,
});

// Preview state
const previewLoading = ref(false);
const previewResult = ref(null);

const submit = () => {
    form.put(route('segments.update', props.segment.id));
};

const preview = async () => {
    if (form.criteria.length === 0 || !form.criteria[0].field) {
        return;
    }

    previewLoading.value = true;
    previewResult.value = null;

    try {
        const response = await fetch(route('segments.preview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({
                match_type: form.match_type,
                criteria: form.criteria,
            }),
        });

        if (response.ok) {
            previewResult.value = await response.json();
        }
    } catch (error) {
        console.error('Preview error:', error);
    } finally {
        previewLoading.value = false;
    }
};

const formatNumber = (num) => new Intl.NumberFormat('fr-FR').format(num || 0);
</script>

<template>
    <AppLayout>
        <Head :title="`Modifier ${segment.name}`" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Modifier le segment</h1>
                <p class="text-gray-500">{{ segment.name }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Informations</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nom du segment *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Description du segment (optionnel)"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                        <span class="text-sm font-medium text-gray-700">Segment actif</span>
                    </div>
                </div>

                <!-- Criteria Builder -->
                <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Critères de segmentation</h2>

                    <SegmentBuilder
                        v-model="form.criteria"
                        v-model:matchType="form.match_type"
                        :availableFields="availableFields"
                        :operators="operators"
                        @preview="preview"
                    />

                    <p v-if="form.errors.criteria" class="text-sm text-red-600">{{ form.errors.criteria }}</p>
                </div>

                <!-- Preview Result -->
                <div v-if="previewLoading || previewResult" class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Prévisualisation</h2>
                        <div v-if="previewLoading" class="flex items-center gap-2 text-gray-500">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Chargement...
                        </div>
                    </div>

                    <div v-if="previewResult">
                        <div class="flex items-center gap-4 mb-4 p-4 bg-primary-50 rounded-lg">
                            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-primary-900">{{ formatNumber(previewResult.count) }}</p>
                                <p class="text-sm text-primary-700">contacts correspondent aux critères</p>
                            </div>
                        </div>

                        <div v-if="previewResult.contacts.length > 0">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Aperçu ({{ previewResult.contacts.length }} premiers)</h3>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="contact in previewResult.contacts" :key="contact.id">
                                            <td class="px-4 py-2 text-gray-900">{{ contact.email }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ contact.first_name }} {{ contact.last_name }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                                    :class="{
                                                        'bg-green-100 text-green-700': contact.status === 'subscribed',
                                                        'bg-gray-100 text-gray-700': contact.status === 'unsubscribed',
                                                        'bg-red-100 text-red-700': contact.status === 'bounced',
                                                    }">
                                                    {{ contact.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a
                        :href="route('segments.show', segment.id)"
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Annuler
                    </a>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50"
                    >
                        <span v-if="form.processing">Enregistrement...</span>
                        <span v-else>Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

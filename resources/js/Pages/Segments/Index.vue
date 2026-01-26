<script setup>
/**
 * Segments Index Page
 * Lists all segments with search and actions
 */

import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    segments: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

// Debounced search
const performSearch = debounce(() => {
    router.get(route('segments.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, performSearch);

const deleteSegment = (segment) => {
    if (confirm(`Supprimer le segment "${segment.name}" ?`)) {
        router.delete(route('segments.destroy', segment.id));
    }
};

const duplicateSegment = (segment) => {
    router.post(route('segments.duplicate', segment.id));
};

const formatNumber = (num) => new Intl.NumberFormat('fr-FR').format(num || 0);
</script>

<template>
    <AppLayout>
        <Head title="Segments" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Segments</h1>
                    <p class="text-gray-500">Créez des segments dynamiques pour cibler vos contacts</p>
                </div>
                <Link
                    :href="route('segments.create')"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                >
                    Nouveau segment
                </Link>
            </div>

            <!-- Search -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher un segment..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>
            </div>

            <!-- Segments List -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div v-if="segments.data.length === 0" class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun segment</h3>
                    <p class="text-gray-500 mb-4">Créez votre premier segment pour cibler vos contacts</p>
                    <Link
                        :href="route('segments.create')"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                    >
                        Créer un segment
                    </Link>
                </div>

                <table v-else class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Segment
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Critères
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contacts
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="segment in segments.data" :key="segment.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <Link
                                    :href="route('segments.show', segment.id)"
                                    class="font-medium text-gray-900 hover:text-primary-600"
                                >
                                    {{ segment.name }}
                                </Link>
                                <p v-if="segment.description" class="text-sm text-gray-500 mt-1 line-clamp-1">
                                    {{ segment.description }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                        :class="segment.match_type === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'">
                                        {{ segment.match_type === 'all' ? 'ET' : 'OU' }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ segment.criteria?.length || 0 }} critère(s)
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900">{{ formatNumber(segment.contact_count) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="segment.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                                >
                                    {{ segment.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('segments.show', segment.id)"
                                        class="p-2 text-gray-400 hover:text-primary-600 transition-colors"
                                        title="Voir"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        :href="route('segments.edit', segment.id)"
                                        class="p-2 text-gray-400 hover:text-primary-600 transition-colors"
                                        title="Modifier"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        @click="duplicateSegment(segment)"
                                        class="p-2 text-gray-400 hover:text-primary-600 transition-colors"
                                        title="Dupliquer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteSegment(segment)"
                                        class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                                        title="Supprimer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="segments.links && segments.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            {{ segments.from }} - {{ segments.to }} sur {{ segments.total }} segments
                        </p>
                        <div class="flex items-center gap-1">
                            <template v-for="link in segments.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-1 rounded text-sm"
                                    :class="link.active ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    lists: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');

const applyFilters = debounce(() => {
    router.get('/contact-lists', {
        search: search.value || undefined,
        type: selectedType.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedType], applyFilters);

const deleteList = (list) => {
    if (confirm(`Voulez-vous vraiment supprimer la liste "${list.name}" ?`)) {
        router.delete(`/contact-lists/${list.id}`);
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Listes de contacts" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900">Listes de contacts</h1>
                    <p class="mt-1 text-sm text-secondary-500">Organisez vos contacts en listes pour vos campagnes</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        href="/contacts"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        Voir les contacts
                    </Link>
                    <Link
                        href="/contact-lists/create"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                    >
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouvelle liste
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher une liste..."
                            class="w-full pl-10 pr-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>
                </div>
                <select
                    v-model="selectedType"
                    class="px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                    <option value="">Tous les types</option>
                    <option value="static">Listes statiques</option>
                    <option value="dynamic">Segments dynamiques</option>
                </select>
            </div>

            <!-- Lists Grid -->
            <div v-if="lists.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="list in lists.data"
                    :key="list.id"
                    class="bg-white rounded-xl border border-gray-100 p-5 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <span
                                :style="{ backgroundColor: list.color || '#6b7280' }"
                                class="w-4 h-4 rounded-full mr-3"
                            ></span>
                            <div>
                                <Link
                                    :href="`/contact-lists/${list.id}`"
                                    class="text-lg font-semibold text-secondary-900 hover:text-primary-600"
                                >
                                    {{ list.name }}
                                </Link>
                                <span
                                    :class="[
                                        'ml-2 px-2 py-0.5 text-xs font-medium rounded',
                                        list.type === 'dynamic' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'
                                    ]"
                                >
                                    {{ list.type === 'dynamic' ? 'Segment' : 'Liste' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1">
                            <Link
                                :href="`/contact-lists/${list.id}/import`"
                                class="p-1.5 text-secondary-400 hover:text-green-600 rounded-lg hover:bg-gray-100"
                                title="Importer des contacts"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </Link>
                            <a
                                :href="`/contact-lists/${list.id}/export`"
                                class="p-1.5 text-secondary-400 hover:text-blue-600 rounded-lg hover:bg-gray-100"
                                title="Exporter les contacts"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                            <Link
                                :href="`/contact-lists/${list.id}/edit`"
                                class="p-1.5 text-secondary-400 hover:text-primary-600 rounded-lg hover:bg-gray-100"
                                title="Modifier"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </Link>
                            <button
                                @click="deleteList(list)"
                                class="p-1.5 text-secondary-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                                title="Supprimer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <p v-if="list.description" class="mt-2 text-sm text-secondary-500 line-clamp-2">
                        {{ list.description }}
                    </p>

                    <div class="mt-4 flex items-center justify-between text-sm">
                        <div class="flex items-center space-x-4">
                            <span class="text-secondary-600">
                                <span class="font-semibold text-secondary-900">{{ list.contacts_count }}</span> contacts
                            </span>
                            <span class="text-green-600">
                                <span class="font-semibold">{{ list.subscribed_count }}</span> abonnés
                            </span>
                        </div>
                        <Link
                            :href="`/contact-lists/${list.id}`"
                            class="text-primary-600 hover:text-primary-700 font-medium"
                        >
                            Voir
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl border border-gray-100 p-12 text-center">
                <svg class="mx-auto w-12 h-12 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-4 text-sm font-medium text-secondary-900">Aucune liste</h3>
                <p class="mt-1 text-sm text-secondary-500">
                    Créez votre première liste pour organiser vos contacts.
                </p>
                <Link
                    href="/contact-lists/create"
                    class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Créer une liste
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="lists.data.length > 0 && lists.links.length > 3" class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <Link
                        v-for="link in lists.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 text-sm rounded-lg',
                            link.active ? 'bg-primary-600 text-white' : 'text-secondary-600 hover:bg-gray-100',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

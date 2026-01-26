<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    tags: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingTag = ref(null);

const colors = [
    '#dc2626', '#ea580c', '#ca8a04', '#16a34a',
    '#0891b2', '#2563eb', '#7c3aed', '#c026d3', '#64748b'
];

const createForm = useForm({
    name: '',
    color: colors[0],
    description: '',
});

const editForm = useForm({
    name: '',
    color: '',
    description: '',
});

const applyFilters = debounce(() => {
    router.get('/tags', {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, applyFilters);

const openEditModal = (tag) => {
    editingTag.value = tag;
    editForm.name = tag.name;
    editForm.color = tag.color;
    editForm.description = tag.description || '';
    showEditModal.value = true;
};

const createTag = () => {
    createForm.post('/tags', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const updateTag = () => {
    editForm.put(`/tags/${editingTag.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingTag.value = null;
        },
    });
};

const deleteTag = (tag) => {
    if (confirm(`Voulez-vous vraiment supprimer le tag "${tag.name}" ?`)) {
        router.delete(`/tags/${tag.id}`);
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Tags" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900">Tags</h1>
                    <p class="mt-1 text-sm text-secondary-500">Organisez vos contacts avec des tags</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        href="/contacts"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        Voir les contacts
                    </Link>
                    <button
                        @click="showCreateModal = true"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                    >
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouveau tag
                        </span>
                    </button>
                </div>
            </div>

            <!-- Search -->
            <div class="max-w-md">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher un tag..."
                        class="w-full pl-10 pr-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>
            </div>

            <!-- Tags Grid -->
            <div v-if="tags.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="tag in tags.data"
                    :key="tag.id"
                    class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <span
                                :style="{ backgroundColor: tag.color }"
                                class="w-4 h-4 rounded-full mr-3"
                            ></span>
                            <div>
                                <h3 class="font-semibold text-secondary-900">{{ tag.name }}</h3>
                                <p class="text-sm text-secondary-500">{{ tag.contacts_count }} contacts</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1">
                            <button
                                @click="openEditModal(tag)"
                                class="p-1.5 text-secondary-400 hover:text-primary-600 rounded-lg hover:bg-gray-100"
                                title="Modifier"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                @click="deleteTag(tag)"
                                class="p-1.5 text-secondary-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                                title="Supprimer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p v-if="tag.description" class="mt-2 text-sm text-secondary-500 line-clamp-2">
                        {{ tag.description }}
                    </p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl border border-gray-100 p-12 text-center">
                <svg class="mx-auto w-12 h-12 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <h3 class="mt-4 text-sm font-medium text-secondary-900">Aucun tag</h3>
                <p class="mt-1 text-sm text-secondary-500">
                    Créez votre premier tag pour organiser vos contacts.
                </p>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Créer un tag
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="tags.data.length > 0 && tags.links.length > 3" class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <Link
                        v-for="link in tags.links"
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

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-secondary-900">Créer un tag</h3>

                    <form @submit.prevent="createTag" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700">
                                Nom <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                placeholder="Ex: Client VIP"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            <p v-if="createForm.errors.name" class="mt-1 text-sm text-red-600">{{ createForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-2">Couleur</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="color in colors"
                                    :key="color"
                                    type="button"
                                    @click="createForm.color = color"
                                    :style="{ backgroundColor: color }"
                                    :class="[
                                        'w-8 h-8 rounded-full transition-transform',
                                        createForm.color === color ? 'ring-2 ring-offset-2 ring-secondary-400 scale-110' : 'hover:scale-110'
                                    ]"
                                ></button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Description</label>
                            <textarea
                                v-model="createForm.description"
                                rows="2"
                                placeholder="Description optionnelle..."
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            ></textarea>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                            >
                                Créer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showEditModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-secondary-900">Modifier le tag</h3>

                    <form @submit.prevent="updateTag" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700">
                                Nom <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-2">Couleur</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="color in colors"
                                    :key="color"
                                    type="button"
                                    @click="editForm.color = color"
                                    :style="{ backgroundColor: color }"
                                    :class="[
                                        'w-8 h-8 rounded-full transition-transform',
                                        editForm.color === color ? 'ring-2 ring-offset-2 ring-secondary-400 scale-110' : 'hover:scale-110'
                                    ]"
                                ></button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Description</label>
                            <textarea
                                v-model="editForm.description"
                                rows="2"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            ></textarea>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

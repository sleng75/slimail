<script setup>
/**
 * Contacts Index Page
 * List and manage contacts with filtering and bulk actions
 */

import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    contacts: Object,
    lists: Array,
    tags: Array,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedList = ref(props.filters.list || '');
const selectedTag = ref(props.filters.tag || '');
const selectedContacts = ref([]);
const showBulkActions = ref(false);
const showDeleteModal = ref(false);
const showAddToListModal = ref(false);
const showAddTagsModal = ref(false);
const selectedListForBulk = ref('');
const selectedTagsForBulk = ref([]);

const allSelected = computed(() => {
    return props.contacts.data.length > 0 &&
        selectedContacts.value.length === props.contacts.data.length;
});

const someSelected = computed(() => {
    return selectedContacts.value.length > 0 && !allSelected.value;
});

const toggleAll = () => {
    if (allSelected.value) {
        selectedContacts.value = [];
    } else {
        selectedContacts.value = props.contacts.data.map(c => c.id);
    }
};

const toggleContact = (id) => {
    const index = selectedContacts.value.indexOf(id);
    if (index === -1) {
        selectedContacts.value.push(id);
    } else {
        selectedContacts.value.splice(index, 1);
    }
};

watch(selectedContacts, (value) => {
    showBulkActions.value = value.length > 0;
});

const applyFilters = debounce(() => {
    router.get('/contacts', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        list: selectedList.value || undefined,
        tag: selectedTag.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedList, selectedTag], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedList.value = '';
    selectedTag.value = '';
};

const deleteContact = (contact) => {
    if (confirm(`Voulez-vous vraiment supprimer ${contact.full_name} ?`)) {
        router.delete(`/contacts/${contact.id}`);
    }
};

const bulkDelete = () => {
    router.post('/contacts/bulk-destroy', {
        ids: selectedContacts.value,
    }, {
        onSuccess: () => {
            selectedContacts.value = [];
            showDeleteModal.value = false;
        },
    });
};

const bulkAddToList = () => {
    if (!selectedListForBulk.value) return;
    router.post('/contacts/bulk-add-to-list', {
        ids: selectedContacts.value,
        list_id: selectedListForBulk.value,
    }, {
        onSuccess: () => {
            selectedContacts.value = [];
            showAddToListModal.value = false;
            selectedListForBulk.value = '';
        },
    });
};

const bulkAddTags = () => {
    if (selectedTagsForBulk.value.length === 0) return;
    router.post('/contacts/bulk-add-tags', {
        ids: selectedContacts.value,
        tag_ids: selectedTagsForBulk.value,
    }, {
        onSuccess: () => {
            selectedContacts.value = [];
            showAddTagsModal.value = false;
            selectedTagsForBulk.value = [];
        },
    });
};

const getStatusLabel = (status) => {
    const labels = {
        subscribed: 'Abonné',
        unsubscribed: 'Désabonné',
        bounced: 'Rebond',
        complained: 'Plainte',
    };
    return labels[status] || status;
};

const getStatusColor = (status) => {
    const colors = {
        subscribed: 'bg-green-100 text-green-800',
        unsubscribed: 'bg-gray-100 text-gray-800',
        bounced: 'bg-orange-100 text-orange-800',
        complained: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AppLayout>
        <Head title="Contacts" />

        <PageContainer size="wide">
            <div class="space-y-section">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl lg:text-3xl font-bold text-secondary-900">Contacts</h1>
                    <p class="text-secondary-500">Gérez vos contacts et listes de diffusion</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        href="/contact-lists"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        Listes
                    </Link>
                    <Link
                        href="/tags"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        Tags
                    </Link>
                    <Link
                        href="/contacts/import"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Importer
                        </span>
                    </Link>
                    <a
                        href="/contacts/export"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Exporter
                        </span>
                    </a>
                    <Link
                        href="/contacts/create"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                    >
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ajouter
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card card-hover">
                    <div class="text-sm font-medium text-secondary-500">Total</div>
                    <div class="mt-1 text-2xl font-bold text-secondary-900">{{ stats.total.toLocaleString() }}</div>
                </div>
                <div class="card card-hover">
                    <div class="text-sm font-medium text-emerald-600">Abonnés</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-700">{{ stats.subscribed.toLocaleString() }}</div>
                </div>
                <div class="card card-hover">
                    <div class="text-sm font-medium text-secondary-500">Désabonnés</div>
                    <div class="mt-1 text-2xl font-bold text-secondary-600">{{ stats.unsubscribed.toLocaleString() }}</div>
                </div>
                <div class="card card-hover">
                    <div class="text-sm font-medium text-amber-600">Rebonds</div>
                    <div class="mt-1 text-2xl font-bold text-amber-700">{{ stats.bounced.toLocaleString() }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card">
                <div class="flex flex-wrap gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher par email, nom..."
                                class="w-full pl-10 pr-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <select
                        v-model="selectedStatus"
                        class="px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="subscribed">Abonnés</option>
                        <option value="unsubscribed">Désabonnés</option>
                        <option value="bounced">Rebonds</option>
                        <option value="complained">Plaintes</option>
                    </select>

                    <!-- List Filter -->
                    <select
                        v-model="selectedList"
                        class="px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">Toutes les listes</option>
                        <option v-for="list in lists" :key="list.id" :value="list.id">
                            {{ list.name }} ({{ list.contacts_count }})
                        </option>
                    </select>

                    <!-- Tag Filter -->
                    <select
                        v-model="selectedTag"
                        class="px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">Tous les tags</option>
                        <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                            {{ tag.name }} ({{ tag.contacts_count }})
                        </option>
                    </select>

                    <!-- Clear Filters -->
                    <button
                        v-if="search || selectedStatus || selectedList || selectedTag"
                        @click="clearFilters"
                        class="px-4 py-2 text-sm text-secondary-600 hover:text-secondary-800"
                    >
                        Effacer les filtres
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div
                v-if="showBulkActions"
                class="flex items-center justify-between p-4 bg-primary-50 rounded-xl border border-primary-200"
            >
                <span class="text-sm font-medium text-primary-800">
                    {{ selectedContacts.length }} contact(s) sélectionné(s)
                </span>
                <div class="flex items-center space-x-2">
                    <button
                        @click="showAddToListModal = true"
                        class="px-3 py-1.5 text-sm font-medium text-primary-700 bg-white rounded-lg border border-primary-300 hover:bg-primary-50"
                    >
                        Ajouter à une liste
                    </button>
                    <button
                        @click="showAddTagsModal = true"
                        class="px-3 py-1.5 text-sm font-medium text-primary-700 bg-white rounded-lg border border-primary-300 hover:bg-primary-50"
                    >
                        Ajouter des tags
                    </button>
                    <button
                        @click="showDeleteModal = true"
                        class="px-3 py-1.5 text-sm font-medium text-red-700 bg-white rounded-lg border border-red-300 hover:bg-red-50"
                    >
                        Supprimer
                    </button>
                </div>
            </div>

            <!-- Contacts Table -->
            <div class="card overflow-hidden !p-0">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-12 px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    :indeterminate="someSelected"
                                    @change="toggleAll"
                                    class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Listes
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Tags
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Engagement
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="contact in contacts.data"
                            :key="contact.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedContacts.includes(contact.id)"
                                    @change="toggleContact(contact.id)"
                                    class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary-700">
                                            {{ contact.first_name?.charAt(0) || contact.email.charAt(0).toUpperCase() }}{{ contact.last_name?.charAt(0) || '' }}
                                        </span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-secondary-900">
                                            {{ contact.full_name }}
                                        </div>
                                        <div class="text-sm text-secondary-500">
                                            {{ contact.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(contact.status)]">
                                    {{ getStatusLabel(contact.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="list in contact.lists.slice(0, 2)"
                                        :key="list.id"
                                        class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded"
                                    >
                                        {{ list.name }}
                                    </span>
                                    <span
                                        v-if="contact.lists.length > 2"
                                        class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 rounded"
                                    >
                                        +{{ contact.lists.length - 2 }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in contact.tags.slice(0, 3)"
                                        :key="tag.id"
                                        :style="{ backgroundColor: tag.color + '20', color: tag.color }"
                                        class="px-2 py-0.5 text-xs font-medium rounded"
                                    >
                                        {{ tag.name }}
                                    </span>
                                    <span
                                        v-if="contact.tags.length > 3"
                                        class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 rounded"
                                    >
                                        +{{ contact.tags.length - 3 }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div
                                            :style="{ width: contact.engagement_score + '%' }"
                                            :class="[
                                                'h-full rounded-full',
                                                contact.engagement_score >= 70 ? 'bg-green-500' :
                                                contact.engagement_score >= 40 ? 'bg-yellow-500' : 'bg-red-500'
                                            ]"
                                        ></div>
                                    </div>
                                    <span class="ml-2 text-xs text-secondary-500">
                                        {{ contact.engagement_score }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <Link
                                        :href="`/contacts/${contact.id}`"
                                        class="p-1.5 text-secondary-400 hover:text-secondary-600 rounded-lg hover:bg-gray-100"
                                        title="Voir"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        :href="`/contacts/${contact.id}/edit`"
                                        class="p-1.5 text-secondary-400 hover:text-primary-600 rounded-lg hover:bg-gray-100"
                                        title="Modifier"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        @click="deleteContact(contact)"
                                        class="p-1.5 text-secondary-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                                        title="Supprimer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="contacts.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <svg class="mx-auto w-12 h-12 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <h3 class="mt-4 text-sm font-medium text-secondary-900">Aucun contact</h3>
                                <p class="mt-1 text-sm text-secondary-500">
                                    Commencez par ajouter votre premier contact.
                                </p>
                                <Link
                                    href="/contacts/create"
                                    class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Ajouter un contact
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="contacts.data.length > 0" class="px-4 py-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-secondary-500">
                            Affichage de {{ contacts.from }} à {{ contacts.to }} sur {{ contacts.total }} contacts
                        </div>
                        <div class="flex items-center space-x-2">
                            <Link
                                v-for="link in contacts.links"
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
            </div>
            </div>
        </PageContainer>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-secondary-900">Confirmer la suppression</h3>
                    <p class="mt-2 text-sm text-secondary-500">
                        Voulez-vous vraiment supprimer {{ selectedContacts.length }} contact(s) ? Cette action est irréversible.
                    </p>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="bulkDelete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add to List Modal -->
        <div v-if="showAddToListModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showAddToListModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-secondary-900">Ajouter à une liste</h3>
                    <div class="mt-4">
                        <select
                            v-model="selectedListForBulk"
                            class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="">Sélectionner une liste</option>
                            <option v-for="list in lists" :key="list.id" :value="list.id">
                                {{ list.name }}
                            </option>
                        </select>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="showAddToListModal = false"
                            class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="bulkAddToList"
                            :disabled="!selectedListForBulk"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            Ajouter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Tags Modal -->
        <div v-if="showAddTagsModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showAddTagsModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-secondary-900">Ajouter des tags</h3>
                    <div class="mt-4 space-y-2 max-h-60 overflow-y-auto">
                        <label
                            v-for="tag in tags"
                            :key="tag.id"
                            class="flex items-center p-2 rounded-lg hover:bg-gray-50 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="tag.id"
                                v-model="selectedTagsForBulk"
                                class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                            />
                            <span
                                :style="{ backgroundColor: tag.color + '20', color: tag.color }"
                                class="ml-3 px-2 py-0.5 text-sm font-medium rounded"
                            >
                                {{ tag.name }}
                            </span>
                        </label>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="showAddTagsModal = false"
                            class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="bulkAddTags"
                            :disabled="selectedTagsForBulk.length === 0"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            Ajouter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

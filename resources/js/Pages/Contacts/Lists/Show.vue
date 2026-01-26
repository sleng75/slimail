<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    list: Object,
    contacts: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');

const applyFilters = debounce(() => {
    router.get(`/contact-lists/${props.list.id}`, {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus], applyFilters);

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
        <Head :title="list.name" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <Link
                        href="/contact-lists"
                        class="inline-flex items-center text-sm text-secondary-500 hover:text-secondary-700"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour aux listes
                    </Link>
                    <div class="mt-2 flex items-center">
                        <span
                            :style="{ backgroundColor: list.color || '#6b7280' }"
                            class="w-4 h-4 rounded-full mr-3"
                        ></span>
                        <h1 class="text-2xl font-bold text-secondary-900">{{ list.name }}</h1>
                        <span
                            :class="[
                                'ml-3 px-2.5 py-1 text-sm font-medium rounded',
                                list.type === 'dynamic' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'
                            ]"
                        >
                            {{ list.type === 'dynamic' ? 'Segment' : 'Liste' }}
                        </span>
                    </div>
                    <p v-if="list.description" class="mt-1 text-sm text-secondary-500">{{ list.description }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="`/contact-lists/${list.id}/import`"
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
                        :href="`/contact-lists/${list.id}/export`"
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
                        :href="`/contact-lists/${list.id}/edit`"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        Modifier
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="p-4 bg-white rounded-xl border border-gray-100">
                    <div class="text-sm font-medium text-secondary-500">Total contacts</div>
                    <div class="mt-1 text-2xl font-bold text-secondary-900">{{ list.contacts_count }}</div>
                </div>
                <div class="p-4 bg-white rounded-xl border border-gray-100">
                    <div class="text-sm font-medium text-green-600">Abonnés</div>
                    <div class="mt-1 text-2xl font-bold text-green-700">{{ list.subscribed_count }}</div>
                </div>
                <div class="p-4 bg-white rounded-xl border border-gray-100">
                    <div class="text-sm font-medium text-secondary-500">Désabonnés</div>
                    <div class="mt-1 text-2xl font-bold text-secondary-600">{{ list.unsubscribed_count }}</div>
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
                            placeholder="Rechercher dans cette liste..."
                            class="w-full pl-10 pr-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>
                </div>
                <select
                    v-model="selectedStatus"
                    class="px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                    <option value="">Tous les statuts</option>
                    <option value="subscribed">Abonnés</option>
                    <option value="unsubscribed">Désabonnés</option>
                    <option value="bounced">Rebonds</option>
                </select>
            </div>

            <!-- Contacts Table -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">
                                Tags
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
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary-700">
                                            {{ contact.first_name?.charAt(0) || contact.email.charAt(0).toUpperCase() }}{{ contact.last_name?.charAt(0) || '' }}
                                        </span>
                                    </div>
                                    <div class="ml-3">
                                        <Link
                                            :href="`/contacts/${contact.id}`"
                                            class="text-sm font-medium text-secondary-900 hover:text-primary-600"
                                        >
                                            {{ contact.full_name }}
                                        </Link>
                                        <div class="text-sm text-secondary-500">{{ contact.email }}</div>
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
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="`/contacts/${contact.id}`"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-700"
                                >
                                    Voir
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="contacts.data.length === 0">
                            <td colspan="4" class="px-4 py-12 text-center">
                                <svg class="mx-auto w-12 h-12 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <h3 class="mt-4 text-sm font-medium text-secondary-900">Aucun contact dans cette liste</h3>
                                <p class="mt-1 text-sm text-secondary-500">
                                    Ajoutez des contacts à cette liste depuis la page des contacts.
                                </p>
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
    </AppLayout>
</template>

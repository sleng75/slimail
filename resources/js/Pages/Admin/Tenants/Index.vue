<script setup>
/**
 * Tenant Management Index
 */

import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    tenants: Object,
    plans: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const planFilter = ref(props.filters?.plan || '');

const applyFilters = debounce(() => {
    router.get(route('admin.tenants.index'), {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        plan: planFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch([search, statusFilter, planFilter], applyFilters);

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-emerald-100 text-emerald-700',
        trialing: 'bg-blue-100 text-blue-700',
        past_due: 'bg-yellow-100 text-yellow-700',
        canceled: 'bg-red-100 text-red-700',
        suspended: 'bg-gray-100 text-gray-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num || 0);
};
</script>

<template>
    <AdminLayout>
        <Head title="Gestion des Tenants" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tenants</h1>
                    <p class="text-gray-500">Gestion des comptes clients</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                    />

                    <select
                        v-model="statusFilter"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="all">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="trialing">En essai</option>
                        <option value="past_due">Paiement en retard</option>
                        <option value="canceled">Annulé</option>
                        <option value="suspended">Suspendu</option>
                    </select>

                    <select
                        v-model="planFilter"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">Tous les forfaits</option>
                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                            {{ plan.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Tenants Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tenant
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Propriétaire
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Forfait
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contacts
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Emails envoyés
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Inscrit le
                            </th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="font-medium text-gray-900">{{ tenant.name }}</div>
                                    <div class="text-sm text-gray-500">{{ tenant.slug }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="tenant.owner">
                                    <div class="text-sm text-gray-900">{{ tenant.owner.name }}</div>
                                    <div class="text-sm text-gray-500">{{ tenant.owner.email }}</div>
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="tenant.subscription"
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getStatusColor(tenant.subscription.status)
                                    ]"
                                >
                                    {{ tenant.subscription.plan }}
                                </span>
                                <span v-else class="text-gray-400">Aucun</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatNumber(tenant.contacts_count) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatNumber(tenant.emails_sent) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ tenant.created_at }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link
                                    :href="route('admin.tenants.show', tenant.id)"
                                    class="text-primary-600 hover:text-primary-900"
                                >
                                    Voir
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="tenants.links?.length > 3" class="px-6 py-4 border-t border-gray-200 flex justify-center gap-2">
                    <template v-for="link in tenants.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 rounded-lg text-sm',
                                link.active
                                    ? 'bg-primary-600 text-white'
                                    : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

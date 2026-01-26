<script setup>
/**
 * Invoices List Page
 */

import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    invoices: Object,
});

// Status color helper
const getStatusClass = (color) => {
    const classes = {
        green: 'bg-emerald-100 text-emerald-700',
        yellow: 'bg-yellow-100 text-yellow-700',
        red: 'bg-red-100 text-red-700',
        blue: 'bg-blue-100 text-blue-700',
        gray: 'bg-gray-100 text-gray-700',
        purple: 'bg-purple-100 text-purple-700',
    };
    return classes[color] || classes.gray;
};
</script>

<template>
    <AppLayout>
        <Head title="Factures" />

        <PageContainer>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-secondary-900">Factures</h1>
                        <p class="mt-1 text-secondary-500">Historique de vos factures</p>
                    </div>
                    <Link
                        :href="route('billing.index')"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-secondary-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour
                    </Link>
                </div>

                <!-- Invoices Table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div v-if="invoices.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Numéro
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Échéance
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Montant
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="invoice in invoices.data"
                                    :key="invoice.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-medium text-secondary-900">{{ invoice.number }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-secondary-600">
                                        {{ invoice.issue_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="invoice.is_overdue ? 'text-red-600 font-medium' : 'text-secondary-600'">
                                            {{ invoice.due_date }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(invoice.status_color)]">
                                            {{ invoice.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-secondary-900">
                                        {{ invoice.total }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link
                                                :href="route('billing.invoices.show', invoice.id)"
                                                class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                            >
                                                Voir
                                            </Link>
                                            <a
                                                :href="route('billing.invoices.download', invoice.id)"
                                                target="_blank"
                                                class="text-secondary-500 hover:text-secondary-700 text-sm"
                                            >
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="px-6 py-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-secondary-900 mb-2">Aucune facture</h3>
                        <p class="text-secondary-500">Vos factures apparaîtront ici</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="invoices.data.length > 0 && invoices.last_page > 1" class="px-6 py-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-secondary-500">
                                Page {{ invoices.current_page }} sur {{ invoices.last_page }}
                            </p>
                            <div class="flex gap-2">
                                <Link
                                    v-if="invoices.prev_page_url"
                                    :href="invoices.prev_page_url"
                                    class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-secondary-600 hover:bg-gray-50"
                                >
                                    Précédent
                                </Link>
                                <Link
                                    v-if="invoices.next_page_url"
                                    :href="invoices.next_page_url"
                                    class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-secondary-600 hover:bg-gray-50"
                                >
                                    Suivant
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

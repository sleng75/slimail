<script setup>
/**
 * Payments History Page
 */

import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    payments: Object,
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
        <Head title="Paiements" />

        <PageContainer>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-secondary-900">Paiements</h1>
                        <p class="mt-1 text-secondary-500">Historique de vos paiements</p>
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

                <!-- Payments Table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div v-if="payments.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Transaction
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Méthode
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Facture
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        Montant
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="payment in payments.data"
                                    :key="payment.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span v-if="payment.is_refund" class="text-purple-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </span>
                                            <span class="font-medium text-secondary-900">{{ payment.transaction_id }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-secondary-600">
                                        {{ payment.date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-secondary-600">
                                        {{ payment.payment_method }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="payment.invoice_number" class="text-primary-600">
                                            {{ payment.invoice_number }}
                                        </span>
                                        <span v-else class="text-secondary-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(payment.status_color)]">
                                            {{ payment.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span :class="['font-semibold', payment.is_refund ? 'text-purple-600' : 'text-secondary-900']">
                                            {{ payment.amount }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="px-6 py-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-secondary-900 mb-2">Aucun paiement</h3>
                        <p class="text-secondary-500">Vos paiements apparaîtront ici</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="payments.data.length > 0 && payments.last_page > 1" class="px-6 py-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-secondary-500">
                                Page {{ payments.current_page }} sur {{ payments.last_page }}
                            </p>
                            <div class="flex gap-2">
                                <Link
                                    v-if="payments.prev_page_url"
                                    :href="payments.prev_page_url"
                                    class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-secondary-600 hover:bg-gray-50"
                                >
                                    Précédent
                                </Link>
                                <Link
                                    v-if="payments.next_page_url"
                                    :href="payments.next_page_url"
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

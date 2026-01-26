<script setup>
/**
 * Invoice Detail Page
 */

import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    invoice: Object,
});

// Status color helper
const getStatusClass = (color) => {
    const classes = {
        green: 'bg-emerald-100 text-emerald-700',
        yellow: 'bg-yellow-100 text-yellow-700',
        red: 'bg-red-100 text-red-700',
        blue: 'bg-blue-100 text-blue-700',
        gray: 'bg-gray-100 text-gray-700',
    };
    return classes[color] || classes.gray;
};
</script>

<template>
    <AppLayout>
        <Head :title="`Facture ${invoice.number}`" />

        <PageContainer>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-secondary-900">Facture {{ invoice.number }}</h1>
                        <p class="mt-1 text-secondary-500">Émise le {{ invoice.issue_date }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('billing.invoices')"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-secondary-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour
                        </Link>
                        <a
                            :href="route('billing.invoices.download', invoice.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Télécharger PDF
                        </a>
                    </div>
                </div>

                <!-- Invoice Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <!-- Invoice Header -->
                    <div class="px-8 py-6 border-b border-gray-100 bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-xl font-bold text-secondary-900">FACTURE</h2>
                                <p class="text-secondary-600 mt-1">{{ invoice.number }}</p>
                            </div>
                            <span :class="['px-3 py-1.5 text-sm font-medium rounded-full', getStatusClass(invoice.status_color)]">
                                {{ invoice.status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="px-8 py-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- From -->
                            <div>
                                <h3 class="text-sm font-semibold text-secondary-500 uppercase tracking-wider mb-3">De</h3>
                                <div class="text-secondary-900">
                                    <p class="font-semibold">SLIMAT SARL</p>
                                    <p class="text-secondary-600">Abidjan, Côte d'Ivoire</p>
                                    <p class="text-secondary-600">contact@slimail.com</p>
                                </div>
                            </div>

                            <!-- To -->
                            <div>
                                <h3 class="text-sm font-semibold text-secondary-500 uppercase tracking-wider mb-3">Facturé à</h3>
                                <div class="text-secondary-900">
                                    <p class="font-semibold">{{ invoice.billing_name }}</p>
                                    <p v-if="invoice.billing_address" class="text-secondary-600">{{ invoice.billing_address }}</p>
                                    <p v-if="invoice.billing_city || invoice.billing_country" class="text-secondary-600">
                                        {{ [invoice.billing_city, invoice.billing_country].filter(Boolean).join(', ') }}
                                    </p>
                                    <p v-if="invoice.billing_email" class="text-secondary-600">{{ invoice.billing_email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8 pt-6 border-t border-gray-100">
                            <div>
                                <p class="text-sm text-secondary-500">Date d'émission</p>
                                <p class="font-medium text-secondary-900">{{ invoice.issue_date }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary-500">Date d'échéance</p>
                                <p class="font-medium text-secondary-900">{{ invoice.due_date }}</p>
                            </div>
                            <div v-if="invoice.paid_at">
                                <p class="text-sm text-secondary-500">Date de paiement</p>
                                <p class="font-medium text-emerald-600">{{ invoice.paid_at }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="px-8 py-6 border-t border-gray-100">
                        <h3 class="text-sm font-semibold text-secondary-500 uppercase tracking-wider mb-4">Détails</h3>
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="py-3 text-left text-sm font-semibold text-secondary-700">Description</th>
                                    <th class="py-3 text-right text-sm font-semibold text-secondary-700">Qté</th>
                                    <th class="py-3 text-right text-sm font-semibold text-secondary-700">Prix unitaire</th>
                                    <th class="py-3 text-right text-sm font-semibold text-secondary-700">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in invoice.line_items"
                                    :key="index"
                                    class="border-b border-gray-50"
                                >
                                    <td class="py-4 text-secondary-900">{{ item.description }}</td>
                                    <td class="py-4 text-right text-secondary-600">{{ item.quantity }}</td>
                                    <td class="py-4 text-right text-secondary-600">{{ item.unit_price_formatted }}</td>
                                    <td class="py-4 text-right font-medium text-secondary-900">{{ item.total_formatted }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="px-8 py-6 bg-gray-50 border-t border-gray-100">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-2">
                                <div class="flex justify-between text-secondary-600">
                                    <span>Sous-total</span>
                                    <span>{{ invoice.subtotal }}</span>
                                </div>
                                <div v-if="invoice.discount_amount > 0" class="flex justify-between text-emerald-600">
                                    <span>Remise</span>
                                    <span>-{{ invoice.discount }}</span>
                                </div>
                                <div v-if="invoice.tax_amount > 0" class="flex justify-between text-secondary-600">
                                    <span>TVA ({{ invoice.tax_rate }}%)</span>
                                    <span>{{ invoice.tax }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold text-secondary-900 pt-2 border-t border-gray-200">
                                    <span>Total</span>
                                    <span>{{ invoice.total }}</span>
                                </div>
                                <div v-if="invoice.amount_paid > 0" class="flex justify-between text-emerald-600">
                                    <span>Payé</span>
                                    <span>{{ invoice.paid }}</span>
                                </div>
                                <div v-if="invoice.amount_due > 0" class="flex justify-between text-lg font-bold text-primary-600 pt-2 border-t border-gray-200">
                                    <span>Reste à payer</span>
                                    <span>{{ invoice.due }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="invoice.notes" class="px-8 py-6 border-t border-gray-100">
                        <h3 class="text-sm font-semibold text-secondary-500 uppercase tracking-wider mb-2">Notes</h3>
                        <p class="text-secondary-600">{{ invoice.notes }}</p>
                    </div>

                    <!-- Actions -->
                    <div v-if="invoice.status === 'pending' || invoice.status === 'overdue'" class="px-8 py-6 border-t border-gray-100 bg-gray-50">
                        <div class="flex justify-end">
                            <Link
                                :href="route('billing.invoices.pay', invoice.id)"
                                method="post"
                                as="button"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Payer maintenant
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

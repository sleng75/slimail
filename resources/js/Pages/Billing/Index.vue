<script setup>
/**
 * Billing Dashboard
 * Overview of subscription, invoices and payments
 */

import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    subscription: Object,
    recentInvoices: Array,
    recentPayments: Array,
    hasUnpaidInvoices: Boolean,
});

// Format number
const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
};

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

// Progress bar width
const getProgressWidth = (percent) => {
    return `${Math.min(100, Math.max(0, percent))}%`;
};

// Progress bar color
const getProgressColor = (percent) => {
    if (percent >= 90) return 'bg-red-500';
    if (percent >= 75) return 'bg-yellow-500';
    return 'bg-emerald-500';
};
</script>

<template>
    <AppLayout>
        <Head title="Facturation" />

        <PageContainer>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-secondary-900">Facturation</h1>
                        <p class="mt-1 text-secondary-500">Gérez votre abonnement et vos paiements</p>
                    </div>
                    <Link
                        :href="route('billing.plans')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Voir les forfaits
                    </Link>
                </div>

                <!-- Alert for unpaid invoices -->
                <div v-if="hasUnpaidInvoices" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-xl">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Vous avez des factures impayées.
                                <Link :href="route('billing.invoices')" class="font-medium underline hover:text-yellow-600">
                                    Voir les factures
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Current Subscription -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-secondary-900">Abonnement actuel</h2>
                    </div>

                    <div v-if="subscription" class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                            <!-- Plan info -->
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl font-bold text-secondary-900">{{ subscription.plan.name }}</span>
                                    <span v-if="subscription.on_trial" class="px-2.5 py-1 bg-violet-100 text-violet-700 text-xs font-medium rounded-full">
                                        Essai - {{ subscription.trial_days_remaining }} jours restants
                                    </span>
                                    <span v-else class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs font-medium rounded-full">
                                        Actif
                                    </span>
                                </div>
                                <p class="mt-1 text-secondary-500">
                                    {{ subscription.billing_cycle === 'yearly' ? 'Annuel' : 'Mensuel' }} - {{ subscription.plan.price }}
                                </p>
                                <p class="mt-2 text-sm text-secondary-400">
                                    <span v-if="subscription.ends_at">Renouvellement le {{ subscription.ends_at }}</span>
                                    <span v-else>Depuis le {{ subscription.starts_at }}</span>
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-3">
                                <Link
                                    :href="route('billing.plans')"
                                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-secondary-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors"
                                >
                                    Changer de forfait
                                </Link>
                            </div>
                        </div>

                        <!-- Usage -->
                        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Emails -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-secondary-600">Emails ce mois</span>
                                    <span class="text-sm text-secondary-500">
                                        {{ formatNumber(subscription.usage.emails.used) }}
                                        <span v-if="subscription.usage.emails.limit > 0">
                                            / {{ formatNumber(subscription.usage.emails.limit) }}
                                        </span>
                                        <span v-else>/ Illimité</span>
                                    </span>
                                </div>
                                <div v-if="subscription.usage.emails.limit > 0" class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        :class="getProgressColor(subscription.usage.emails.percent)"
                                        :style="{ width: getProgressWidth(subscription.usage.emails.percent) }"
                                        class="h-full rounded-full transition-all"
                                    ></div>
                                </div>
                            </div>

                            <!-- Contacts -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-secondary-600">Contacts</span>
                                    <span class="text-sm text-secondary-500">
                                        {{ formatNumber(subscription.usage.contacts.count) }}
                                        <span v-if="subscription.usage.contacts.limit > 0">
                                            / {{ formatNumber(subscription.usage.contacts.limit) }}
                                        </span>
                                        <span v-else>/ Illimité</span>
                                    </span>
                                </div>
                                <div v-if="subscription.usage.contacts.limit > 0" class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        :class="getProgressColor(subscription.usage.contacts.percent)"
                                        :style="{ width: getProgressWidth(subscription.usage.contacts.percent) }"
                                        class="h-full rounded-full transition-all"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-6 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-secondary-900 mb-2">Aucun abonnement actif</h3>
                        <p class="text-secondary-500 mb-4">Choisissez un forfait pour commencer</p>
                        <Link
                            :href="route('billing.plans')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors"
                        >
                            Voir les forfaits
                        </Link>
                    </div>
                </div>

                <!-- Grid: Invoices & Payments -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Invoices -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-secondary-900">Factures récentes</h3>
                            <Link :href="route('billing.invoices')" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                                Voir tout
                            </Link>
                        </div>

                        <div v-if="recentInvoices.length > 0" class="divide-y divide-gray-100">
                            <div
                                v-for="invoice in recentInvoices"
                                :key="invoice.id"
                                class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
                            >
                                <div>
                                    <p class="font-medium text-secondary-900">{{ invoice.number }}</p>
                                    <p class="text-sm text-secondary-500">{{ invoice.issue_date }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(invoice.status_color)]">
                                        {{ invoice.status_label }}
                                    </span>
                                    <span class="font-semibold text-secondary-900">{{ invoice.total }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="px-6 py-8 text-center text-secondary-500">
                            Aucune facture
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-secondary-900">Paiements récents</h3>
                            <Link :href="route('billing.payments')" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                                Voir tout
                            </Link>
                        </div>

                        <div v-if="recentPayments.length > 0" class="divide-y divide-gray-100">
                            <div
                                v-for="payment in recentPayments"
                                :key="payment.id"
                                class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
                            >
                                <div>
                                    <p class="font-medium text-secondary-900">{{ payment.payment_method }}</p>
                                    <p class="text-sm text-secondary-500">{{ payment.date }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(payment.status_color)]">
                                        {{ payment.status_label }}
                                    </span>
                                    <span class="font-semibold text-secondary-900">{{ payment.amount }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="px-6 py-8 text-center text-secondary-500">
                            Aucun paiement
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

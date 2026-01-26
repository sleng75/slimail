<script setup>
/**
 * Plan Management Index
 */

import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    plans: Array,
    totals: Object,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

const formatLimit = (limit) => {
    if (limit === -1) return 'Illimité';
    return new Intl.NumberFormat('fr-FR').format(limit);
};

const deletePlan = (plan) => {
    if (confirm(`Supprimer le forfait "${plan.name}" ?`)) {
        router.delete(route('admin.plans.destroy', plan.id));
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Gestion des Forfaits" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Forfaits</h1>
                    <p class="text-gray-500">Gestion des plans d'abonnement</p>
                </div>
                <Link
                    :href="route('admin.plans.create')"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                >
                    Nouveau forfait
                </Link>
            </div>

            <!-- Totals -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-sm text-gray-500">Forfaits</p>
                    <p class="text-2xl font-bold">{{ totals.total_plans }}</p>
                    <p class="text-xs text-gray-400">{{ totals.active_plans }} actifs</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-sm text-gray-500">Abonnés</p>
                    <p class="text-2xl font-bold">{{ totals.total_subscribers }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <p class="text-sm text-gray-500">MRR estimé</p>
                    <p class="text-2xl font-bold">{{ formatCurrency(totals.estimated_mrr) }}</p>
                </div>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="[
                        'bg-white rounded-xl shadow-sm overflow-hidden',
                        plan.is_featured ? 'ring-2 ring-primary-500' : '',
                        !plan.is_active ? 'opacity-60' : ''
                    ]"
                >
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ plan.name }}</h3>
                                <p class="text-sm text-gray-500">{{ plan.slug }}</p>
                            </div>
                            <div v-if="plan.is_featured" class="px-2 py-1 bg-primary-100 text-primary-700 rounded text-xs font-medium">
                                Populaire
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="px-6 py-4 bg-gray-50">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-gray-900">{{ formatCurrency(plan.price_monthly) }}</span>
                            <span class="text-gray-500">/mois</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ formatCurrency(plan.price_yearly) }}/an
                        </p>
                    </div>

                    <!-- Limits -->
                    <div class="px-6 py-4 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Emails/mois</span>
                            <span class="font-medium">{{ formatLimit(plan.email_limit) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Contacts</span>
                            <span class="font-medium">{{ formatLimit(plan.contact_limit) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Abonnés actifs</span>
                            <span class="font-medium text-emerald-600">{{ plan.active_subscriptions }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Revenus mensuels</span>
                            <span class="font-medium">{{ formatCurrency(plan.monthly_revenue) }}</span>
                        </div>
                    </div>

                    <!-- Features -->
                    <div v-if="plan.features?.length" class="px-6 py-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500 uppercase mb-2">Fonctionnalités</p>
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="feature in plan.features.slice(0, 5)"
                                :key="feature"
                                class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs"
                            >
                                {{ feature }}
                            </span>
                            <span v-if="plan.features.length > 5" class="text-xs text-gray-400">
                                +{{ plan.features.length - 5 }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <span
                            :class="[
                                'px-2 py-1 rounded text-xs font-medium',
                                plan.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'
                            ]"
                        >
                            {{ plan.is_active ? 'Actif' : 'Inactif' }}
                        </span>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('admin.plans.edit', plan.id)"
                                class="p-2 text-gray-400 hover:text-primary-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </Link>
                            <button
                                v-if="plan.active_subscriptions === 0"
                                @click="deletePlan(plan)"
                                class="p-2 text-gray-400 hover:text-red-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

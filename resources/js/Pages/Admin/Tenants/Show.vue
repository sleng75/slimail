<script setup>
/**
 * Tenant Details Page
 */

import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tenant: Object,
    stats: Object,
    recentCampaigns: Array,
    invoices: Array,
    plans: Array,
});

const showPlanModal = ref(false);
const planForm = useForm({
    plan_id: props.tenant.subscription?.plan?.id || '',
    billing_period: props.tenant.subscription?.billing_period || 'monthly',
    action: 'change',
});

const formatNumber = (num) => new Intl.NumberFormat('fr-FR').format(num || 0);
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

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

const changePlan = () => {
    planForm.post(route('admin.tenants.change-subscription', props.tenant.id), {
        onSuccess: () => {
            showPlanModal.value = false;
        },
    });
};

const suspendTenant = () => {
    if (confirm('Suspendre ce tenant ?')) {
        router.post(route('admin.tenants.suspend', props.tenant.id));
    }
};

const reactivateTenant = () => {
    router.post(route('admin.tenants.reactivate', props.tenant.id));
};

const deleteTenant = () => {
    if (confirm('ATTENTION: Cette action est irréversible. Supprimer définitivement ce tenant et toutes ses données ?')) {
        router.delete(route('admin.tenants.destroy', props.tenant.id));
    }
};

const impersonate = () => {
    if (confirm('Se connecter en tant que ce tenant ?')) {
        router.post(route('admin.tenants.impersonate', props.tenant.id));
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Tenant: ${tenant.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('admin.tenants.index')"
                        class="p-2 text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ tenant.name }}</h1>
                        <p class="text-gray-500">{{ tenant.email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="impersonate"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                    >
                        Se connecter comme
                    </button>
                    <button
                        v-if="tenant.subscription?.status !== 'suspended'"
                        @click="suspendTenant"
                        class="px-4 py-2 border border-yellow-300 text-yellow-700 rounded-lg hover:bg-yellow-50"
                    >
                        Suspendre
                    </button>
                    <button
                        v-else
                        @click="reactivateTenant"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"
                    >
                        Réactiver
                    </button>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <p class="text-sm text-gray-500">Contacts</p>
                            <p class="text-2xl font-bold">{{ formatNumber(stats.contacts.total) }}</p>
                            <p class="text-xs text-gray-400">{{ stats.contacts.subscribed }} abonnés</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <p class="text-sm text-gray-500">Campagnes</p>
                            <p class="text-2xl font-bold">{{ formatNumber(stats.campaigns.total) }}</p>
                            <p class="text-xs text-gray-400">{{ stats.campaigns.sent }} envoyées</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <p class="text-sm text-gray-500">Emails envoyés</p>
                            <p class="text-2xl font-bold">{{ formatNumber(stats.emails.sent) }}</p>
                            <p class="text-xs text-gray-400">{{ stats.emails.opened }} ouverts</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <p class="text-sm text-gray-500">Automatisations</p>
                            <p class="text-2xl font-bold">{{ formatNumber(stats.automations.total) }}</p>
                            <p class="text-xs text-gray-400">{{ stats.automations.active }} actives</p>
                        </div>
                    </div>

                    <!-- Users -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold">Utilisateurs</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="user in tenant.users"
                                :key="user.id"
                                class="px-6 py-4 flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                        <span class="text-gray-600 font-medium">{{ user.name?.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ user.name }}</p>
                                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">{{ user.role }}</span>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ user.last_login_at || 'Jamais connecté' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Campaigns -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold">Campagnes récentes</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="campaign in recentCampaigns"
                                :key="campaign.id"
                                class="px-6 py-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ campaign.name }}</p>
                                        <p class="text-sm text-gray-500">Par {{ campaign.creator?.name }}</p>
                                    </div>
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded text-xs font-medium',
                                            campaign.status === 'sent' ? 'bg-emerald-100 text-emerald-700' :
                                            campaign.status === 'draft' ? 'bg-gray-100 text-gray-700' :
                                            'bg-blue-100 text-blue-700'
                                        ]"
                                    >
                                        {{ campaign.status }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="!recentCampaigns?.length" class="px-6 py-8 text-center text-gray-500">
                                Aucune campagne
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Subscription & Actions -->
                <div class="space-y-6">
                    <!-- Subscription -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">Abonnement</h2>
                        <div v-if="tenant.subscription" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Forfait</span>
                                <span class="font-medium">{{ tenant.subscription.plan?.name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Statut</span>
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getStatusColor(tenant.subscription.status)
                                    ]"
                                >
                                    {{ tenant.subscription.status }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Période</span>
                                <span class="font-medium">{{ tenant.subscription.billing_period }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Fin période</span>
                                <span class="font-medium">{{ tenant.subscription.current_period_end }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Emails ce mois</span>
                                <span class="font-medium">{{ formatNumber(tenant.subscription.emails_sent_this_month) }}</span>
                            </div>
                            <button
                                @click="showPlanModal = true"
                                class="w-full mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                            >
                                Modifier le forfait
                            </button>
                        </div>
                        <div v-else class="text-center py-4">
                            <p class="text-gray-500 mb-4">Aucun abonnement actif</p>
                            <button
                                @click="showPlanModal = true"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                            >
                                Attribuer un forfait
                            </button>
                        </div>
                    </div>

                    <!-- Invoices -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">Dernières factures</h2>
                        <div v-if="invoices?.length" class="space-y-3">
                            <div
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
                            >
                                <div>
                                    <p class="text-sm font-medium">{{ invoice.number }}</p>
                                    <p class="text-xs text-gray-400">{{ invoice.due_date }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">{{ formatCurrency(invoice.total) }}</p>
                                    <span
                                        :class="[
                                            'text-xs',
                                            invoice.status === 'paid' ? 'text-emerald-600' : 'text-yellow-600'
                                        ]"
                                    >
                                        {{ invoice.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-center py-4">Aucune facture</p>
                    </div>

                    <!-- Danger Zone -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-red-200">
                        <h2 class="text-lg font-semibold text-red-600 mb-4">Zone de danger</h2>
                        <p class="text-sm text-gray-500 mb-4">
                            La suppression est irréversible et entraînera la perte de toutes les données.
                        </p>
                        <button
                            @click="deleteTenant"
                            class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        >
                            Supprimer définitivement
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plan Modal -->
        <div v-if="showPlanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h2 class="text-lg font-semibold mb-4">Modifier le forfait</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Forfait</label>
                        <select
                            v-model="planForm.plan_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                        >
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                        <select
                            v-model="planForm.billing_period"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="monthly">Mensuel</option>
                            <option value="yearly">Annuel</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="showPlanModal = false"
                        class="px-4 py-2 text-gray-700 hover:text-gray-900"
                    >
                        Annuler
                    </button>
                    <button
                        @click="changePlan"
                        :disabled="planForm.processing"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                    >
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

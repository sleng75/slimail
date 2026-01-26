<script setup>
/**
 * Admin Dashboard
 * Global platform overview
 */

import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    stats: Object,
    recentTenants: Array,
    emailVolume: Array,
    revenueChart: Array,
    planDistribution: Array,
});

const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num?.toString() || '0';
};

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
        none: 'bg-gray-100 text-gray-700',
    };
    return colors[status] || colors.none;
};
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard Admin</h1>
                <p class="text-gray-500">Vue d'ensemble de la plateforme SliMail</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Tenants -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Tenants</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.tenants.total) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-4 text-sm">
                        <span class="text-emerald-600">{{ stats.tenants.active }} actifs</span>
                        <span class="text-blue-600">{{ stats.tenants.trial }} en essai</span>
                    </div>
                </div>

                <!-- Users -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Utilisateurs</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.users.total) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        {{ stats.users.active_today }} actifs aujourd'hui
                    </div>
                </div>

                <!-- Emails -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Emails ce mois</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.emails.sent_this_month) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-4 text-sm">
                        <span class="text-yellow-600">Rebond: {{ stats.emails.bounce_rate }}%</span>
                        <span class="text-red-600">Plainte: {{ stats.emails.complaint_rate }}%</span>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Revenus ce mois</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.revenue.this_month) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        MRR: {{ formatCurrency(stats.revenue.mrr) }}
                    </div>
                </div>
            </div>

            <!-- Charts and Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Tenants -->
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Inscriptions récentes</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="tenant in recentTenants"
                            :key="tenant.id"
                            class="px-6 py-4 flex items-center justify-between hover:bg-gray-50"
                        >
                            <div>
                                <p class="font-medium text-gray-900">{{ tenant.name }}</p>
                                <p class="text-sm text-gray-500">{{ tenant.email }}</p>
                            </div>
                            <div class="text-right">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        getStatusColor(tenant.status)
                                    ]"
                                >
                                    {{ tenant.plan }}
                                </span>
                                <p class="text-xs text-gray-400 mt-1">{{ tenant.created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan Distribution -->
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Distribution des forfaits</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="plan in planDistribution"
                                :key="plan.plan"
                                class="flex items-center justify-between"
                            >
                                <span class="text-gray-700">{{ plan.plan }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-primary-600 rounded-full"
                                            :style="{ width: `${(plan.count / stats.tenants.active) * 100}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-sm text-gray-500 w-12 text-right">{{ plan.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Volume Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Volume d'emails (30 derniers jours)</h2>
                <div class="h-64 flex items-end gap-1">
                    <div
                        v-for="day in emailVolume"
                        :key="day.date"
                        class="flex-1 flex flex-col items-center gap-1"
                    >
                        <div
                            class="w-full bg-primary-500 rounded-t"
                            :style="{ height: `${Math.min((day.count / Math.max(...emailVolume.map(d => d.count))) * 100, 100)}%` }"
                            :title="`${day.date}: ${day.count} emails`"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
/**
 * System Monitoring Dashboard
 */

import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    health: Object,
    emailStats: Array,
    sesReputation: Object,
    queueStats: Object,
    topSenders: Array,
    recentErrors: Array,
});

const getHealthColor = (status) => {
    const colors = {
        healthy: 'bg-emerald-500',
        warning: 'bg-yellow-500',
        error: 'bg-red-500',
    };
    return colors[status] || 'bg-gray-500';
};

const getHealthBgColor = (status) => {
    const colors = {
        healthy: 'bg-emerald-50 border-emerald-200',
        warning: 'bg-yellow-50 border-yellow-200',
        error: 'bg-red-50 border-red-200',
    };
    return colors[status] || 'bg-gray-50 border-gray-200';
};

const formatNumber = (num) => new Intl.NumberFormat('fr-FR').format(num || 0);

const clearCache = () => {
    if (confirm('Vider tous les caches ?')) {
        router.post(route('admin.monitoring.cache.clear'));
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Monitoring Système" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Monitoring</h1>
                    <p class="text-gray-500">État du système et performances</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.monitoring.ses')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                    >
                        SES Détails
                    </Link>
                    <Link
                        :href="route('admin.monitoring.queues')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                    >
                        Files d'attente
                    </Link>
                    <button
                        @click="clearCache"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                    >
                        Vider cache
                    </button>
                </div>
            </div>

            <!-- Health Checks -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div
                    v-for="(check, name) in health"
                    :key="name"
                    :class="[
                        'rounded-xl border p-4',
                        getHealthBgColor(check.status)
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div :class="['w-3 h-3 rounded-full', getHealthColor(check.status)]"></div>
                        <span class="font-medium capitalize">{{ name }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">{{ check.message }}</p>
                    <p v-if="check.latency" class="text-xs text-gray-400 mt-1">
                        {{ check.latency }}ms
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- SES Reputation -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Réputation SES</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Taux de rebond</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-yellow-500 rounded-full"
                                        :style="{ width: `${Math.min(sesReputation.bounce_rate * 10, 100)}%` }"
                                    ></div>
                                </div>
                                <span :class="[
                                    'font-medium',
                                    sesReputation.bounce_rate > 5 ? 'text-red-600' : 'text-gray-900'
                                ]">
                                    {{ sesReputation.bounce_rate }}%
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Taux de plainte</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-red-500 rounded-full"
                                        :style="{ width: `${Math.min(sesReputation.complaint_rate * 100, 100)}%` }"
                                    ></div>
                                </div>
                                <span :class="[
                                    'font-medium',
                                    sesReputation.complaint_rate > 0.1 ? 'text-red-600' : 'text-gray-900'
                                ]">
                                    {{ sesReputation.complaint_rate }}%
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Quota d'envoi</span>
                            <span class="font-medium">{{ formatNumber(sesReputation.sending_quota) }}/24h</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Débit max</span>
                            <span class="font-medium">{{ sesReputation.max_send_rate }} emails/sec</span>
                        </div>
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center gap-2">
                                <div
                                    :class="[
                                        'w-3 h-3 rounded-full',
                                        sesReputation.reputation_status === 'healthy' ? 'bg-emerald-500' :
                                        sesReputation.reputation_status === 'warning' ? 'bg-yellow-500' :
                                        'bg-red-500'
                                    ]"
                                ></div>
                                <span class="font-medium capitalize">{{ sesReputation.reputation_status }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Queue Stats -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Files d'attente</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Jobs en attente</span>
                            <span class="text-2xl font-bold">{{ formatNumber(queueStats.pending) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Jobs échoués</span>
                            <span :class="[
                                'text-2xl font-bold',
                                queueStats.failed > 0 ? 'text-red-600' : 'text-gray-900'
                            ]">
                                {{ formatNumber(queueStats.failed) }}
                            </span>
                        </div>
                        <Link
                            v-if="queueStats.failed > 0"
                            :href="route('admin.monitoring.queues')"
                            class="block text-center w-full mt-4 px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50"
                        >
                            Voir les jobs échoués
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Top Senders -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold">Top envoyeurs (30 jours)</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <div
                        v-for="(sender, index) in topSenders"
                        :key="sender.id"
                        class="px-6 py-4 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-6 text-center text-gray-400 font-medium">{{ index + 1 }}</span>
                            <Link
                                :href="route('admin.tenants.show', sender.id)"
                                class="font-medium text-gray-900 hover:text-primary-600"
                            >
                                {{ sender.name }}
                            </Link>
                        </div>
                        <span class="font-medium">{{ formatNumber(sender.sent_emails_count) }} emails</span>
                    </div>
                </div>
            </div>

            <!-- Email Volume Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Volume d'emails (7 derniers jours)</h2>
                <div class="h-48 flex items-end gap-2">
                    <div
                        v-for="day in emailStats"
                        :key="day.date"
                        class="flex-1 flex flex-col items-center gap-1"
                    >
                        <div class="w-full flex flex-col-reverse gap-0.5">
                            <div
                                class="w-full bg-emerald-500 rounded-t"
                                :style="{ height: `${(day.delivered / Math.max(...emailStats.map(d => d.total))) * 150}px` }"
                                :title="`Delivered: ${day.delivered}`"
                            ></div>
                            <div
                                v-if="day.bounced"
                                class="w-full bg-red-500"
                                :style="{ height: `${(day.bounced / Math.max(...emailStats.map(d => d.total))) * 150}px` }"
                                :title="`Bounced: ${day.bounced}`"
                            ></div>
                        </div>
                        <span class="text-xs text-gray-400">{{ new Date(day.date).toLocaleDateString('fr-FR', { weekday: 'short' }) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-6 mt-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-emerald-500 rounded"></div>
                        <span class="text-sm text-gray-500">Délivrés</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-red-500 rounded"></div>
                        <span class="text-sm text-gray-500">Rebonds</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

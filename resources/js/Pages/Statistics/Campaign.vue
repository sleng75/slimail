<script setup>
/**
 * Campaign Statistics Page
 * Detailed statistics for a specific campaign with interactive charts
 */

import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import { Line, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    campaign: Object,
    stats: Object,
    timeline: Object,
    links: Array,
    devices: Object,
});

// Format number with locale
const formatNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return new Intl.NumberFormat('fr-FR').format(num);
};

// Format full number
const formatFullNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
};

// Format percentage
const formatPercent = (num) => {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(num) + '%';
};

// Status badge config
const statusConfig = {
    draft: { label: 'Brouillon', class: 'bg-gray-100 text-gray-700', dot: 'bg-gray-400' },
    scheduled: { label: 'Programmée', class: 'bg-blue-100 text-blue-700', dot: 'bg-blue-500' },
    sending: { label: 'En cours', class: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500' },
    sent: { label: 'Envoyée', class: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
    paused: { label: 'En pause', class: 'bg-orange-100 text-orange-700', dot: 'bg-orange-500' },
    cancelled: { label: 'Annulée', class: 'bg-red-100 text-red-700', dot: 'bg-red-500' },
};

// Export CSV
const exportCsv = () => {
    window.location.href = route('statistics.campaign.export', props.campaign.id);
};

// Export PDF (opens in new tab for printing)
const exportPdf = () => {
    window.open(route('statistics.campaign.export-pdf', props.campaign.id), '_blank');
};

// Main stats cards with colors
const mainStats = computed(() => [
    {
        key: 'recipients',
        label: 'Destinataires',
        value: props.stats?.total_recipients ?? 0,
        icon: 'users',
        color: 'gray',
        gradient: 'from-gray-500 to-gray-600',
    },
    {
        key: 'delivered',
        label: 'Délivrés',
        value: props.stats?.delivered ?? 0,
        rate: props.stats?.delivery_rate,
        icon: 'check',
        color: 'blue',
        gradient: 'from-blue-500 to-blue-600',
    },
    {
        key: 'opened',
        label: 'Ouverts',
        value: props.stats?.opened ?? 0,
        rate: props.stats?.open_rate,
        icon: 'eye',
        color: 'violet',
        gradient: 'from-violet-500 to-violet-600',
    },
    {
        key: 'clicked',
        label: 'Cliqués',
        value: props.stats?.clicked ?? 0,
        rate: props.stats?.click_rate,
        icon: 'cursor',
        color: 'amber',
        gradient: 'from-amber-500 to-amber-600',
    },
]);

// Secondary stats
const secondaryStats = computed(() => [
    { label: 'Rebonds', value: props.stats?.bounced ?? 0, rate: props.stats?.bounce_rate, danger: true },
    { label: 'Plaintes', value: props.stats?.complained ?? 0 },
    { label: 'Désabonnements', value: props.stats?.unsubscribed ?? 0 },
]);

// Timeline chart configuration
const timelineChartData = computed(() => {
    const labels = props.timeline?.labels || [];
    const opens = props.timeline?.opens || [];
    const clicks = props.timeline?.clicks || [];

    return {
        labels,
        datasets: [
            {
                label: 'Ouvertures',
                data: opens,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#8b5cf6',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            },
            {
                label: 'Clics',
                data: clicks,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#f59e0b',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            },
        ],
    };
});

const timelineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20,
                font: {
                    size: 12,
                    family: "'Inter', sans-serif",
                },
            },
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.95)',
            titleFont: { size: 13, weight: '600' },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 8,
            displayColors: true,
            boxPadding: 4,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                font: { size: 11 },
                color: '#9ca3af',
                maxRotation: 0,
            },
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.04)' },
            ticks: {
                font: { size: 11 },
                color: '#9ca3af',
            },
        },
    },
};

// Device breakdown donut chart
const deviceChartData = computed(() => {
    const desktop = props.devices?.desktop ?? 0;
    const mobile = props.devices?.mobile ?? 0;
    const tablet = props.devices?.tablet ?? 0;

    return {
        labels: ['Desktop', 'Mobile', 'Tablette'],
        datasets: [{
            data: [desktop, mobile, tablet],
            backgroundColor: ['#3b82f6', '#8b5cf6', '#f59e0b'],
            borderWidth: 0,
            hoverOffset: 4,
        }],
    };
});

const deviceChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.95)',
            titleFont: { size: 13, weight: '600' },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (context) => ` ${context.parsed}%`,
            },
        },
    },
};

// Check if we have device data
const hasDeviceData = computed(() => {
    return (props.devices?.desktop ?? 0) + (props.devices?.mobile ?? 0) + (props.devices?.tablet ?? 0) > 0;
});

// Check if we have timeline data
const hasTimelineData = computed(() => {
    return props.timeline?.labels?.length > 0 && props.timeline?.opens?.some(v => v > 0);
});

// Funnel data for visualization
const funnelData = computed(() => {
    const sent = props.stats?.sent ?? 0;
    const delivered = props.stats?.delivered ?? 0;
    const opened = props.stats?.opened ?? 0;
    const clicked = props.stats?.clicked ?? 0;

    return [
        { label: 'Envoyés', value: sent, percent: 100, color: 'bg-blue-500' },
        { label: 'Délivrés', value: delivered, percent: sent > 0 ? (delivered / sent) * 100 : 0, color: 'bg-emerald-500' },
        { label: 'Ouverts', value: opened, percent: sent > 0 ? (opened / sent) * 100 : 0, color: 'bg-violet-500' },
        { label: 'Cliqués', value: clicked, percent: sent > 0 ? (clicked / sent) * 100 : 0, color: 'bg-amber-500' },
    ];
});
</script>

<template>
    <AppLayout>
        <Head :title="`Statistiques - ${campaign.name}`" />

        <PageContainer>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="flex-1">
                        <!-- Breadcrumb -->
                        <nav class="flex items-center gap-2 text-sm text-secondary-500 mb-3">
                            <Link :href="route('statistics.index')" class="hover:text-primary-600 transition-colors">
                                Statistiques
                            </Link>
                            <svg class="w-4 h-4 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="text-secondary-900 font-medium truncate max-w-xs">{{ campaign.name }}</span>
                        </nav>

                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <h1 class="text-2xl font-bold text-secondary-900">{{ campaign.name }}</h1>
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full',
                                    statusConfig[campaign.status]?.class ?? 'bg-gray-100 text-gray-700'
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full', statusConfig[campaign.status]?.dot ?? 'bg-gray-400']"></span>
                                {{ statusConfig[campaign.status]?.label ?? campaign.status }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-sm text-secondary-500">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="truncate max-w-sm">{{ campaign.subject }}</span>
                            </div>
                            <div v-if="campaign.sent_at" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ campaign.sent_at }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Export buttons -->
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                            <button
                                @click="exportCsv"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-sm font-medium text-secondary-700 hover:bg-gray-50 transition-colors border-r border-gray-200"
                                title="Exporter en CSV"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span class="hidden sm:inline">CSV</span>
                            </button>
                            <button
                                @click="exportPdf"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-sm font-medium text-secondary-700 hover:bg-gray-50 transition-colors"
                                title="Exporter en PDF"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <span class="hidden sm:inline">PDF</span>
                            </button>
                        </div>

                        <!-- View campaign button -->
                        <Link
                            :href="route('campaigns.show', campaign.id)"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-medium hover:bg-primary-700 transition-colors shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Voir campagne
                        </Link>
                    </div>
                </div>

                <!-- Main Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="stat in mainStats"
                        :key="stat.key"
                        class="relative bg-white rounded-2xl border border-gray-100 p-5 shadow-sm overflow-hidden group hover:shadow-md transition-shadow"
                    >
                        <div :class="['absolute top-0 left-0 right-0 h-1 bg-gradient-to-r', stat.gradient]"></div>

                        <div class="flex items-start justify-between mb-3">
                            <div :class="[
                                'w-11 h-11 rounded-xl flex items-center justify-center',
                                `bg-${stat.color}-50`
                            ]">
                                <!-- Users icon -->
                                <svg v-if="stat.icon === 'users'" :class="['w-5 h-5', `text-${stat.color}-500`]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <!-- Check icon -->
                                <svg v-else-if="stat.icon === 'check'" :class="['w-5 h-5', `text-${stat.color}-500`]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <!-- Eye icon -->
                                <svg v-else-if="stat.icon === 'eye'" :class="['w-5 h-5', `text-${stat.color}-500`]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <!-- Cursor icon -->
                                <svg v-else-if="stat.icon === 'cursor'" :class="['w-5 h-5', `text-${stat.color}-500`]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                                </svg>
                            </div>

                            <span v-if="stat.rate !== undefined" class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                                {{ formatPercent(stat.rate) }}
                            </span>
                        </div>

                        <p class="text-sm font-medium text-secondary-500 mb-1">{{ stat.label }}</p>
                        <p class="text-3xl font-bold text-secondary-900">{{ formatNumber(stat.value) }}</p>
                    </div>
                </div>

                <!-- Funnel + Secondary Stats -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Email Funnel -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-secondary-900">Entonnoir de conversion</h3>
                            <p class="text-sm text-secondary-500">Parcours des emails envoyés</p>
                        </div>

                        <div class="space-y-4">
                            <div v-for="(step, index) in funnelData" :key="step.label" class="relative">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold', step.color]">
                                            {{ index + 1 }}
                                        </div>
                                        <span class="font-medium text-secondary-700">{{ step.label }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-bold text-secondary-900">{{ formatFullNumber(step.value) }}</span>
                                        <span class="text-sm text-secondary-400 ml-2">({{ formatPercent(step.percent) }})</span>
                                    </div>
                                </div>
                                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        :class="[step.color, 'h-full rounded-full transition-all duration-500']"
                                        :style="{ width: `${step.percent}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Secondary Stats & Devices -->
                    <div class="space-y-6">
                        <!-- Secondary Stats -->
                        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                            <h4 class="text-sm font-semibold text-secondary-700 uppercase tracking-wider mb-4">Autres métriques</h4>
                            <div class="space-y-4">
                                <div v-for="stat in secondaryStats" :key="stat.label" class="flex items-center justify-between">
                                    <span class="text-sm text-secondary-600">{{ stat.label }}</span>
                                    <div class="flex items-center gap-2">
                                        <span :class="['text-lg font-bold', stat.danger ? 'text-red-600' : 'text-secondary-900']">
                                            {{ formatNumber(stat.value) }}
                                        </span>
                                        <span v-if="stat.rate !== undefined" class="text-xs text-secondary-400">
                                            ({{ formatPercent(stat.rate) }})
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Device Breakdown Mini -->
                        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                            <h4 class="text-sm font-semibold text-secondary-700 uppercase tracking-wider mb-4">Appareils</h4>

                            <div v-if="hasDeviceData">
                                <div class="h-32 mb-4">
                                    <Doughnut :data="deviceChartData" :options="deviceChartOptions" />
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                            <span class="text-secondary-600">Desktop</span>
                                        </div>
                                        <span class="font-semibold">{{ devices?.desktop ?? 0 }}%</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full bg-violet-500"></div>
                                            <span class="text-secondary-600">Mobile</span>
                                        </div>
                                        <span class="font-semibold">{{ devices?.mobile ?? 0 }}%</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                                            <span class="text-secondary-600">Tablette</span>
                                        </div>
                                        <span class="font-semibold">{{ devices?.tablet ?? 0 }}%</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-6 text-center text-secondary-400">
                                <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <p class="text-xs">Aucune donnée</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline Chart -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-secondary-900">Activité après envoi</h3>
                            <p class="text-sm text-secondary-500">Ouvertures et clics dans les 24h suivant l'envoi</p>
                        </div>
                    </div>

                    <div v-if="hasTimelineData" class="h-72">
                        <Line :data="timelineChartData" :options="timelineChartOptions" />
                    </div>

                    <div v-else class="h-72 flex items-center justify-center">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-secondary-600">Aucune activité enregistrée</p>
                            <p class="text-xs text-secondary-400 mt-1">Les données apparaîtront après l'envoi de la campagne</p>
                        </div>
                    </div>
                </div>

                <!-- Link Clicks Table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900">Liens cliqués</h3>
                                <p class="text-sm text-secondary-500">Performance des liens dans votre email</p>
                            </div>
                            <span v-if="links && links.length > 0" class="text-sm text-secondary-400">
                                {{ links.length }} lien{{ links.length > 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>

                    <div v-if="links && links.length > 0" class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-secondary-500 uppercase tracking-wider">
                                        URL
                                    </th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-secondary-500 uppercase tracking-wider w-32">
                                        Clics totaux
                                    </th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-secondary-500 uppercase tracking-wider w-32">
                                        Clics uniques
                                    </th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-secondary-500 uppercase tracking-wider w-24">
                                        CTR
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(link, index) in links" :key="link.url" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center text-primary-600 font-semibold text-sm">
                                                {{ index + 1 }}
                                            </div>
                                            <a
                                                :href="link.url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-sm text-primary-600 hover:text-primary-700 hover:underline truncate max-w-lg block"
                                            >
                                                {{ link.url }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm font-semibold text-secondary-900">{{ formatFullNumber(link.clicks) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm font-semibold text-secondary-900">{{ formatFullNumber(link.unique_clicks) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                                            {{ stats?.delivered > 0 ? formatPercent((link.unique_clicks / stats.delivered) * 100) : '0%' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="px-6 py-16 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-secondary-600">Aucun lien cliqué</p>
                        <p class="text-xs text-secondary-400 mt-1">Les clics sur les liens apparaîtront ici</p>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

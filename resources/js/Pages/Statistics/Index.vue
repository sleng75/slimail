<script setup>
/**
 * Statistics Dashboard
 * Global statistics overview with interactive charts and metrics
 */

import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
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
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    overview: Object,
    timeSeries: Object,
    topCampaigns: Array,
    devices: Object,
    emailClients: Array,
    hourlyDistribution: Object,
    alerts: Array,
    period: String,
    periods: Array,
});

const selectedPeriod = ref(props.period);
const isLoading = ref(false);
const activeMetric = ref('sent');

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

// Get change indicator class
const getChangeClass = (value, inverse = false) => {
    if (value === 0) return 'text-secondary-400';
    const isPositive = inverse ? value < 0 : value > 0;
    return isPositive ? 'text-emerald-600' : 'text-red-500';
};

// Get change background class
const getChangeBgClass = (value, inverse = false) => {
    if (value === 0) return 'bg-secondary-50';
    const isPositive = inverse ? value < 0 : value > 0;
    return isPositive ? 'bg-emerald-50' : 'bg-red-50';
};

// Period change handler
const handlePeriodChange = (newPeriod) => {
    isLoading.value = true;
    selectedPeriod.value = newPeriod;
    router.get(route('statistics.index'), { period: newPeriod }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

// Main metrics cards with gradient colors
const metricCards = computed(() => [
    {
        key: 'sent',
        title: 'Emails envoyés',
        value: props.overview?.stats?.sent ?? 0,
        change: props.overview?.changes?.sent ?? 0,
        icon: 'paper-airplane',
        gradient: 'from-blue-500 to-blue-600',
        lightBg: 'bg-blue-50',
        iconColor: 'text-blue-600',
    },
    {
        key: 'delivery_rate',
        title: 'Délivrabilité',
        value: props.overview?.stats?.delivery_rate ?? 0,
        change: props.overview?.changes?.delivery_rate ?? 0,
        isPercent: true,
        icon: 'check-badge',
        gradient: 'from-emerald-500 to-emerald-600',
        lightBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
    },
    {
        key: 'open_rate',
        title: 'Taux d\'ouverture',
        value: props.overview?.stats?.open_rate ?? 0,
        change: props.overview?.changes?.open_rate ?? 0,
        isPercent: true,
        icon: 'envelope-open',
        gradient: 'from-violet-500 to-violet-600',
        lightBg: 'bg-violet-50',
        iconColor: 'text-violet-600',
    },
    {
        key: 'click_rate',
        title: 'Taux de clic',
        value: props.overview?.stats?.click_rate ?? 0,
        change: props.overview?.changes?.click_rate ?? 0,
        isPercent: true,
        icon: 'cursor-arrow-rays',
        gradient: 'from-amber-500 to-amber-600',
        lightBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
    },
    {
        key: 'bounce_rate',
        title: 'Taux de rebond',
        value: props.overview?.stats?.bounce_rate ?? 0,
        change: props.overview?.changes?.bounce_rate ?? 0,
        isPercent: true,
        icon: 'exclamation-triangle',
        gradient: 'from-red-500 to-red-600',
        lightBg: 'bg-red-50',
        iconColor: 'text-red-600',
        inverse: true,
    },
]);

// Line chart configuration
const lineChartData = computed(() => {
    const sent = props.timeSeries?.sent || { labels: [], data: [] };
    const opened = props.timeSeries?.opened || { labels: [], data: [] };
    const clicked = props.timeSeries?.clicked || { labels: [], data: [] };

    return {
        labels: sent.labels || [],
        datasets: [
            {
                label: 'Envoyés',
                data: sent.data || [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#3b82f6',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            },
            {
                label: 'Ouverts',
                data: opened.data || [],
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
                label: 'Cliqués',
                data: clicked.data || [],
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

const lineChartOptions = {
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
            grid: {
                display: false,
            },
            ticks: {
                font: { size: 11 },
                color: '#9ca3af',
                maxRotation: 0,
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.04)',
            },
            ticks: {
                font: { size: 11 },
                color: '#9ca3af',
                callback: (value) => formatNumber(value),
            },
        },
    },
};

// Device breakdown donut chart
const deviceChartData = computed(() => {
    const desktop = props.devices?.desktop?.percentage ?? 0;
    const mobile = props.devices?.mobile?.percentage ?? 0;
    const tablet = props.devices?.tablet?.percentage ?? 0;

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
        legend: {
            display: false,
        },
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

// Hourly distribution bar chart
const hourlyChartData = computed(() => {
    const hourly = props.hourlyDistribution || { labels: [], data: [] };

    return {
        labels: hourly.labels || [],
        datasets: [{
            label: 'Ouvertures',
            data: hourly.data || [],
            backgroundColor: 'rgba(139, 92, 246, 0.8)',
            borderRadius: 4,
            barThickness: 12,
        }],
    };
});

const hourlyChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.95)',
            titleFont: { size: 13, weight: '600' },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                font: { size: 10 },
                color: '#9ca3af',
                maxRotation: 0,
                callback: function(value, index) {
                    return index % 3 === 0 ? this.getLabelForValue(value) : '';
                },
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

// Export functions
const exportCsv = () => {
    window.location.href = route('statistics.export', { period: selectedPeriod.value });
};

const exportPdf = () => {
    window.open(route('statistics.export-pdf', { period: selectedPeriod.value }), '_blank');
};

// Check if we have any data
const hasData = computed(() => {
    return (props.overview?.stats?.sent ?? 0) > 0;
});

// Total devices for percentage calculation
const totalDevices = computed(() => {
    return (props.devices?.desktop?.count ?? 0) +
           (props.devices?.mobile?.count ?? 0) +
           (props.devices?.tablet?.count ?? 0);
});
</script>

<template>
    <AppLayout>
        <Head title="Statistiques" />

        <PageContainer>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-secondary-900">Statistiques</h1>
                        <p class="mt-1 text-secondary-500">Vue d'ensemble de vos performances email</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Period selector -->
                        <div class="relative">
                            <select
                                v-model="selectedPeriod"
                                @change="handlePeriodChange(selectedPeriod)"
                                :disabled="isLoading"
                                class="appearance-none pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-secondary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 cursor-pointer disabled:opacity-50 disabled:cursor-wait"
                            >
                                <option v-for="p in periods" :key="p.value" :value="p.value">
                                    {{ p.label }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

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
                    </div>
                </div>

                <!-- Loading overlay -->
                <div v-if="isLoading" class="fixed inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center">
                    <div class="bg-white rounded-2xl shadow-xl p-6 flex items-center gap-4">
                        <svg class="animate-spin h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-secondary-700 font-medium">Chargement des statistiques...</span>
                    </div>
                </div>

                <!-- Alerts -->
                <div v-if="alerts && alerts.length > 0" class="space-y-3">
                    <div
                        v-for="(alert, index) in alerts"
                        :key="index"
                        :class="[
                            'p-4 rounded-2xl border-l-4 shadow-sm',
                            alert.type === 'danger'
                                ? 'bg-red-50 border-red-500'
                                : 'bg-amber-50 border-amber-500'
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <div :class="[
                                'flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
                                alert.type === 'danger' ? 'bg-red-100' : 'bg-amber-100'
                            ]">
                                <svg :class="['w-5 h-5', alert.type === 'danger' ? 'text-red-600' : 'text-amber-600']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 :class="['font-semibold', alert.type === 'danger' ? 'text-red-800' : 'text-amber-800']">
                                    {{ alert.title }}
                                </h4>
                                <p :class="['text-sm mt-1', alert.type === 'danger' ? 'text-red-700' : 'text-amber-700']">
                                    {{ alert.message }}
                                </p>
                                <button
                                    v-if="alert.action"
                                    :class="[
                                        'mt-3 text-sm font-medium inline-flex items-center gap-1 hover:underline',
                                        alert.type === 'danger' ? 'text-red-700' : 'text-amber-700'
                                    ]"
                                >
                                    {{ alert.action }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metric Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div
                        v-for="metric in metricCards"
                        :key="metric.key"
                        class="group relative bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-lg hover:border-gray-200 transition-all duration-300 overflow-hidden"
                    >
                        <!-- Gradient accent -->
                        <div :class="['absolute top-0 left-0 right-0 h-1 bg-gradient-to-r', metric.gradient]"></div>

                        <div class="flex items-start justify-between mb-4">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', metric.lightBg]">
                                <!-- Paper Airplane -->
                                <svg v-if="metric.icon === 'paper-airplane'" :class="['w-6 h-6', metric.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                <!-- Check Badge -->
                                <svg v-else-if="metric.icon === 'check-badge'" :class="['w-6 h-6', metric.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                                <!-- Envelope Open -->
                                <svg v-else-if="metric.icon === 'envelope-open'" :class="['w-6 h-6', metric.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                                </svg>
                                <!-- Cursor Arrow Rays -->
                                <svg v-else-if="metric.icon === 'cursor-arrow-rays'" :class="['w-6 h-6', metric.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                </svg>
                                <!-- Exclamation Triangle -->
                                <svg v-else-if="metric.icon === 'exclamation-triangle'" :class="['w-6 h-6', metric.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>

                            <!-- Change indicator -->
                            <div
                                v-if="metric.change !== 0"
                                :class="[
                                    'flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold',
                                    getChangeBgClass(metric.change, metric.inverse),
                                    getChangeClass(metric.change, metric.inverse)
                                ]"
                            >
                                <svg v-if="metric.change > 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                {{ Math.abs(metric.change) }}{{ metric.isPercent ? ' pts' : '%' }}
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-secondary-500 mb-1">{{ metric.title }}</p>
                            <p class="text-3xl font-bold text-secondary-900">
                                {{ metric.isPercent ? formatPercent(metric.value) : formatNumber(metric.value) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Chart - Time Series -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900">Évolution des envois</h3>
                                <p class="text-sm text-secondary-500">Performance sur la période sélectionnée</p>
                            </div>
                        </div>

                        <div v-if="hasData" class="h-72">
                            <Line :data="lineChartData" :options="lineChartOptions" />
                        </div>

                        <div v-else class="h-72 flex items-center justify-center">
                            <div class="text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-secondary-600">Aucune donnée à afficher</p>
                                <p class="text-xs text-secondary-400 mt-1">Envoyez des campagnes pour voir les statistiques</p>
                            </div>
                        </div>
                    </div>

                    <!-- Device Breakdown -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-secondary-900">Appareils</h3>
                            <p class="text-sm text-secondary-500">Répartition des ouvertures</p>
                        </div>

                        <div v-if="totalDevices > 0">
                            <div class="h-48 mb-6">
                                <Doughnut :data="deviceChartData" :options="deviceChartOptions" />
                            </div>

                            <div class="space-y-3">
                                <!-- Desktop -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm text-secondary-600">Desktop</span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-secondary-900">{{ devices?.desktop?.percentage ?? 0 }}%</span>
                                </div>
                                <!-- Mobile -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full bg-violet-500"></div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm text-secondary-600">Mobile</span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-secondary-900">{{ devices?.mobile?.percentage ?? 0 }}%</span>
                                </div>
                                <!-- Tablet -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm text-secondary-600">Tablette</span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-secondary-900">{{ devices?.tablet?.percentage ?? 0 }}%</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="h-64 flex items-center justify-center">
                            <div class="text-center">
                                <div class="w-14 h-14 mx-auto mb-3 bg-gray-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-7 h-7 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-sm text-secondary-500">Aucune donnée</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Campaigns -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-secondary-900">Meilleures campagnes</h3>
                                    <p class="text-sm text-secondary-500">Par taux d'ouverture</p>
                                </div>
                                <Link
                                    :href="route('campaigns.index')"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-700"
                                >
                                    Voir tout
                                </Link>
                            </div>
                        </div>

                        <div v-if="topCampaigns && topCampaigns.length > 0" class="divide-y divide-gray-100">
                            <div
                                v-for="(campaign, index) in topCampaigns"
                                :key="campaign.id"
                                @click="router.get(route('statistics.campaign', campaign.id))"
                                class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 transition-colors cursor-pointer group"
                            >
                                <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-secondary-900 truncate group-hover:text-primary-600 transition-colors">
                                        {{ campaign.name }}
                                    </p>
                                    <p class="text-xs text-secondary-500">{{ formatFullNumber(campaign.sent_count) }} envois</p>
                                </div>
                                <div class="flex items-center gap-6">
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-emerald-600">{{ formatPercent(campaign.open_rate) }}</p>
                                        <p class="text-xs text-secondary-400">Ouverture</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-amber-600">{{ formatPercent(campaign.click_rate) }}</p>
                                        <p class="text-xs text-secondary-400">Clic</p>
                                    </div>
                                    <svg class="w-5 h-5 text-secondary-300 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div v-else class="px-6 py-12 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-secondary-600">Aucune campagne envoyée</p>
                            <p class="text-xs text-secondary-400 mt-1">Créez votre première campagne</p>
                            <Link
                                :href="route('campaigns.create')"
                                class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nouvelle campagne
                            </Link>
                        </div>
                    </div>

                    <!-- Hourly Distribution -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-secondary-900">Heures d'ouverture</h3>
                            <p class="text-sm text-secondary-500">Distribution sur 24h</p>
                        </div>

                        <div v-if="hasData" class="h-64">
                            <Bar :data="hourlyChartData" :options="hourlyChartOptions" />
                        </div>

                        <div v-else class="h-64 flex items-center justify-center">
                            <div class="text-center">
                                <div class="w-14 h-14 mx-auto mb-3 bg-gray-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-7 h-7 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-sm text-secondary-500">Aucune donnée</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Clients & Stats Summary -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Email Clients -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <div class="mb-5">
                            <h3 class="text-lg font-semibold text-secondary-900">Clients email</h3>
                            <p class="text-sm text-secondary-500">Top clients par ouvertures</p>
                        </div>

                        <div v-if="emailClients && emailClients.length > 0" class="space-y-4">
                            <div
                                v-for="(client, index) in emailClients.slice(0, 6)"
                                :key="client.name"
                                class="flex items-center gap-3"
                            >
                                <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <span class="text-xs font-semibold text-secondary-500">{{ index + 1 }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-secondary-700 truncate">{{ client.name }}</p>
                                </div>
                                <span class="text-sm font-semibold text-secondary-900">{{ formatNumber(client.count) }}</span>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center">
                            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-secondary-500">Aucune donnée</p>
                        </div>
                    </div>

                    <!-- Stats Summary -->
                    <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-3xl font-bold">{{ formatNumber(overview?.stats?.delivered ?? 0) }}</p>
                            <p class="text-sm text-white/80 mt-1">Délivrés</p>
                        </div>

                        <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-2xl p-5 text-white">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <p class="text-3xl font-bold">{{ formatNumber(overview?.stats?.opened ?? 0) }}</p>
                            <p class="text-sm text-white/80 mt-1">Ouverts</p>
                        </div>

                        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                                </svg>
                            </div>
                            <p class="text-3xl font-bold">{{ formatNumber(overview?.stats?.clicked ?? 0) }}</p>
                            <p class="text-sm text-white/80 mt-1">Cliqués</p>
                        </div>

                        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl p-5 text-white">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <p class="text-3xl font-bold">{{ formatNumber(overview?.stats?.bounced ?? 0) }}</p>
                            <p class="text-sm text-white/80 mt-1">Rebonds</p>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

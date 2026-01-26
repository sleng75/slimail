<script setup>
/**
 * Templates Index Page
 * List and manage email templates with grid/list views
 */

import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    templates: Object,
    categories: Array,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const selectedActive = ref(props.filters.active ?? '');
const showDeleteModal = ref(false);
const templateToDelete = ref(null);
const isLoading = ref(false);
const isDeleting = ref(false);
const isDuplicating = ref(null);
const viewMode = ref('grid'); // 'grid' or 'list'
const hoveredTemplate = ref(null);

const categoryLabels = {
    newsletter: 'Newsletter',
    promotional: 'Promotionnel',
    transactional: 'Transactionnel',
    notification: 'Notification',
    welcome: 'Bienvenue',
    abandoned_cart: 'Panier abandonné',
    event: 'Événement',
    survey: 'Enquête',
    other: 'Autre',
};

const categoryIcons = {
    newsletter: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2z"/><path d="M3 6l9 7 9-7"/></svg>`,
    promotional: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/><path d="M9 12l2 2 4-4"/></svg>`,
    transactional: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/><path d="M9 12h6M9 16h6"/></svg>`,
    notification: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>`,
    welcome: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg>`,
    abandoned_cart: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
    event: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
    survey: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>`,
    other: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>`,
};

const categoryGradients = {
    newsletter: 'from-blue-500 to-indigo-600',
    promotional: 'from-emerald-500 to-teal-600',
    transactional: 'from-violet-500 to-purple-600',
    notification: 'from-amber-500 to-orange-600',
    welcome: 'from-pink-500 to-rose-600',
    abandoned_cart: 'from-orange-500 to-red-600',
    event: 'from-indigo-500 to-blue-600',
    survey: 'from-teal-500 to-cyan-600',
    other: 'from-slate-500 to-gray-600',
};

const applyFilters = debounce(() => {
    isLoading.value = true;
    router.get('/templates', {
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
        active: selectedActive.value !== '' ? selectedActive.value : undefined,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}, 300);

watch([search, selectedCategory, selectedActive], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    selectedActive.value = '';
};

const confirmDelete = (template) => {
    templateToDelete.value = template;
    showDeleteModal.value = true;
};

const deleteTemplate = () => {
    if (templateToDelete.value) {
        isDeleting.value = true;
        router.delete(`/templates/${templateToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                templateToDelete.value = null;
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

const duplicateTemplate = (template) => {
    isDuplicating.value = template.id;
    router.post(`/templates/${template.id}/duplicate`, {}, {
        onFinish: () => {
            isDuplicating.value = null;
        },
    });
};

const getCategoryColor = (category) => {
    const colors = {
        newsletter: 'bg-blue-100 text-blue-700 border-blue-200',
        promotional: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        transactional: 'bg-violet-100 text-violet-700 border-violet-200',
        notification: 'bg-amber-100 text-amber-700 border-amber-200',
        welcome: 'bg-pink-100 text-pink-700 border-pink-200',
        abandoned_cart: 'bg-orange-100 text-orange-700 border-orange-200',
        event: 'bg-indigo-100 text-indigo-700 border-indigo-200',
        survey: 'bg-teal-100 text-teal-700 border-teal-200',
        other: 'bg-slate-100 text-slate-700 border-slate-200',
    };
    return colors[category] || colors.other;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Generate preview illustrations based on category
const getTemplateIllustration = (template) => {
    const category = template.category || 'other';
    return {
        icon: categoryIcons[category] || categoryIcons.other,
        gradient: categoryGradients[category] || categoryGradients.other,
    };
};

// Stats animation
const animatedStats = ref({
    total: 0,
    active: 0,
    system: 0,
});

onMounted(() => {
    // Animate stats on mount
    const duration = 1000;
    const steps = 30;
    const interval = duration / steps;

    let currentStep = 0;
    const timer = setInterval(() => {
        currentStep++;
        const progress = currentStep / steps;
        const easeOut = 1 - Math.pow(1 - progress, 3);

        animatedStats.value = {
            total: Math.round(props.stats.total * easeOut),
            active: Math.round(props.stats.active * easeOut),
            system: Math.round(props.stats.system * easeOut),
        };

        if (currentStep >= steps) {
            clearInterval(timer);
            animatedStats.value = { ...props.stats };
        }
    }, interval);
});
</script>

<template>
    <AppLayout>
        <Head title="Templates" />

        <PageContainer size="wide" :noPadding="true">
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">
                <div class="py-page px-page">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">
                                Templates d'emails
                            </h1>
                            <p class="mt-2 text-slate-600">
                                Créez et gérez vos modèles d'emails avec l'éditeur drag & drop.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link
                                href="/templates/library"
                                class="group inline-flex items-center px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 shadow-sm"
                            >
                                <svg class="w-5 h-5 mr-2 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Bibliothèque
                            </Link>
                            <Link
                                href="/templates/create"
                                class="group inline-flex items-center px-5 py-2.5 border border-transparent rounded-xl shadow-lg shadow-indigo-500/25 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 transition-all duration-200"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nouveau template
                            </Link>
                        </div>
                    </div>

                    <!-- Stats Cards with Animation -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <!-- Total Templates -->
                        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-lg hover:border-slate-300 transition-all duration-300 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative flex items-center">
                                <div class="flex-shrink-0 w-14 h-14 flex items-center justify-center bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl shadow-lg shadow-indigo-500/30">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                    </svg>
                                </div>
                                <div class="ml-5">
                                    <p class="text-sm font-medium text-slate-500">Total</p>
                                    <p class="text-3xl font-bold text-slate-900 tabular-nums">{{ animatedStats.total }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Active Templates -->
                        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-lg hover:border-slate-300 transition-all duration-300 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative flex items-center">
                                <div class="flex-shrink-0 w-14 h-14 flex items-center justify-center bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg shadow-emerald-500/30">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5">
                                    <p class="text-sm font-medium text-slate-500">Actifs</p>
                                    <p class="text-3xl font-bold text-slate-900 tabular-nums">{{ animatedStats.active }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- System Templates -->
                        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-lg hover:border-slate-300 transition-all duration-300 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative flex items-center">
                                <div class="flex-shrink-0 w-14 h-14 flex items-center justify-center bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl shadow-lg shadow-violet-500/30">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div class="ml-5">
                                    <p class="text-sm font-medium text-slate-500">Système</p>
                                    <p class="text-3xl font-bold text-slate-900 tabular-nums">{{ animatedStats.system }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <!-- Search -->
                            <div class="md:col-span-2 relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Rechercher un template..."
                                    class="block w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                />
                            </div>

                            <!-- Category -->
                            <select
                                v-model="selectedCategory"
                                class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            >
                                <option value="">Toutes les catégories</option>
                                <option v-for="category in categories" :key="category" :value="category">
                                    {{ categoryLabels[category] || category }}
                                </option>
                            </select>

                            <!-- Status -->
                            <select
                                v-model="selectedActive"
                                class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            >
                                <option value="">Tous les statuts</option>
                                <option :value="true">Actifs</option>
                                <option :value="false">Inactifs</option>
                            </select>

                            <!-- View Toggle & Clear -->
                            <div class="flex items-center gap-2">
                                <div class="flex items-center bg-slate-100 rounded-lg p-1">
                                    <button
                                        @click="viewMode = 'grid'"
                                        :class="[
                                            'p-2 rounded-md transition-all',
                                            viewMode === 'grid' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'
                                        ]"
                                        title="Vue grille"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="viewMode = 'list'"
                                        :class="[
                                            'p-2 rounded-md transition-all',
                                            viewMode === 'list' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'
                                        ]"
                                        title="Vue liste"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                        </svg>
                                    </button>
                                </div>
                                <button
                                    @click="clearFilters"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 bg-white hover:bg-slate-50 hover:border-slate-300 transition-all"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="isLoading" class="flex items-center justify-center py-16">
                        <LoadingSpinner size="lg" text="Chargement des templates..." />
                    </div>

                    <!-- Templates Grid -->
                    <div v-else-if="templates.data.length > 0">
                        <!-- Grid View -->
                        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="template in templates.data"
                                :key="template.id"
                                @mouseenter="hoveredTemplate = template.id"
                                @mouseleave="hoveredTemplate = null"
                                class="group bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden hover:shadow-xl hover:border-slate-300 transition-all duration-300"
                            >
                                <!-- Thumbnail / Illustration -->
                                <div class="relative aspect-[4/3] overflow-hidden">
                                    <!-- Dynamic Gradient Background with Icon -->
                                    <div :class="['absolute inset-0 bg-gradient-to-br', getTemplateIllustration(template).gradient]">
                                        <!-- Decorative Pattern -->
                                        <div class="absolute inset-0 opacity-20">
                                            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                                                <defs>
                                                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                                                    </pattern>
                                                </defs>
                                                <rect width="100" height="100" fill="url(#grid)" />
                                            </svg>
                                        </div>

                                        <!-- Email Preview Mockup -->
                                        <div class="absolute inset-4 bg-white/95 rounded-xl shadow-2xl transform group-hover:scale-[0.98] transition-transform duration-300">
                                            <!-- Email Header Mockup -->
                                            <div class="p-3 border-b border-slate-100">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br" :class="getTemplateIllustration(template).gradient"></div>
                                                    <div class="flex-1">
                                                        <div class="h-2.5 bg-slate-200 rounded-full w-24"></div>
                                                        <div class="h-2 bg-slate-100 rounded-full w-16 mt-1.5"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Email Body Mockup -->
                                            <div class="p-3 space-y-2">
                                                <div class="h-2.5 bg-slate-200 rounded-full w-full"></div>
                                                <div class="h-2.5 bg-slate-100 rounded-full w-5/6"></div>
                                                <div class="h-2.5 bg-slate-100 rounded-full w-4/6"></div>
                                                <div class="mt-3 h-8 rounded-lg bg-gradient-to-r opacity-80" :class="getTemplateIllustration(template).gradient"></div>
                                                <div class="h-2 bg-slate-100 rounded-full w-3/4 mt-3"></div>
                                                <div class="h-2 bg-slate-100 rounded-full w-5/6"></div>
                                            </div>
                                        </div>

                                        <!-- Category Icon Badge -->
                                        <div class="absolute top-2 left-2 w-10 h-10 bg-white/90 backdrop-blur rounded-lg shadow-lg flex items-center justify-center">
                                            <div class="w-5 h-5 text-slate-700" v-html="getTemplateIllustration(template).icon"></div>
                                        </div>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="absolute top-3 right-3 z-10">
                                        <span
                                            v-if="template.is_system"
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-violet-100/90 text-violet-700 backdrop-blur shadow-sm border border-violet-200/50"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Système
                                        </span>
                                        <span
                                            v-else-if="template.is_active"
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-100/90 text-emerald-700 backdrop-blur shadow-sm border border-emerald-200/50"
                                        >
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                                            Actif
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100/90 text-slate-600 backdrop-blur shadow-sm border border-slate-200/50"
                                        >
                                            Inactif
                                        </span>
                                    </div>

                                    <!-- Hover Actions Overlay -->
                                    <div
                                        :class="[
                                            'absolute inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center gap-3 transition-all duration-300',
                                            hoveredTemplate === template.id ? 'opacity-100' : 'opacity-0 pointer-events-none'
                                        ]"
                                    >
                                        <Link
                                            :href="`/templates/${template.id}/edit`"
                                            class="w-12 h-12 flex items-center justify-center bg-white rounded-xl shadow-lg hover:scale-110 transition-transform text-slate-700 hover:text-indigo-600"
                                            title="Modifier"
                                        >
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="duplicateTemplate(template)"
                                            :disabled="isDuplicating === template.id"
                                            class="w-12 h-12 flex items-center justify-center bg-white rounded-xl shadow-lg hover:scale-110 transition-transform text-slate-700 hover:text-emerald-600 disabled:opacity-50"
                                            title="Dupliquer"
                                        >
                                            <LoadingSpinner v-if="isDuplicating === template.id" size="sm" />
                                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="!template.is_system"
                                            @click="confirmDelete(template)"
                                            class="w-12 h-12 flex items-center justify-center bg-white rounded-xl shadow-lg hover:scale-110 transition-transform text-slate-700 hover:text-red-600"
                                            title="Supprimer"
                                        >
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="p-5">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-semibold text-slate-900 truncate">
                                                {{ template.name }}
                                            </h3>
                                            <p v-if="template.description" class="mt-1 text-sm text-slate-500 line-clamp-2">
                                                {{ template.description }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                                        <span
                                            v-if="template.category"
                                            :class="getCategoryColor(template.category)"
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium border"
                                        >
                                            {{ categoryLabels[template.category] || template.category }}
                                        </span>
                                        <span v-else class="text-xs text-slate-400">Sans catégorie</span>

                                        <div class="flex items-center gap-3 text-xs text-slate-500">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                </svg>
                                                {{ template.usage_count || 0 }}
                                            </span>
                                            <span v-if="template.updated_at" class="hidden sm:inline">
                                                {{ formatDate(template.updated_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- List View -->
                        <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                            <div class="divide-y divide-slate-100">
                                <div
                                    v-for="template in templates.data"
                                    :key="template.id"
                                    class="group flex items-center gap-4 p-4 hover:bg-slate-50 transition-colors"
                                >
                                    <!-- Icon -->
                                    <div :class="['flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center', getTemplateIllustration(template).gradient]">
                                        <div class="w-6 h-6 text-white" v-html="getTemplateIllustration(template).icon"></div>
                                    </div>

                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-900 truncate">
                                                {{ template.name }}
                                            </h3>
                                            <span
                                                v-if="template.is_system"
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-700"
                                            >
                                                Système
                                            </span>
                                            <span
                                                v-else-if="template.is_active"
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700"
                                            >
                                                Actif
                                            </span>
                                        </div>
                                        <p v-if="template.description" class="mt-0.5 text-sm text-slate-500 truncate">
                                            {{ template.description }}
                                        </p>
                                    </div>

                                    <!-- Category -->
                                    <span
                                        v-if="template.category"
                                        :class="getCategoryColor(template.category)"
                                        class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium border"
                                    >
                                        {{ categoryLabels[template.category] || template.category }}
                                    </span>

                                    <!-- Usage -->
                                    <span class="hidden md:inline-flex items-center gap-1 text-sm text-slate-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        {{ template.usage_count || 0 }}
                                    </span>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-1">
                                        <Link
                                            :href="`/templates/${template.id}/edit`"
                                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Modifier"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="duplicateTemplate(template)"
                                            :disabled="isDuplicating === template.id"
                                            class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors disabled:opacity-50"
                                            title="Dupliquer"
                                        >
                                            <LoadingSpinner v-if="isDuplicating === template.id" size="sm" />
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="!template.is_system"
                                            @click="confirmDelete(template)"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Supprimer"
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

                    <!-- Empty State -->
                    <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-16 text-center">
                        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-indigo-100 to-violet-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900">Aucun template trouvé</h3>
                        <p class="mt-2 text-slate-500 max-w-md mx-auto">
                            Créez votre premier template ou explorez notre bibliothèque de modèles prêts à l'emploi.
                        </p>
                        <div class="mt-8 flex items-center justify-center gap-4">
                            <Link
                                href="/templates/library"
                                class="inline-flex items-center px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-300 transition-all"
                            >
                                <svg class="w-5 h-5 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Voir la bibliothèque
                            </Link>
                            <Link
                                href="/templates/create"
                                class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-xl shadow-lg shadow-indigo-500/25 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 transition-all"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Créer un template
                            </Link>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="!isLoading && templates.data.length > 0" class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-slate-600">
                            Affichage de
                            <span class="font-semibold text-slate-900">{{ templates.from }}</span>
                            à
                            <span class="font-semibold text-slate-900">{{ templates.to }}</span>
                            sur
                            <span class="font-semibold text-slate-900">{{ templates.total }}</span>
                            templates
                        </p>

                        <nav class="flex items-center gap-1">
                            <template v-for="(link, index) in templates.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all',
                                        link.active
                                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/25'
                                            : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg',
                                        'bg-slate-50 border border-slate-200 text-slate-400 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </PageContainer>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                        <Transition
                            enter-active-class="transition-all duration-300 delay-75"
                            enter-from-class="opacity-0 scale-95 translate-y-4"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-200"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showDeleteModal" class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
                                <div class="flex items-center justify-center w-16 h-16 mx-auto bg-gradient-to-br from-red-100 to-rose-100 rounded-2xl">
                                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-xl font-bold text-slate-900 text-center">Supprimer le template</h3>
                                <p class="mt-3 text-slate-500 text-center">
                                    Êtes-vous sûr de vouloir supprimer
                                    <span class="font-semibold text-slate-700">"{{ templateToDelete?.name }}"</span> ?
                                    Cette action est irréversible.
                                </p>
                                <div class="mt-8 flex gap-3">
                                    <button
                                        @click="showDeleteModal = false"
                                        :disabled="isDeleting"
                                        class="flex-1 px-5 py-3 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-300 transition-all disabled:opacity-50"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        @click="deleteTemplate"
                                        :disabled="isDeleting"
                                        class="flex-1 inline-flex items-center justify-center px-5 py-3 border border-transparent rounded-xl text-sm font-medium text-white bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-lg shadow-red-500/25 transition-all disabled:opacity-50"
                                    >
                                        <LoadingSpinner v-if="isDeleting" size="sm" class="mr-2" />
                                        {{ isDeleting ? 'Suppression...' : 'Supprimer' }}
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

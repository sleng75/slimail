<script setup>
/**
 * Dashboard Page
 * Main dashboard with statistics and quick actions
 */

import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        default: () => ({
            emailsSent: 0,
            emailsDelivered: 0,
            emailsOpened: 0,
            emailsClicked: 0,
            contacts: 0,
            campaigns: 0,
            openRate: 0,
            clickRate: 0,
            deliveryRate: 0,
            bounceRate: 0,
            changes: {
                sent: 0,
                delivered: 0,
                opened: 0,
                clicked: 0,
            },
        }),
    },
    recentCampaigns: {
        type: Array,
        default: () => [],
    },
    emailActivity: {
        type: Object,
        default: () => ({}),
    },
    period: {
        type: String,
        default: '30d',
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <PageContainer size="wide">
            <div class="space-y-section">
                <!-- Page Header -->
                <div class="space-y-2">
                    <h1 class="text-2xl lg:text-3xl font-bold text-secondary-900">
                        Dashboard
                    </h1>
                    <p class="text-secondary-500">
                        Bienvenue sur SliMail. Voici un aperçu de vos activités.
                    </p>
                </div>

                <!-- Stats cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Emails sent -->
                    <div class="card card-hover group">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center group-hover:bg-primary-200 transition-colors">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-secondary-500">Emails envoyés</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ stats.emailsSent.toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contacts -->
                    <div class="card card-hover group">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-secondary-500">Contacts</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ stats.contacts.toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Campaigns -->
                    <div class="card card-hover group">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-secondary-500">Campagnes</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ stats.campaigns }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Open rate -->
                    <div class="card card-hover group">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-secondary-500">Taux d'ouverture</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ stats.openRate }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="card">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-secondary-900">Actions rapides</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Link href="/campaigns/create" class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-primary-50 hover:border-primary-200 border border-transparent transition-all group">
                                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center group-hover:bg-primary-200 transition-colors">
                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-secondary-900 group-hover:text-primary-700">Nouvelle campagne</span>
                            </Link>

                            <Link href="/contacts/import" class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-emerald-50 hover:border-emerald-200 border border-transparent transition-all group">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-secondary-900 group-hover:text-emerald-700">Importer des contacts</span>
                            </Link>

                            <Link href="/templates" class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-violet-50 hover:border-violet-200 border border-transparent transition-all group">
                                <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-secondary-900 group-hover:text-violet-700">Créer un template</span>
                            </Link>

                            <Link href="/api-settings" class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-amber-50 hover:border-amber-200 border border-transparent transition-all group">
                                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-secondary-900 group-hover:text-amber-700">Configurer l'API</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Recent campaigns -->
                <div class="card">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-secondary-900">Campagnes récentes</h2>
                        <Link href="/campaigns" class="text-sm text-primary-600 hover:text-primary-700">
                            Voir tout
                        </Link>
                    </div>
                    <div class="p-6">
                        <div v-if="recentCampaigns.length > 0" class="space-y-4">
                            <div
                                v-for="campaign in recentCampaigns"
                                :key="campaign.id"
                                class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors"
                            >
                                <div class="flex-1 min-w-0">
                                    <Link :href="`/campaigns/${campaign.id}`" class="text-sm font-medium text-secondary-900 hover:text-primary-600 truncate block">
                                        {{ campaign.name }}
                                    </Link>
                                    <p class="text-xs text-secondary-500 mt-1">
                                        Envoyée le {{ campaign.completed_at }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-6 ml-4">
                                    <div class="text-center">
                                        <p class="text-lg font-semibold text-secondary-900">{{ campaign.sent_count.toLocaleString() }}</p>
                                        <p class="text-xs text-secondary-500">Envoyés</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-lg font-semibold text-emerald-600">{{ campaign.open_rate }}%</p>
                                        <p class="text-xs text-secondary-500">Ouvertures</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-lg font-semibold text-primary-600">{{ campaign.click_rate }}%</p>
                                        <p class="text-xs text-secondary-500">Clics</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto">
                                <svg class="h-8 w-8 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-sm font-medium text-secondary-900">Aucune campagne récente</h3>
                            <p class="mt-1 text-sm text-secondary-500">
                                Commencez par créer votre première campagne email.
                            </p>
                            <div class="mt-6">
                                <Link
                                    href="/campaigns/create"
                                    class="btn btn-primary"
                                >
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Nouvelle campagne
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

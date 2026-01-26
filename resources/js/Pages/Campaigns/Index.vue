<script setup>
/**
 * Campaigns Index Page
 * List and manage email campaigns
 */

import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    campaigns: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedType = ref(props.filters.type || '');
const showCreateModal = ref(false);
const newCampaignName = ref('');
const newCampaignType = ref('regular');

const applyFilters = debounce(() => {
    router.get('/campaigns', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        type: selectedType.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedType], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedType.value = '';
};

const createCampaign = () => {
    if (!newCampaignName.value.trim()) return;

    router.post('/campaigns', {
        name: newCampaignName.value,
        type: newCampaignType.value,
    }, {
        onSuccess: () => {
            showCreateModal.value = false;
            newCampaignName.value = '';
            newCampaignType.value = 'regular';
        },
    });
};

const duplicateCampaign = (campaign) => {
    router.post(`/campaigns/${campaign.id}/duplicate`);
};

const deleteCampaign = (campaign) => {
    if (confirm(`Voulez-vous vraiment supprimer la campagne "${campaign.name}" ?`)) {
        router.delete(`/campaigns/${campaign.id}`);
    }
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800',
        scheduled: 'bg-blue-100 text-blue-800',
        sending: 'bg-yellow-100 text-yellow-800',
        sent: 'bg-green-100 text-green-800',
        paused: 'bg-orange-100 text-orange-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatNumber = (num) => {
    return (num || 0).toLocaleString('fr-FR');
};
</script>

<template>
    <AppLayout>
        <Head title="Campagnes" />

        <PageContainer size="wide">
            <div class="space-y-section">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-2xl lg:text-3xl font-bold text-secondary-900">Campagnes</h1>
                        <p class="text-secondary-500">Créez et envoyez vos campagnes d'emailing</p>
                    </div>
                <button
                    @click="showCreateModal = true"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                >
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouvelle campagne
                    </span>
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card card-hover">
                    <div class="text-sm font-medium text-secondary-500">Total</div>
                    <div class="mt-1 text-2xl font-bold text-secondary-900">{{ formatNumber(stats.total) }}</div>
                </div>
                <div class="card card-hover">
                    <div class="text-sm font-medium text-secondary-600">Brouillons</div>
                    <div class="mt-1 text-2xl font-bold text-secondary-700">{{ formatNumber(stats.draft) }}</div>
                </div>
                <div class="card card-hover">
                    <div class="text-sm font-medium text-sky-600">Programmées</div>
                    <div class="mt-1 text-2xl font-bold text-sky-700">{{ formatNumber(stats.scheduled) }}</div>
                </div>
                <div class="card card-hover">
                    <div class="text-sm font-medium text-emerald-600">Envoyées</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-700">{{ formatNumber(stats.sent) }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card">
                <div class="flex flex-wrap gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher par nom ou objet..."
                                class="w-full pl-10 pr-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <select
                        v-model="selectedStatus"
                        class="px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="draft">Brouillons</option>
                        <option value="scheduled">Programmées</option>
                        <option value="sending">En cours</option>
                        <option value="sent">Envoyées</option>
                        <option value="paused">En pause</option>
                        <option value="cancelled">Annulées</option>
                    </select>

                    <!-- Type Filter -->
                    <select
                        v-model="selectedType"
                        class="px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">Tous les types</option>
                        <option value="regular">Standard</option>
                        <option value="ab_test">Test A/B</option>
                    </select>

                    <!-- Clear Filters -->
                    <button
                        v-if="search || selectedStatus || selectedType"
                        @click="clearFilters"
                        class="px-4 py-2 text-sm text-secondary-600 hover:text-secondary-800"
                    >
                        Effacer les filtres
                    </button>
                </div>
            </div>

            <!-- Campaigns List -->
            <div class="card overflow-hidden !p-0">
                <div v-if="campaigns.data.length === 0" class="px-4 py-12 text-center">
                    <svg class="mx-auto w-12 h-12 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-medium text-secondary-900">Aucune campagne</h3>
                    <p class="mt-1 text-sm text-secondary-500">
                        Créez votre première campagne pour commencer.
                    </p>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Créer une campagne
                    </button>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div
                        v-for="campaign in campaigns.data"
                        :key="campaign.id"
                        class="p-4 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3">
                                    <Link
                                        :href="campaign.status === 'draft' ? `/campaigns/${campaign.id}/edit` : `/campaigns/${campaign.id}`"
                                        class="text-base font-semibold text-secondary-900 hover:text-primary-600 truncate"
                                    >
                                        {{ campaign.name }}
                                    </Link>
                                    <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', getStatusColor(campaign.status)]">
                                        {{ campaign.status_label }}
                                    </span>
                                    <span v-if="campaign.type === 'ab_test'" class="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">
                                        A/B Test
                                    </span>
                                </div>

                                <div class="mt-1 flex items-center gap-4 text-sm text-secondary-500">
                                    <span v-if="campaign.subject" class="truncate max-w-md">
                                        {{ campaign.subject }}
                                    </span>
                                    <span v-if="campaign.scheduled_at" class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ formatDate(campaign.scheduled_at) }}
                                    </span>
                                </div>

                                <!-- Stats row for sent campaigns -->
                                <div v-if="campaign.status === 'sent' || campaign.status === 'sending'" class="mt-3 flex items-center gap-6 text-sm">
                                    <div class="flex items-center">
                                        <span class="text-secondary-500 mr-1">Envoyés:</span>
                                        <span class="font-medium text-secondary-900">{{ formatNumber(campaign.sent_count) }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-secondary-500 mr-1">Ouvertures:</span>
                                        <span class="font-medium text-green-600">{{ campaign.open_rate }}%</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-secondary-500 mr-1">Clics:</span>
                                        <span class="font-medium text-blue-600">{{ campaign.click_rate }}%</span>
                                    </div>
                                    <div v-if="campaign.bounced_count > 0" class="flex items-center">
                                        <span class="text-secondary-500 mr-1">Rebonds:</span>
                                        <span class="font-medium text-orange-600">{{ formatNumber(campaign.bounced_count) }}</span>
                                    </div>
                                </div>

                                <!-- Recipients count for drafts -->
                                <div v-else-if="campaign.recipients_count > 0" class="mt-2 text-sm text-secondary-500">
                                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ formatNumber(campaign.recipients_count) }} destinataires
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 ml-4">
                                <Link
                                    v-if="campaign.status === 'draft'"
                                    :href="`/campaigns/${campaign.id}/edit`"
                                    class="px-3 py-1.5 text-sm font-medium text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100"
                                >
                                    Continuer
                                </Link>
                                <Link
                                    v-else
                                    :href="`/campaigns/${campaign.id}`"
                                    class="px-3 py-1.5 text-sm font-medium text-secondary-700 bg-secondary-100 rounded-lg hover:bg-secondary-200"
                                >
                                    Voir
                                </Link>

                                <div class="relative group">
                                    <button class="p-1.5 text-secondary-400 hover:text-secondary-600 rounded-lg hover:bg-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                    <div class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-10 hidden group-hover:block">
                                        <button
                                            @click="duplicateCampaign(campaign)"
                                            class="w-full px-4 py-2 text-left text-sm text-secondary-700 hover:bg-gray-50"
                                        >
                                            Dupliquer
                                        </button>
                                        <Link
                                            v-if="campaign.status === 'draft'"
                                            :href="`/campaigns/${campaign.id}/edit`"
                                            class="block px-4 py-2 text-sm text-secondary-700 hover:bg-gray-50"
                                        >
                                            Modifier
                                        </Link>
                                        <button
                                            v-if="campaign.status !== 'sending'"
                                            @click="deleteCampaign(campaign)"
                                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                        >
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="campaigns.data.length > 0" class="px-4 py-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-secondary-500">
                            Affichage de {{ campaigns.from }} à {{ campaigns.to }} sur {{ campaigns.total }} campagnes
                        </div>
                        <div class="flex items-center space-x-2">
                            <Link
                                v-for="link in campaigns.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded-lg',
                                    link.active ? 'bg-primary-600 text-white' : 'text-secondary-600 hover:bg-gray-100',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </PageContainer>

        <!-- Create Campaign Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-secondary-900">Nouvelle campagne</h3>
                    <p class="mt-1 text-sm text-secondary-500">Donnez un nom à votre campagne pour commencer.</p>

                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Nom de la campagne</label>
                            <input
                                v-model="newCampaignName"
                                type="text"
                                placeholder="Ex: Newsletter Janvier 2026"
                                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500"
                                @keyup.enter="createCampaign"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-2">Type de campagne</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    :class="[
                                        'flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition-colors',
                                        newCampaignType === 'regular' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <input type="radio" v-model="newCampaignType" value="regular" class="sr-only" />
                                    <svg class="w-8 h-8 mb-2" :class="newCampaignType === 'regular' ? 'text-primary-600' : 'text-secondary-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm font-medium" :class="newCampaignType === 'regular' ? 'text-primary-900' : 'text-secondary-700'">Standard</span>
                                    <span class="text-xs text-secondary-500 mt-1">Campagne simple</span>
                                </label>

                                <label
                                    :class="[
                                        'flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition-colors',
                                        newCampaignType === 'ab_test' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <input type="radio" v-model="newCampaignType" value="ab_test" class="sr-only" />
                                    <svg class="w-8 h-8 mb-2" :class="newCampaignType === 'ab_test' ? 'text-primary-600' : 'text-secondary-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <span class="text-sm font-medium" :class="newCampaignType === 'ab_test' ? 'text-primary-900' : 'text-secondary-700'">Test A/B</span>
                                    <span class="text-xs text-secondary-500 mt-1">Comparer 2 versions</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="createCampaign"
                            :disabled="!newCampaignName.trim()"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            Créer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

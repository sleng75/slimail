<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ABTestResults from '@/Components/Campaigns/ABTestResults.vue';

const props = defineProps({
    campaign: Object,
    lists: Array,
    variants: {
        type: Array,
        default: () => []
    },
});

// A/B Test computed
const isAbTest = computed(() => props.campaign.type === 'ab_test');
const abTestConfig = computed(() => props.campaign.ab_test_config || {});
const abTestComplete = computed(() => {
    if (!isAbTest.value) return false;
    return props.campaign.status === 'sent' || props.variants.some(v => v.is_winner);
});

const refreshInterval = ref(null);

const formatNumber = (num) => {
    return (num || 0).toLocaleString('fr-FR');
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
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

const pauseCampaign = () => {
    router.post(`/campaigns/${props.campaign.id}/pause`);
};

const resumeCampaign = () => {
    router.post(`/campaigns/${props.campaign.id}/resume`);
};

const cancelCampaign = () => {
    if (confirm('Voulez-vous vraiment annuler cette campagne ?')) {
        router.post(`/campaigns/${props.campaign.id}/cancel`);
    }
};

const duplicateCampaign = () => {
    router.post(`/campaigns/${props.campaign.id}/duplicate`);
};

// A/B Test actions
const selectWinner = (variantKey) => {
    router.post(`/campaigns/${props.campaign.id}/ab-test/select-winner`, {
        variant_key: variantKey
    });
};

const sendToRemaining = (variantKey) => {
    router.post(`/campaigns/${props.campaign.id}/ab-test/send-remaining`, {
        variant_key: variantKey
    });
};

// Auto-refresh stats for sending campaigns
onMounted(() => {
    if (props.campaign.status === 'sending') {
        refreshInterval.value = setInterval(() => {
            router.reload({ only: ['campaign'] });
        }, 10000); // Every 10 seconds
    }
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});
</script>

<template>
    <AppLayout>
        <Head :title="`Campagne: ${campaign.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link href="/campaigns" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900">{{ campaign.name }}</h1>
                            <span :class="['px-3 py-1 text-sm font-medium rounded-full', getStatusColor(campaign.status)]">
                                {{ campaign.status_label }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ campaign.subject }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="campaign.status === 'sending'"
                        @click="pauseCampaign"
                        class="px-4 py-2 text-sm font-medium text-orange-700 bg-orange-100 rounded-lg hover:bg-orange-200"
                    >
                        Mettre en pause
                    </button>
                    <button
                        v-if="campaign.status === 'paused'"
                        @click="resumeCampaign"
                        class="px-4 py-2 text-sm font-medium text-green-700 bg-green-100 rounded-lg hover:bg-green-200"
                    >
                        Reprendre
                    </button>
                    <button
                        v-if="['sending', 'paused', 'scheduled'].includes(campaign.status)"
                        @click="cancelCampaign"
                        class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200"
                    >
                        Annuler
                    </button>
                    <button
                        @click="duplicateCampaign"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Dupliquer
                    </button>
                </div>
            </div>

            <!-- Progress bar for sending -->
            <div v-if="campaign.status === 'sending'" class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Envoi en cours...</span>
                    <span class="text-sm text-gray-500">
                        {{ formatNumber(campaign.sent_count) }} / {{ formatNumber(campaign.recipients_count) }}
                    </span>
                </div>
                <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        :style="{ width: `${(campaign.sent_count / campaign.recipients_count) * 100}%` }"
                        class="h-full bg-primary-600 rounded-full transition-all duration-500"
                    ></div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-gray-500">Envoyés</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ formatNumber(campaign.sent_count) }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-gray-500">Délivrés</div>
                    <div class="mt-1 text-2xl font-bold text-green-600">{{ formatNumber(campaign.delivered_count) }}</div>
                    <div class="text-xs text-gray-400">{{ campaign.delivery_rate || 0 }}%</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-gray-500">Ouvertures</div>
                    <div class="mt-1 text-2xl font-bold text-blue-600">{{ formatNumber(campaign.opened_count) }}</div>
                    <div class="text-xs text-gray-400">{{ campaign.open_rate }}%</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-gray-500">Clics</div>
                    <div class="mt-1 text-2xl font-bold text-purple-600">{{ formatNumber(campaign.clicked_count) }}</div>
                    <div class="text-xs text-gray-400">{{ campaign.click_rate }}%</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-gray-500">Rebonds</div>
                    <div class="mt-1 text-2xl font-bold text-orange-600">{{ formatNumber(campaign.bounced_count) }}</div>
                    <div class="text-xs text-gray-400">{{ campaign.bounce_rate || 0 }}%</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-gray-500">Désinscriptions</div>
                    <div class="mt-1 text-2xl font-bold text-red-600">{{ formatNumber(campaign.unsubscribed_count) }}</div>
                </div>
            </div>

            <!-- A/B Test Results (if applicable) -->
            <ABTestResults
                v-if="isAbTest && variants.length > 0"
                :campaign="campaign"
                :variants="variants"
                :config="abTestConfig"
                :is-complete="abTestComplete"
                @select-winner="selectWinner"
                @send-to-remaining="sendToRemaining"
            />

            <!-- Details and Preview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Details -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Campaign Info -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ campaign.type_label || 'Standard' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Expéditeur</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ campaign.from_name }}</dd>
                                <dd class="text-sm text-gray-500">{{ campaign.from_email }}</dd>
                            </div>
                            <div v-if="campaign.scheduled_at">
                                <dt class="text-sm font-medium text-gray-500">Programmé pour</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(campaign.scheduled_at) }}</dd>
                            </div>
                            <div v-if="campaign.started_at">
                                <dt class="text-sm font-medium text-gray-500">Démarré le</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(campaign.started_at) }}</dd>
                            </div>
                            <div v-if="campaign.completed_at">
                                <dt class="text-sm font-medium text-gray-500">Terminé le</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(campaign.completed_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Créé par</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ campaign.creator?.name || '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Recipients Info -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Destinataires</h2>
                        <div class="text-3xl font-bold text-gray-900 mb-4">
                            {{ formatNumber(campaign.recipients_count) }}
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="list in lists"
                                :key="list.id"
                                class="flex items-center justify-between text-sm"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-2 h-2 rounded-full"
                                        :style="{ backgroundColor: list.color || '#6366f1' }"
                                    ></span>
                                    <span class="text-gray-700">{{ list.name }}</span>
                                </div>
                                <span class="text-gray-500">{{ formatNumber(list.contacts_count) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tracking Settings -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Suivi</h2>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Tracking des ouvertures</span>
                                <span :class="campaign.track_opens ? 'text-green-600' : 'text-gray-400'" class="text-sm font-medium">
                                    {{ campaign.track_opens ? 'Activé' : 'Désactivé' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Tracking des clics</span>
                                <span :class="campaign.track_clicks ? 'text-green-600' : 'text-gray-400'" class="text-sm font-medium">
                                    {{ campaign.track_clicks ? 'Activé' : 'Désactivé' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Google Analytics</span>
                                <span :class="campaign.google_analytics ? 'text-green-600' : 'text-gray-400'" class="text-sm font-medium">
                                    {{ campaign.google_analytics ? 'Activé' : 'Désactivé' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Preview -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Aperçu de l'email</h2>
                            <a
                                :href="`/campaigns/${campaign.id}/preview`"
                                target="_blank"
                                class="text-sm text-primary-600 hover:text-primary-800"
                            >
                                Ouvrir dans un nouvel onglet
                            </a>
                        </div>
                        <div class="p-4 bg-gray-100">
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <!-- Email header simulation -->
                                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                    <div class="text-xs text-gray-500 mb-1">De: {{ campaign.from_name }} &lt;{{ campaign.from_email }}&gt;</div>
                                    <div class="text-sm font-medium text-gray-900">{{ campaign.subject }}</div>
                                    <div v-if="campaign.preview_text" class="text-xs text-gray-500 mt-1">{{ campaign.preview_text }}</div>
                                </div>
                                <!-- Email content -->
                                <iframe
                                    :src="`/campaigns/${campaign.id}/preview`"
                                    class="w-full h-[500px] border-0"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

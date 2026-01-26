<script setup>
import { computed, ref } from 'vue';
import { Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(ArcElement, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    campaign: {
        type: Object,
        required: true
    },
    variants: {
        type: Array,
        default: () => []
    },
    config: {
        type: Object,
        default: () => ({})
    },
    isComplete: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['select-winner', 'send-to-remaining']);

const selectedVariantForSend = ref(null);
const showConfirmModal = ref(false);

// Get variant A and B
const variantA = computed(() => props.variants.find(v => v.variant_key === 'A') || {});
const variantB = computed(() => props.variants.find(v => v.variant_key === 'B') || {});
const winner = computed(() => props.variants.find(v => v.is_winner));

// Determine winning criteria text
const criteriaLabel = computed(() => {
    return props.config.winning_criteria === 'clicks' ? 'Taux de clic' : 'Taux d\'ouverture';
});

// Calculate who is currently winning
const currentLeader = computed(() => {
    if (!variantA.value || !variantB.value) return null;

    const criteriaField = props.config.winning_criteria === 'clicks' ? 'click_rate' : 'open_rate';
    const aRate = variantA.value[criteriaField] || 0;
    const bRate = variantB.value[criteriaField] || 0;

    if (aRate === bRate) return 'tie';
    return aRate > bRate ? 'A' : 'B';
});

// Stats comparison
const statsComparison = computed(() => {
    const a = variantA.value;
    const b = variantB.value;

    return [
        {
            label: 'Envoyés',
            a: a.sent_count || 0,
            b: b.sent_count || 0,
            format: 'number'
        },
        {
            label: 'Délivrés',
            a: a.delivered_count || 0,
            b: b.delivered_count || 0,
            format: 'number'
        },
        {
            label: 'Ouverts',
            a: a.opened_count || 0,
            b: b.opened_count || 0,
            aRate: a.open_rate || 0,
            bRate: b.open_rate || 0,
            format: 'rate',
            isWinningCriteria: props.config.winning_criteria === 'opens'
        },
        {
            label: 'Cliqués',
            a: a.clicked_count || 0,
            b: b.clicked_count || 0,
            aRate: a.click_rate || 0,
            bRate: b.click_rate || 0,
            format: 'rate',
            isWinningCriteria: props.config.winning_criteria === 'clicks'
        },
        {
            label: 'Rebonds',
            a: a.bounced_count || 0,
            b: b.bounced_count || 0,
            format: 'number',
            negative: true
        },
    ];
});

// Chart data for comparison
const comparisonChartData = computed(() => ({
    labels: ['Variante A', 'Variante B'],
    datasets: [
        {
            label: 'Taux d\'ouverture',
            data: [variantA.value.open_rate || 0, variantB.value.open_rate || 0],
            backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(34, 197, 94, 0.8)'],
            borderColor: ['rgb(59, 130, 246)', 'rgb(34, 197, 94)'],
            borderWidth: 1,
            borderRadius: 8,
        },
    ],
}));

const clickChartData = computed(() => ({
    labels: ['Variante A', 'Variante B'],
    datasets: [
        {
            label: 'Taux de clic',
            data: [variantA.value.click_rate || 0, variantB.value.click_rate || 0],
            backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(34, 197, 94, 0.8)'],
            borderColor: ['rgb(59, 130, 246)', 'rgb(34, 197, 94)'],
            borderWidth: 1,
            borderRadius: 8,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label: (context) => `${context.parsed.y.toFixed(2)}%`
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: (value) => value + '%'
            }
        }
    }
};

// Distribution chart
const distributionChartData = computed(() => ({
    labels: ['Variante A', 'Variante B', 'Restants'],
    datasets: [
        {
            data: [
                variantA.value.percentage || 0,
                variantB.value.percentage || 0,
                100 - (variantA.value.percentage || 0) - (variantB.value.percentage || 0)
            ],
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(168, 85, 247, 0.8)'
            ],
            borderWidth: 0,
        }
    ]
}));

const distributionChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        }
    },
    cutout: '60%'
};

// Methods
const formatNumber = (num) => {
    return (num || 0).toLocaleString('fr-FR');
};

const getTestTypeLabel = () => {
    const types = {
        subject: 'Objet de l\'email',
        from_name: 'Nom de l\'expéditeur',
        content: 'Contenu'
    };
    return types[props.config.test_type] || 'Non défini';
};

const getTestedValue = (variant) => {
    if (props.config.test_type === 'subject') {
        return variant.subject;
    }
    if (props.config.test_type === 'from_name') {
        return variant.from_name;
    }
    return 'Contenu personnalisé';
};

const confirmSelectWinner = (variantKey) => {
    selectedVariantForSend.value = variantKey;
    showConfirmModal.value = true;
};

const executeSelectWinner = () => {
    emit('select-winner', selectedVariantForSend.value);
    showConfirmModal.value = false;
};

const getRemainingCount = () => {
    const totalSent = (variantA.value.sent_count || 0) + (variantB.value.sent_count || 0);
    const testPercentage = (variantA.value.percentage || 0) + (variantB.value.percentage || 0);
    const totalRecipients = Math.round(totalSent / (testPercentage / 100));
    return totalRecipients - totalSent;
};

// Improvement percentage
const improvement = computed(() => {
    if (!winner.value || currentLeader.value === 'tie') return 0;

    const criteriaField = props.config.winning_criteria === 'clicks' ? 'click_rate' : 'open_rate';
    const aRate = variantA.value[criteriaField] || 0;
    const bRate = variantB.value[criteriaField] || 0;

    const loserRate = currentLeader.value === 'A' ? bRate : aRate;
    const winnerRate = currentLeader.value === 'A' ? aRate : bRate;

    if (loserRate === 0) return 100;
    return Math.round(((winnerRate - loserRate) / loserRate) * 100);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header / Status -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div :class="[
                        'p-3 rounded-xl',
                        isComplete ? 'bg-green-100' : 'bg-purple-100'
                    ]">
                        <svg v-if="isComplete" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ isComplete ? 'Test A/B terminé' : 'Test A/B en cours' }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            Test sur : <span class="font-medium">{{ getTestTypeLabel() }}</span>
                            &bull; Critère : <span class="font-medium">{{ criteriaLabel }}</span>
                        </p>
                    </div>
                </div>

                <!-- Winner badge -->
                <div v-if="winner" class="flex items-center gap-2 px-4 py-2 bg-green-100 rounded-xl">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <span class="text-sm font-semibold text-green-700">
                        Gagnant: Variante {{ winner.variant_key }}
                    </span>
                </div>

                <!-- Current leader (test in progress) -->
                <div v-else-if="currentLeader && currentLeader !== 'tie'" class="flex items-center gap-2 px-4 py-2 bg-amber-100 rounded-xl">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <span class="text-sm font-medium text-amber-700">
                        En tête: Variante {{ currentLeader }}
                    </span>
                </div>
            </div>

            <!-- Improvement stats -->
            <div v-if="winner && improvement > 0" class="mt-4 p-4 bg-green-50 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-green-700">
                            La variante {{ winner.variant_key }} a obtenu un {{ criteriaLabel.toLowerCase() }}
                            <span class="font-bold">{{ improvement }}% supérieur</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variants Comparison -->
        <div class="grid grid-cols-2 gap-6">
            <!-- Variant A Card -->
            <div :class="[
                'bg-white rounded-xl border-2 overflow-hidden transition-all',
                winner?.variant_key === 'A' ? 'border-green-500 ring-2 ring-green-200' : 'border-gray-200'
            ]">
                <!-- Header -->
                <div :class="[
                    'px-6 py-4 border-b',
                    winner?.variant_key === 'A' ? 'bg-green-50 border-green-100' : 'bg-blue-50 border-blue-100'
                ]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold',
                                winner?.variant_key === 'A' ? 'bg-green-200 text-green-800' : 'bg-blue-200 text-blue-800'
                            ]">A</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Variante A</h3>
                                <p class="text-xs text-gray-500">{{ variantA.percentage || 0 }}% des destinataires</p>
                            </div>
                        </div>
                        <div v-if="winner?.variant_key === 'A'" class="flex items-center gap-1 text-green-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span class="text-sm font-semibold">Gagnant</span>
                        </div>
                    </div>
                </div>

                <!-- Tested Value -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ getTestTypeLabel() }}</p>
                    <p class="text-sm font-medium text-gray-900 line-clamp-2">
                        {{ getTestedValue(variantA) || '(Non défini)' }}
                    </p>
                </div>

                <!-- Stats -->
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="stat in statsComparison" :key="stat.label" class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ stat.label }}</span>
                            <div class="text-right">
                                <span class="font-semibold text-gray-900">{{ formatNumber(stat.a) }}</span>
                                <span v-if="stat.format === 'rate'" class="text-sm text-gray-500 ml-1">
                                    ({{ stat.aRate?.toFixed(2) || 0 }}%)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Manual winner selection -->
                    <div v-if="!winner && isComplete" class="mt-6">
                        <button
                            @click="confirmSelectWinner('A')"
                            class="w-full px-4 py-2 text-sm font-medium text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50"
                        >
                            Sélectionner comme gagnant
                        </button>
                    </div>
                </div>
            </div>

            <!-- Variant B Card -->
            <div :class="[
                'bg-white rounded-xl border-2 overflow-hidden transition-all',
                winner?.variant_key === 'B' ? 'border-green-500 ring-2 ring-green-200' : 'border-gray-200'
            ]">
                <!-- Header -->
                <div :class="[
                    'px-6 py-4 border-b',
                    winner?.variant_key === 'B' ? 'bg-green-50 border-green-100' : 'bg-green-50 border-green-100'
                ]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold',
                                winner?.variant_key === 'B' ? 'bg-green-200 text-green-800' : 'bg-green-200 text-green-800'
                            ]">B</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Variante B</h3>
                                <p class="text-xs text-gray-500">{{ variantB.percentage || 0 }}% des destinataires</p>
                            </div>
                        </div>
                        <div v-if="winner?.variant_key === 'B'" class="flex items-center gap-1 text-green-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span class="text-sm font-semibold">Gagnant</span>
                        </div>
                    </div>
                </div>

                <!-- Tested Value -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ getTestTypeLabel() }}</p>
                    <p class="text-sm font-medium text-gray-900 line-clamp-2">
                        {{ getTestedValue(variantB) || '(Non défini)' }}
                    </p>
                </div>

                <!-- Stats -->
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="stat in statsComparison" :key="stat.label" class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ stat.label }}</span>
                            <div class="text-right">
                                <span class="font-semibold text-gray-900">{{ formatNumber(stat.b) }}</span>
                                <span v-if="stat.format === 'rate'" class="text-sm text-gray-500 ml-1">
                                    ({{ stat.bRate?.toFixed(2) || 0 }}%)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Manual winner selection -->
                    <div v-if="!winner && isComplete" class="mt-6">
                        <button
                            @click="confirmSelectWinner('B')"
                            class="w-full px-4 py-2 text-sm font-medium text-green-600 border border-green-200 rounded-lg hover:bg-green-50"
                        >
                            Sélectionner comme gagnant
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-3 gap-6">
            <!-- Open Rate Chart -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Taux d'ouverture</h4>
                <div class="h-48">
                    <Bar :data="comparisonChartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Click Rate Chart -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Taux de clic</h4>
                <div class="h-48">
                    <Bar :data="clickChartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Distribution Chart -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Distribution</h4>
                <div class="h-48">
                    <Doughnut :data="distributionChartData" :options="distributionChartOptions" />
                </div>
            </div>
        </div>

        <!-- Send to Remaining -->
        <div v-if="winner && getRemainingCount() > 0" class="bg-purple-50 rounded-xl border border-purple-200 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-purple-900">Envoyer aux destinataires restants</h4>
                        <p class="text-sm text-purple-700">
                            {{ formatNumber(getRemainingCount()) }} contacts n'ont pas encore reçu l'email.
                            Envoyez-leur la variante gagnante.
                        </p>
                    </div>
                </div>
                <button
                    @click="$emit('send-to-remaining', winner.variant_key)"
                    class="px-6 py-3 text-sm font-semibold text-white bg-purple-600 rounded-lg hover:bg-purple-700"
                >
                    Envoyer la variante {{ winner.variant_key }}
                </button>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showConfirmModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-amber-100 rounded-lg">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Confirmer la sélection</h3>
                            <p class="text-sm text-gray-500">Cette action est irréversible</p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 mb-6">
                        Vous êtes sur le point de sélectionner la <strong>Variante {{ selectedVariantForSend }}</strong> comme gagnante.
                        Cette variante sera envoyée aux {{ formatNumber(getRemainingCount()) }} destinataires restants.
                    </p>

                    <div class="flex justify-end gap-3">
                        <button
                            @click="showConfirmModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="executeSelectWinner"
                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700"
                        >
                            Confirmer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

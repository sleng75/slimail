<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    campaign: Object,
    variants: {
        type: Array,
        default: () => []
    },
    modelValue: {
        type: Object,
        default: () => ({
            enabled: false,
            test_type: 'subject', // subject, content, from_name
            test_percentage: 20,
            winning_criteria: 'opens', // opens, clicks
            wait_hours: 4,
            auto_send_winner: true,
            variants: []
        })
    }
});

const emit = defineEmits(['update:modelValue', 'save']);

const localConfig = ref({ ...props.modelValue });
const activeVariant = ref('A');
const showPreview = ref(false);

// Variant data
const variantA = ref({
    key: 'A',
    name: 'Variante A',
    subject: props.campaign?.subject || '',
    from_name: props.campaign?.from_name || '',
    preview_text: props.campaign?.preview_text || '',
    html_content: props.campaign?.html_content || '',
    percentage: 50
});

const variantB = ref({
    key: 'B',
    name: 'Variante B',
    subject: '',
    from_name: '',
    preview_text: '',
    html_content: '',
    percentage: 50
});

// Load existing variants if any
watch(() => props.variants, (newVariants) => {
    if (newVariants && newVariants.length > 0) {
        const vA = newVariants.find(v => v.variant_key === 'A');
        const vB = newVariants.find(v => v.variant_key === 'B');
        if (vA) {
            variantA.value = { ...variantA.value, ...vA, key: 'A' };
        }
        if (vB) {
            variantB.value = { ...variantB.value, ...vB, key: 'B' };
        }
    }
}, { immediate: true });

// Test type options
const testTypes = [
    { value: 'subject', label: 'Objet de l\'email', icon: 'subject', description: 'Testez différents objets pour optimiser le taux d\'ouverture' },
    { value: 'from_name', label: 'Nom de l\'expéditeur', icon: 'person', description: 'Testez si le nom de l\'expéditeur influence l\'ouverture' },
    { value: 'content', label: 'Contenu', icon: 'content', description: 'Testez différentes versions de votre email' },
];

// Winning criteria options
const winningCriterias = [
    { value: 'opens', label: 'Taux d\'ouverture', description: 'La variante avec le meilleur taux d\'ouverture gagne' },
    { value: 'clicks', label: 'Taux de clic', description: 'La variante avec le meilleur taux de clic gagne' },
];

// Computed
const currentVariant = computed(() => activeVariant.value === 'A' ? variantA.value : variantB.value);

// Computed with getter/setter for v-model binding
const currentSubject = computed({
    get: () => activeVariant.value === 'A' ? variantA.value.subject : variantB.value.subject,
    set: (value) => {
        if (activeVariant.value === 'A') {
            variantA.value.subject = value;
        } else {
            variantB.value.subject = value;
        }
    }
});

const currentFromName = computed({
    get: () => activeVariant.value === 'A' ? variantA.value.from_name : variantB.value.from_name,
    set: (value) => {
        if (activeVariant.value === 'A') {
            variantA.value.from_name = value;
        } else {
            variantB.value.from_name = value;
        }
    }
});

const totalPercentage = computed(() => variantA.value.percentage + variantB.value.percentage);

const remainingPercentage = computed(() => 100 - localConfig.value.test_percentage);

const isValid = computed(() => {
    if (!localConfig.value.enabled) return true;

    if (localConfig.value.test_type === 'subject') {
        return variantA.value.subject && variantB.value.subject && variantA.value.subject !== variantB.value.subject;
    }
    if (localConfig.value.test_type === 'from_name') {
        return variantA.value.from_name && variantB.value.from_name && variantA.value.from_name !== variantB.value.from_name;
    }
    if (localConfig.value.test_type === 'content') {
        return variantA.value.html_content && variantB.value.html_content;
    }
    return false;
});

const validationMessage = computed(() => {
    if (!localConfig.value.enabled) return '';

    if (localConfig.value.test_type === 'subject') {
        if (!variantA.value.subject || !variantB.value.subject) {
            return 'Veuillez renseigner les deux objets';
        }
        if (variantA.value.subject === variantB.value.subject) {
            return 'Les deux objets doivent être différents';
        }
    }
    if (localConfig.value.test_type === 'from_name') {
        if (!variantA.value.from_name || !variantB.value.from_name) {
            return 'Veuillez renseigner les deux noms d\'expéditeur';
        }
        if (variantA.value.from_name === variantB.value.from_name) {
            return 'Les deux noms doivent être différents';
        }
    }
    if (localConfig.value.test_type === 'content') {
        if (!variantA.value.html_content || !variantB.value.html_content) {
            return 'Veuillez créer le contenu des deux variantes';
        }
    }
    return '';
});

// Methods
const toggleABTest = () => {
    localConfig.value.enabled = !localConfig.value.enabled;
    emitUpdate();
};

const selectTestType = (type) => {
    localConfig.value.test_type = type;
    emitUpdate();
};

const updatePercentage = (value) => {
    localConfig.value.test_percentage = parseInt(value);
    // Split equally between A and B for the test portion
    variantA.value.percentage = Math.floor(localConfig.value.test_percentage / 2);
    variantB.value.percentage = localConfig.value.test_percentage - variantA.value.percentage;
    emitUpdate();
};

const emitUpdate = () => {
    emit('update:modelValue', {
        ...localConfig.value,
        variants: [
            { ...variantA.value },
            { ...variantB.value }
        ]
    });
};

const saveVariants = () => {
    emit('save', {
        config: localConfig.value,
        variants: [variantA.value, variantB.value]
    });
};

// Update parent on changes
watch([variantA, variantB, localConfig], () => {
    emitUpdate();
}, { deep: true });
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Test A/B</h3>
                        <p class="text-sm text-gray-500">Optimisez vos campagnes avec des tests comparatifs</p>
                    </div>
                </div>
                <button
                    @click="toggleABTest"
                    :class="[
                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2',
                        localConfig.enabled ? 'bg-purple-600' : 'bg-gray-200'
                    ]"
                >
                    <span
                        :class="[
                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                            localConfig.enabled ? 'translate-x-5' : 'translate-x-0'
                        ]"
                    />
                </button>
            </div>
        </div>

        <!-- Content (when enabled) -->
        <div v-if="localConfig.enabled" class="p-6 space-y-8">
            <!-- Test Type Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Que voulez-vous tester ?</label>
                <div class="grid grid-cols-3 gap-4">
                    <button
                        v-for="type in testTypes"
                        :key="type.value"
                        @click="selectTestType(type.value)"
                        :class="[
                            'relative flex flex-col items-start p-4 border-2 rounded-xl transition-all text-left',
                            localConfig.test_type === type.value
                                ? 'border-purple-500 bg-purple-50'
                                : 'border-gray-200 hover:border-gray-300'
                        ]"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <svg v-if="type.value === 'subject'" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <svg v-else-if="type.value === 'from_name'" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                            <span class="font-medium text-gray-900">{{ type.label }}</span>
                        </div>
                        <p class="text-xs text-gray-500">{{ type.description }}</p>
                        <div
                            v-if="localConfig.test_type === type.value"
                            class="absolute top-2 right-2 w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center"
                        >
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Variants Editor -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <label class="block text-sm font-medium text-gray-700">Vos variantes</label>
                    <button
                        @click="showPreview = !showPreview"
                        class="text-sm text-purple-600 hover:text-purple-700"
                    >
                        {{ showPreview ? 'Masquer l\'aperçu' : 'Voir l\'aperçu côte à côte' }}
                    </button>
                </div>

                <!-- Variant Tabs -->
                <div class="flex border-b border-gray-200 mb-4">
                    <button
                        @click="activeVariant = 'A'"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2 transition-colors -mb-px',
                            activeVariant === 'A'
                                ? 'text-purple-600 border-purple-600'
                                : 'text-gray-500 border-transparent hover:text-gray-700'
                        ]"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">A</span>
                            Variante A (Original)
                        </span>
                    </button>
                    <button
                        @click="activeVariant = 'B'"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2 transition-colors -mb-px',
                            activeVariant === 'B'
                                ? 'text-purple-600 border-purple-600'
                                : 'text-gray-500 border-transparent hover:text-gray-700'
                        ]"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold flex items-center justify-center">B</span>
                            Variante B
                        </span>
                    </button>
                </div>

                <!-- Variant Content -->
                <div class="bg-gray-50 rounded-xl p-4">
                    <!-- Subject Test -->
                    <div v-if="localConfig.test_type === 'subject'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Objet de l'email - Variante {{ activeVariant }}
                            </label>
                            <input
                                v-model="currentSubject"
                                type="text"
                                placeholder="Entrez l'objet de l'email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                {{ activeVariant === 'A' ? 'L\'objet original de votre campagne' : 'L\'objet alternatif à tester' }}
                            </p>
                        </div>
                    </div>

                    <!-- From Name Test -->
                    <div v-else-if="localConfig.test_type === 'from_name'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nom de l'expéditeur - Variante {{ activeVariant }}
                            </label>
                            <input
                                v-model="currentFromName"
                                type="text"
                                placeholder="Nom de l'expéditeur"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Ex: "Mon Entreprise", "Jean de Mon Entreprise", "L'équipe Marketing"
                            </p>
                        </div>
                    </div>

                    <!-- Content Test -->
                    <div v-else-if="localConfig.test_type === 'content'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900">Contenu - Variante {{ activeVariant }}</h4>
                                <p class="text-xs text-gray-500">
                                    {{ currentVariant.html_content ? 'Contenu configuré' : 'Aucun contenu configuré' }}
                                </p>
                            </div>
                            <button class="px-4 py-2 text-sm font-medium text-purple-600 border border-purple-200 rounded-lg hover:bg-purple-50">
                                Modifier dans l'éditeur
                            </button>
                        </div>
                        <div v-if="currentVariant.html_content" class="border border-gray-200 rounded-lg bg-white p-4 h-48 overflow-hidden">
                            <div v-html="currentVariant.html_content" class="text-sm text-gray-600 transform scale-50 origin-top-left"></div>
                        </div>
                        <div v-else class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                            <p class="text-gray-500">Cliquez sur "Modifier dans l'éditeur" pour créer le contenu</p>
                        </div>
                    </div>
                </div>

                <!-- Side by side preview -->
                <div v-if="showPreview" class="mt-6 grid grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-blue-50 px-4 py-2 border-b border-blue-100">
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-blue-700">
                                <span class="w-5 h-5 rounded-full bg-blue-200 text-blue-800 text-xs font-bold flex items-center justify-center">A</span>
                                Variante A
                            </span>
                        </div>
                        <div class="p-4 bg-white">
                            <div v-if="localConfig.test_type === 'subject'" class="space-y-2">
                                <p class="text-xs text-gray-500">Objet:</p>
                                <p class="font-medium text-gray-900">{{ variantA.subject || '(Non défini)' }}</p>
                            </div>
                            <div v-else-if="localConfig.test_type === 'from_name'" class="space-y-2">
                                <p class="text-xs text-gray-500">Expéditeur:</p>
                                <p class="font-medium text-gray-900">{{ variantA.from_name || '(Non défini)' }}</p>
                            </div>
                            <div v-else class="text-sm text-gray-500">
                                {{ variantA.html_content ? 'Contenu personnalisé' : 'Aucun contenu' }}
                            </div>
                        </div>
                    </div>
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-green-50 px-4 py-2 border-b border-green-100">
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-green-700">
                                <span class="w-5 h-5 rounded-full bg-green-200 text-green-800 text-xs font-bold flex items-center justify-center">B</span>
                                Variante B
                            </span>
                        </div>
                        <div class="p-4 bg-white">
                            <div v-if="localConfig.test_type === 'subject'" class="space-y-2">
                                <p class="text-xs text-gray-500">Objet:</p>
                                <p class="font-medium text-gray-900">{{ variantB.subject || '(Non défini)' }}</p>
                            </div>
                            <div v-else-if="localConfig.test_type === 'from_name'" class="space-y-2">
                                <p class="text-xs text-gray-500">Expéditeur:</p>
                                <p class="font-medium text-gray-900">{{ variantB.from_name || '(Non défini)' }}</p>
                            </div>
                            <div v-else class="text-sm text-gray-500">
                                {{ variantB.html_content ? 'Contenu personnalisé' : 'Aucun contenu' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Configuration -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Test Percentage -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pourcentage de test
                    </label>
                    <div class="space-y-3">
                        <input
                            type="range"
                            min="10"
                            max="50"
                            step="5"
                            :value="localConfig.test_percentage"
                            @input="updatePercentage($event.target.value)"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-purple-600"
                        />
                        <div class="flex justify-between text-sm">
                            <span class="text-purple-600 font-medium">{{ localConfig.test_percentage }}% en test ({{ Math.floor(localConfig.test_percentage / 2) }}% par variante)</span>
                            <span class="text-gray-500">{{ remainingPercentage }}% au gagnant</span>
                        </div>
                        <div class="flex h-3 rounded-full overflow-hidden bg-gray-100">
                            <div class="bg-blue-500" :style="{ width: Math.floor(localConfig.test_percentage / 2) + '%' }"></div>
                            <div class="bg-green-500" :style="{ width: (localConfig.test_percentage - Math.floor(localConfig.test_percentage / 2)) + '%' }"></div>
                            <div class="bg-purple-500 flex-1"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Variante A</span>
                            <span>Variante B</span>
                            <span>Gagnant</span>
                        </div>
                    </div>
                </div>

                <!-- Winning Criteria & Duration -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Critère de victoire
                        </label>
                        <div class="flex gap-3">
                            <button
                                v-for="criteria in winningCriterias"
                                :key="criteria.value"
                                @click="localConfig.winning_criteria = criteria.value"
                                :class="[
                                    'flex-1 px-4 py-3 border-2 rounded-lg text-left transition-all',
                                    localConfig.winning_criteria === criteria.value
                                        ? 'border-purple-500 bg-purple-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <span class="block text-sm font-medium text-gray-900">{{ criteria.label }}</span>
                                <span class="block text-xs text-gray-500 mt-1">{{ criteria.description }}</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Durée du test
                        </label>
                        <select
                            v-model="localConfig.wait_hours"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option :value="1">1 heure</option>
                            <option :value="2">2 heures</option>
                            <option :value="4">4 heures</option>
                            <option :value="6">6 heures</option>
                            <option :value="12">12 heures</option>
                            <option :value="24">24 heures</option>
                            <option :value="48">48 heures</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Temps d'attente avant de déterminer le gagnant</p>
                    </div>
                </div>
            </div>

            <!-- Auto send toggle -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                <div>
                    <h4 class="font-medium text-gray-900">Envoi automatique au gagnant</h4>
                    <p class="text-sm text-gray-500">Envoyer automatiquement la variante gagnante aux {{ remainingPercentage }}% restants</p>
                </div>
                <button
                    @click="localConfig.auto_send_winner = !localConfig.auto_send_winner"
                    :class="[
                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2',
                        localConfig.auto_send_winner ? 'bg-purple-600' : 'bg-gray-200'
                    ]"
                >
                    <span
                        :class="[
                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                            localConfig.auto_send_winner ? 'translate-x-5' : 'translate-x-0'
                        ]"
                    />
                </button>
            </div>

            <!-- Validation Message -->
            <div v-if="validationMessage" class="flex items-center gap-2 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-700">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="text-sm">{{ validationMessage }}</span>
            </div>
        </div>

        <!-- Disabled state info -->
        <div v-else class="p-6 bg-gray-50">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900">Pourquoi utiliser les tests A/B ?</h4>
                    <p class="text-sm text-gray-500 mt-1">
                        Les tests A/B vous permettent d'envoyer deux versions différentes de votre email à un petit groupe de destinataires
                        pour identifier quelle version performe le mieux avant d'envoyer à toute votre liste.
                    </p>
                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <div class="text-center p-3 bg-white rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">+23%</div>
                            <div class="text-xs text-gray-500">d'ouvertures en moyenne</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">+18%</div>
                            <div class="text-xs text-gray-500">de clics supplémentaires</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">100%</div>
                            <div class="text-xs text-gray-500">automatisé</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

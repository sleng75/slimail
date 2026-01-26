<script setup>
/**
 * StepConfigModal Component
 * Modal for configuring automation steps
 */

import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    step: Object, // null for new step
    stepTypes: Object,
    conditionTypes: Object,
    lists: Array,
    tags: Array,
    templates: Array,
});

const emit = defineEmits(['save', 'close']);

// Form state
const selectedType = ref(props.step?.type || '');
const stepName = ref(props.step?.name || '');
const config = ref(props.step?.config || {});

// Step type categories
const actionSteps = computed(() => {
    return Object.entries(props.stepTypes || {})
        .filter(([_, info]) => info.category === 'action')
        .map(([type, info]) => ({ type, ...info }));
});

const flowSteps = computed(() => {
    return Object.entries(props.stepTypes || {})
        .filter(([_, info]) => info.category === 'flow')
        .map(([type, info]) => ({ type, ...info }));
});

// Watch type changes to reset config
watch(selectedType, (newType, oldType) => {
    if (newType !== oldType && !props.step) {
        config.value = getDefaultConfig(newType);
    }
});

// Get default config for step type
const getDefaultConfig = (type) => {
    switch (type) {
        case 'wait':
            return { wait_type: 'duration', duration_value: 1, duration_unit: 'days' };
        case 'condition':
            return { condition_type: '', operator: 'equals' };
        case 'send_email':
            return { subject: '', html_content: '', from_name: '', from_email: '' };
        case 'webhook':
            return { url: '', method: 'POST', headers: {} };
        default:
            return {};
    }
};

// Initialize config for editing
onMounted(() => {
    if (props.step?.config) {
        config.value = { ...props.step.config };
    }
});

// Get step icon
const getStepIcon = (type) => {
    const icons = {
        send_email: '📧',
        wait: '⏰',
        condition: '⚡',
        add_tag: '🏷️',
        remove_tag: '🏷️',
        add_to_list: '📋',
        remove_from_list: '📋',
        update_field: '✏️',
        webhook: '🌐',
        goal: '🎯',
        exit: '🚪',
    };
    return icons[type] || '⚙️';
};

// Save step
const save = () => {
    if (!selectedType.value) return;

    emit('save', {
        type: selectedType.value,
        name: stepName.value || null,
        config: config.value,
    });
};

// Can save?
const canSave = computed(() => {
    if (!selectedType.value) return false;

    switch (selectedType.value) {
        case 'send_email':
            return config.value.subject && config.value.html_content;
        case 'add_tag':
        case 'remove_tag':
            return config.value.tag_id;
        case 'add_to_list':
        case 'remove_from_list':
            return config.value.list_id;
        case 'condition':
            return config.value.condition_type;
        case 'wait':
            return config.value.duration_value > 0;
        case 'webhook':
            return config.value.url;
        default:
            return true;
    }
});
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-secondary-900">
                        {{ step ? 'Modifier l\'étape' : 'Ajouter une étape' }}
                    </h2>
                    <button
                        @click="emit('close')"
                        class="p-2 text-secondary-400 hover:text-secondary-600 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Step type selection (only for new steps) -->
                <div v-if="!step">
                    <h3 class="text-sm font-medium text-secondary-700 mb-3">Type d'étape</h3>

                    <!-- Actions -->
                    <div class="mb-4">
                        <div class="text-xs text-secondary-400 uppercase tracking-wider mb-2">Actions</div>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="stepType in actionSteps"
                                :key="stepType.type"
                                @click="selectedType = stepType.type"
                                :class="[
                                    'flex items-center gap-3 p-3 rounded-lg border-2 text-left transition-colors',
                                    selectedType === stepType.type
                                        ? 'border-primary-500 bg-primary-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <span class="text-xl">{{ getStepIcon(stepType.type) }}</span>
                                <span class="text-sm font-medium text-secondary-900">{{ stepType.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Flow control -->
                    <div>
                        <div class="text-xs text-secondary-400 uppercase tracking-wider mb-2">Contrôle de flux</div>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="stepType in flowSteps"
                                :key="stepType.type"
                                @click="selectedType = stepType.type"
                                :class="[
                                    'flex items-center gap-3 p-3 rounded-lg border-2 text-left transition-colors',
                                    selectedType === stepType.type
                                        ? 'border-primary-500 bg-primary-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <span class="text-xl">{{ getStepIcon(stepType.type) }}</span>
                                <span class="text-sm font-medium text-secondary-900">{{ stepType.label }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step configuration -->
                <div v-if="selectedType" class="space-y-4">
                    <div class="p-4 bg-gray-50 rounded-lg flex items-center gap-3">
                        <span class="text-2xl">{{ getStepIcon(selectedType) }}</span>
                        <div>
                            <div class="font-medium text-secondary-900">{{ stepTypes[selectedType]?.label }}</div>
                            <div class="text-sm text-secondary-500">Configurez les paramètres ci-dessous</div>
                        </div>
                    </div>

                    <!-- Step name -->
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">
                            Nom de l'étape (optionnel)
                        </label>
                        <input
                            v-model="stepName"
                            type="text"
                            placeholder="Ex: Email de bienvenue"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                        />
                    </div>

                    <!-- Send email config -->
                    <template v-if="selectedType === 'send_email'">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Objet de l'email *</label>
                            <input
                                v-model="config.subject"
                                type="text"
                                placeholder="Objet de l'email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Ou choisir un template</label>
                            <select
                                v-model="config.template_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">Créer un email personnalisé</option>
                                <option v-for="template in templates" :key="template.id" :value="template.id">
                                    {{ template.name }}
                                </option>
                            </select>
                        </div>
                        <div v-if="!config.template_id">
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Contenu HTML *</label>
                            <textarea
                                v-model="config.html_content"
                                rows="6"
                                placeholder="<html>...</html>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 font-mono text-sm"
                            />
                        </div>
                    </template>

                    <!-- Wait config -->
                    <template v-if="selectedType === 'wait'">
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-secondary-600">Attendre</span>
                            <input
                                v-model.number="config.duration_value"
                                type="number"
                                min="1"
                                class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            />
                            <select
                                v-model="config.duration_unit"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="minutes">minutes</option>
                                <option value="hours">heures</option>
                                <option value="days">jours</option>
                                <option value="weeks">semaines</option>
                            </select>
                        </div>
                    </template>

                    <!-- Condition config -->
                    <template v-if="selectedType === 'condition'">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Type de condition *</label>
                            <select
                                v-model="config.condition_type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">Sélectionnez une condition</option>
                                <option v-for="(label, type) in conditionTypes" :key="type" :value="type">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div v-if="config.condition_type === 'has_tag'">
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Tag</label>
                            <select
                                v-model="config.tag_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">Sélectionnez un tag</option>
                                <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                                    {{ tag.name }}
                                </option>
                            </select>
                        </div>
                        <div v-if="config.condition_type === 'in_list'">
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Liste</label>
                            <select
                                v-model="config.list_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">Sélectionnez une liste</option>
                                <option v-for="list in lists" :key="list.id" :value="list.id">
                                    {{ list.name }}
                                </option>
                            </select>
                        </div>
                    </template>

                    <!-- Add/Remove tag config -->
                    <template v-if="['add_tag', 'remove_tag'].includes(selectedType)">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Tag *</label>
                            <select
                                v-model="config.tag_id"
                                @change="config.tag_name = tags.find(t => t.id === config.tag_id)?.name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">Sélectionnez un tag</option>
                                <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                                    {{ tag.name }}
                                </option>
                            </select>
                        </div>
                    </template>

                    <!-- Add/Remove list config -->
                    <template v-if="['add_to_list', 'remove_from_list'].includes(selectedType)">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Liste *</label>
                            <select
                                v-model="config.list_id"
                                @change="config.list_name = lists.find(l => l.id === config.list_id)?.name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">Sélectionnez une liste</option>
                                <option v-for="list in lists" :key="list.id" :value="list.id">
                                    {{ list.name }}
                                </option>
                            </select>
                        </div>
                    </template>

                    <!-- Update field config -->
                    <template v-if="selectedType === 'update_field'">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Champ *</label>
                            <input
                                v-model="config.field"
                                type="text"
                                placeholder="Nom du champ"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Nouvelle valeur</label>
                            <input
                                v-model="config.value"
                                type="text"
                                placeholder="Valeur"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                    </template>

                    <!-- Webhook config -->
                    <template v-if="selectedType === 'webhook'">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">URL du webhook *</label>
                            <input
                                v-model="config.url"
                                type="url"
                                placeholder="https://..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Méthode HTTP</label>
                            <select
                                v-model="config.method"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="POST">POST</option>
                                <option value="GET">GET</option>
                                <option value="PUT">PUT</option>
                            </select>
                        </div>
                    </template>

                    <!-- Goal config -->
                    <template v-if="selectedType === 'goal'">
                        <p class="text-sm text-secondary-500">
                            Cette étape marque un objectif atteint. Le contact continuera dans l'automatisation sauf si l'option "Sortir si objectif atteint" est activée.
                        </p>
                    </template>

                    <!-- Exit config -->
                    <template v-if="selectedType === 'exit'">
                        <p class="text-sm text-secondary-500">
                            Cette étape fait sortir le contact de l'automatisation immédiatement.
                        </p>
                    </template>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button
                    @click="emit('close')"
                    class="px-4 py-2 text-secondary-700 hover:text-secondary-900"
                >
                    Annuler
                </button>
                <button
                    @click="save"
                    :disabled="!canSave"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    {{ step ? 'Enregistrer' : 'Ajouter l\'étape' }}
                </button>
            </div>
        </div>
    </div>
</template>

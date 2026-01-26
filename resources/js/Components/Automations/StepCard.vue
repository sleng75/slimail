<script setup>
/**
 * StepCard Component
 * Displays a single step in the workflow builder
 */

const props = defineProps({
    step: Object,
    stepTypes: Object,
    index: Number,
    total: Number,
});

const emit = defineEmits(['edit', 'delete', 'move-up', 'move-down', 'add-yes', 'add-no']);

// Step icons
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

// Step colors
const getStepColorClass = (type) => {
    const colors = {
        send_email: 'border-blue-200 bg-blue-50',
        wait: 'border-gray-200 bg-gray-50',
        condition: 'border-purple-200 bg-purple-50',
        add_tag: 'border-green-200 bg-green-50',
        remove_tag: 'border-red-200 bg-red-50',
        add_to_list: 'border-green-200 bg-green-50',
        remove_from_list: 'border-red-200 bg-red-50',
        update_field: 'border-yellow-200 bg-yellow-50',
        webhook: 'border-indigo-200 bg-indigo-50',
        goal: 'border-emerald-200 bg-emerald-50',
        exit: 'border-red-200 bg-red-50',
    };
    return colors[type] || 'border-gray-200 bg-gray-50';
};

// Get step label
const getStepLabel = () => {
    return props.stepTypes?.[props.step.type]?.label || props.step.type;
};

// Get step description based on config
const getStepDescription = () => {
    const config = props.step.config || {};

    switch (props.step.type) {
        case 'send_email':
            return config.subject || 'Configurer l\'email';
        case 'wait':
            if (config.duration_value && config.duration_unit) {
                const units = { minutes: 'minute(s)', hours: 'heure(s)', days: 'jour(s)', weeks: 'semaine(s)' };
                return `Attendre ${config.duration_value} ${units[config.duration_unit] || config.duration_unit}`;
            }
            return 'Configurer le délai';
        case 'condition':
            return config.condition_type ? 'Condition configurée' : 'Configurer la condition';
        case 'add_tag':
        case 'remove_tag':
            return config.tag_name || 'Sélectionner un tag';
        case 'add_to_list':
        case 'remove_from_list':
            return config.list_name || 'Sélectionner une liste';
        case 'update_field':
            return config.field ? `Champ: ${config.field}` : 'Configurer le champ';
        case 'webhook':
            return config.url || 'Configurer l\'URL';
        case 'goal':
            return props.step.name || 'Objectif atteint';
        case 'exit':
            return 'Sortir de l\'automatisation';
        default:
            return 'Configurer';
    }
};

// Is condition step?
const isCondition = () => props.step.type === 'condition';
</script>

<template>
    <div class="step-card">
        <!-- Main step card -->
        <div
            :class="[
                'relative max-w-md mx-auto rounded-xl border-2 p-4 transition-shadow hover:shadow-md',
                getStepColorClass(step.type)
            ]"
        >
            <!-- Step number badge -->
            <div class="absolute -top-3 -left-3 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-xs font-bold">
                {{ index + 1 }}
            </div>

            <!-- Move buttons -->
            <div class="absolute -right-10 top-1/2 -translate-y-1/2 flex flex-col gap-1">
                <button
                    v-if="index > 0"
                    @click="emit('move-up')"
                    class="w-6 h-6 flex items-center justify-center bg-white border border-gray-200 rounded hover:bg-gray-50 text-secondary-400 hover:text-secondary-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </button>
                <button
                    v-if="index < total - 1"
                    @click="emit('move-down')"
                    class="w-6 h-6 flex items-center justify-center bg-white border border-gray-200 rounded hover:bg-gray-50 text-secondary-400 hover:text-secondary-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <!-- Step content -->
            <div class="flex items-start gap-3">
                <span class="text-2xl">{{ getStepIcon(step.type) }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="font-medium text-secondary-900">
                            {{ step.name || getStepLabel() }}
                        </h4>
                        <div class="flex items-center gap-1">
                            <button
                                @click="emit('edit')"
                                class="p-1 text-secondary-400 hover:text-primary-600 transition-colors"
                                title="Modifier"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                @click="emit('delete')"
                                class="p-1 text-secondary-400 hover:text-red-600 transition-colors"
                                title="Supprimer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-secondary-500 mt-1">{{ getStepDescription() }}</p>

                    <!-- Stats if available -->
                    <div v-if="step.stats && step.stats.entered > 0" class="flex items-center gap-3 mt-2 text-xs text-secondary-400">
                        <span>{{ step.stats.entered }} entrées</span>
                        <span>{{ step.stats.completed }} complétés</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Condition branches -->
        <div v-if="isCondition()" class="mt-4 grid grid-cols-2 gap-8">
            <!-- Yes branch -->
            <div class="space-y-2">
                <div class="flex items-center justify-center gap-2">
                    <div class="w-0.5 h-4 bg-emerald-300"></div>
                </div>
                <div class="text-center">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-xs font-medium">OUI</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-0.5 h-4 bg-emerald-300"></div>
                    <button
                        @click="emit('add-yes')"
                        class="w-8 h-8 flex items-center justify-center border-2 border-dashed border-emerald-300 rounded-full text-emerald-400 hover:border-emerald-500 hover:text-emerald-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>
                <!-- Yes branch steps would be rendered here recursively -->
            </div>

            <!-- No branch -->
            <div class="space-y-2">
                <div class="flex items-center justify-center gap-2">
                    <div class="w-0.5 h-4 bg-red-300"></div>
                </div>
                <div class="text-center">
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">NON</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-0.5 h-4 bg-red-300"></div>
                    <button
                        @click="emit('add-no')"
                        class="w-8 h-8 flex items-center justify-center border-2 border-dashed border-red-300 rounded-full text-red-400 hover:border-red-500 hover:text-red-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>
                <!-- No branch steps would be rendered here recursively -->
            </div>
        </div>
    </div>
</template>

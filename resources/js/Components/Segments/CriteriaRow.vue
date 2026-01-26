<script setup>
/**
 * Criteria Row Component
 * Single criterion row in the segment builder
 */

import { ref, computed, watch } from 'vue';

const props = defineProps({
    criterion: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    availableFields: {
        type: Array,
        required: true,
    },
    operators: {
        type: Array,
        required: true,
    },
    canRemove: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update', 'remove']);

const localField = ref(props.criterion.field || '');
const localOperator = ref(props.criterion.operator || 'equals');
const localValue = ref(props.criterion.value || '');

// Watch for external changes
watch(() => props.criterion, (newVal) => {
    localField.value = newVal.field || '';
    localOperator.value = newVal.operator || 'equals';
    localValue.value = newVal.value || '';
}, { deep: true });

// Emit updates
const emitUpdate = () => {
    emit('update', {
        field: localField.value,
        operator: localOperator.value,
        value: localValue.value,
    });
};

watch([localField, localOperator, localValue], emitUpdate);

// When field changes, reset operator if needed
watch(localField, () => {
    const validOps = props.operators.map(o => o.value);
    if (!validOps.includes(localOperator.value)) {
        localOperator.value = validOps[0] || 'equals';
    }
    // Reset value for some operators
    if (['is_empty', 'is_not_empty'].includes(localOperator.value)) {
        localValue.value = '';
    }
});

// Get selected field config
const selectedField = computed(() => {
    return props.availableFields.find(f => f.value === localField.value);
});

// Check if value input should be shown
const showValueInput = computed(() => {
    return !['is_empty', 'is_not_empty'].includes(localOperator.value);
});

// Get value input type
const valueInputType = computed(() => {
    if (!selectedField.value) return 'text';

    switch (selectedField.value.type) {
        case 'number':
            return 'number';
        case 'date':
            return localOperator.value === 'in_last_days' ? 'number' : 'date';
        default:
            return 'text';
    }
});

// Check if field has options (select, list, tag)
const hasOptions = computed(() => {
    return selectedField.value?.options && ['select', 'list', 'tag'].includes(selectedField.value.type);
});

// Get options for select
const fieldOptions = computed(() => {
    if (!selectedField.value?.options) return [];

    // Handle both array of strings and array of objects
    return selectedField.value.options.map(opt => {
        if (typeof opt === 'string') {
            return { value: opt, label: opt };
        }
        return opt;
    });
});

// Grouped fields for better organization
const groupedFields = computed(() => {
    const groups = {
        'Informations': [],
        'Engagement': [],
        'Dates': [],
        'Listes & Tags': [],
        'Personnalisé': [],
    };

    props.availableFields.forEach(field => {
        if (['email', 'first_name', 'last_name', 'phone', 'company', 'job_title', 'city', 'country', 'postal_code', 'status', 'source'].includes(field.value)) {
            groups['Informations'].push(field);
        } else if (['engagement_score', 'emails_sent', 'emails_opened', 'emails_clicked'].includes(field.value)) {
            groups['Engagement'].push(field);
        } else if (['created_at', 'subscribed_at', 'last_email_sent_at', 'last_email_opened_at', 'last_email_clicked_at'].includes(field.value)) {
            groups['Dates'].push(field);
        } else if (['list', 'tag'].includes(field.value)) {
            groups['Listes & Tags'].push(field);
        } else {
            groups['Personnalisé'].push(field);
        }
    });

    return Object.entries(groups).filter(([_, fields]) => fields.length > 0);
});
</script>

<template>
    <div class="flex items-start gap-3 p-4 bg-white border border-gray-200 rounded-lg">
        <!-- Field Select -->
        <div class="flex-1 min-w-0">
            <label class="block text-xs font-medium text-gray-500 mb-1">Champ</label>
            <select
                v-model="localField"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
            >
                <option value="">Sélectionner un champ</option>
                <optgroup v-for="[group, fields] in groupedFields" :key="group" :label="group">
                    <option v-for="field in fields" :key="field.value" :value="field.value">
                        {{ field.label }}
                    </option>
                </optgroup>
            </select>
        </div>

        <!-- Operator Select -->
        <div class="w-48">
            <label class="block text-xs font-medium text-gray-500 mb-1">Opérateur</label>
            <select
                v-model="localOperator"
                :disabled="!localField"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm disabled:bg-gray-50 disabled:text-gray-400"
            >
                <option v-for="op in operators" :key="op.value" :value="op.value">
                    {{ op.label }}
                </option>
            </select>
        </div>

        <!-- Value Input -->
        <div v-if="showValueInput" class="flex-1 min-w-0">
            <label class="block text-xs font-medium text-gray-500 mb-1">Valeur</label>

            <!-- Select for options -->
            <select
                v-if="hasOptions"
                v-model="localValue"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
            >
                <option value="">Sélectionner</option>
                <option v-for="opt in fieldOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                </option>
            </select>

            <!-- Regular input -->
            <input
                v-else
                v-model="localValue"
                :type="valueInputType"
                :placeholder="valueInputType === 'number' ? '0' : 'Valeur'"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
            />

            <!-- Helper text for in_last_days -->
            <p v-if="localOperator === 'in_last_days'" class="text-xs text-gray-500 mt-1">
                Nombre de jours
            </p>
        </div>

        <!-- Spacer when no value input -->
        <div v-else class="flex-1"></div>

        <!-- Remove Button -->
        <div class="pt-6">
            <button
                v-if="canRemove"
                type="button"
                @click="emit('remove')"
                class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                title="Supprimer ce critère"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</template>

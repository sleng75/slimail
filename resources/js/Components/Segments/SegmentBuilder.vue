<script setup>
/**
 * Segment Builder Component
 * Visual builder for segment criteria
 */

import { ref, computed, watch } from 'vue';
import CriteriaRow from './CriteriaRow.vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    matchType: {
        type: String,
        default: 'all',
    },
    availableFields: {
        type: Array,
        required: true,
    },
    operators: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue', 'update:matchType', 'preview']);

const criteria = ref([...props.modelValue]);
const localMatchType = ref(props.matchType);

// Ensure at least one criterion
if (criteria.value.length === 0) {
    criteria.value.push({ field: '', operator: 'equals', value: '' });
}

// Sync with parent
watch(() => props.modelValue, (newVal) => {
    criteria.value = [...newVal];
}, { deep: true });

watch(criteria, (newVal) => {
    emit('update:modelValue', newVal);
}, { deep: true });

watch(localMatchType, (newVal) => {
    emit('update:matchType', newVal);
});

// Add new criterion
const addCriterion = () => {
    criteria.value.push({ field: '', operator: 'equals', value: '' });
};

// Remove criterion
const removeCriterion = (index) => {
    if (criteria.value.length > 1) {
        criteria.value.splice(index, 1);
    }
};

// Update criterion
const updateCriterion = (index, criterion) => {
    criteria.value[index] = criterion;
};

// Get field config
const getFieldConfig = (fieldValue) => {
    return props.availableFields.find(f => f.value === fieldValue);
};

// Get operators for field
const getOperatorsForField = (fieldValue) => {
    const field = getFieldConfig(fieldValue);
    if (!field) return [];

    const type = field.type || 'string';
    const operatorMap = {
        string: ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'],
        number: ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal'],
        date: ['equals', 'before', 'after', 'in_last_days', 'is_empty', 'is_not_empty'],
        select: ['equals', 'not_equals'],
        list: ['in_list', 'not_in_list'],
        tag: ['has_tag', 'not_has_tag'],
        custom: ['equals', 'not_equals', 'contains', 'not_contains', 'is_empty', 'is_not_empty'],
    };

    const allowedOps = operatorMap[type] || operatorMap.string;
    return allowedOps.map(op => ({
        value: op,
        label: props.operators[op] || op,
    }));
};

// Preview
const triggerPreview = () => {
    emit('preview');
};

// Check if criteria are valid
const isValid = computed(() => {
    return criteria.value.every(c => c.field && c.operator);
});
</script>

<template>
    <div class="segment-builder space-y-4">
        <!-- Match Type -->
        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
            <span class="text-sm font-medium text-gray-700">Correspondance :</span>
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="radio"
                    v-model="localMatchType"
                    value="all"
                    class="text-primary-600 focus:ring-primary-500"
                />
                <span class="text-sm text-gray-600">Tous les critères (ET)</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="radio"
                    v-model="localMatchType"
                    value="any"
                    class="text-primary-600 focus:ring-primary-500"
                />
                <span class="text-sm text-gray-600">Au moins un critère (OU)</span>
            </label>
        </div>

        <!-- Criteria List -->
        <div class="space-y-3">
            <div
                v-for="(criterion, index) in criteria"
                :key="index"
                class="relative"
            >
                <!-- Connector -->
                <div
                    v-if="index > 0"
                    class="flex items-center justify-center mb-2"
                >
                    <span
                        class="px-3 py-1 text-xs font-medium rounded-full"
                        :class="localMatchType === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                    >
                        {{ localMatchType === 'all' ? 'ET' : 'OU' }}
                    </span>
                </div>

                <CriteriaRow
                    :criterion="criterion"
                    :index="index"
                    :availableFields="availableFields"
                    :operators="getOperatorsForField(criterion.field)"
                    :canRemove="criteria.length > 1"
                    @update="updateCriterion(index, $event)"
                    @remove="removeCriterion(index)"
                />
            </div>
        </div>

        <!-- Add Criterion Button -->
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="addCriterion"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-600 border border-primary-300 rounded-lg hover:bg-primary-50 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Ajouter un critère
            </button>

            <button
                type="button"
                @click="triggerPreview"
                :disabled="!isValid"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Prévisualiser
            </button>
        </div>
    </div>
</template>

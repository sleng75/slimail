<script setup>
/**
 * AutomationBuilder Component
 * Visual workflow builder for automations
 */

import { ref, computed, watch } from 'vue';
import StepCard from './StepCard.vue';
import StepConfigModal from './StepConfigModal.vue';

const props = defineProps({
    workflow: {
        type: Array,
        default: () => [],
    },
    stepTypes: Object,
    conditionTypes: Object,
    lists: Array,
    tags: Array,
    templates: Array,
});

const emit = defineEmits(['update']);

const localWorkflow = ref([...props.workflow]);
const showStepModal = ref(false);
const editingStep = ref(null);
const insertPosition = ref(null);
const insertParentId = ref(null);
const insertBranch = ref(null);

// Watch for external changes
watch(() => props.workflow, (newVal) => {
    localWorkflow.value = [...newVal];
}, { deep: true });

// Step type icons
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

// Step type colors
const getStepColor = (type) => {
    const colors = {
        send_email: 'blue',
        wait: 'gray',
        condition: 'purple',
        add_tag: 'green',
        remove_tag: 'red',
        add_to_list: 'green',
        remove_from_list: 'red',
        update_field: 'yellow',
        webhook: 'indigo',
        goal: 'emerald',
        exit: 'red',
    };
    return colors[type] || 'gray';
};

// Add step at position
const addStepAt = (position, parentId = null, branch = null) => {
    insertPosition.value = position;
    insertParentId.value = parentId;
    insertBranch.value = branch;
    editingStep.value = null;
    showStepModal.value = true;
};

// Edit existing step
const editStep = (step) => {
    editingStep.value = { ...step };
    showStepModal.value = true;
};

// Save step (new or edit)
const saveStep = (stepData) => {
    if (editingStep.value?.id) {
        // Update existing step
        updateStepInWorkflow(localWorkflow.value, editingStep.value.id, stepData);
    } else {
        // Add new step
        const newStep = {
            id: `step_${Date.now()}`,
            ...stepData,
            stats: { entered: 0, completed: 0, failed: 0 },
        };

        if (insertParentId.value) {
            // Add to parent's children or branch
            addStepToParent(localWorkflow.value, insertParentId.value, newStep, insertBranch.value);
        } else {
            // Add to root
            localWorkflow.value.splice(insertPosition.value, 0, newStep);
        }
    }

    emit('update', localWorkflow.value);
    closeModal();
};

// Delete step
const deleteStep = (stepId) => {
    if (confirm('Supprimer cette étape ?')) {
        removeStepFromWorkflow(localWorkflow.value, stepId);
        emit('update', localWorkflow.value);
    }
};

// Helper: Update step in workflow tree
const updateStepInWorkflow = (steps, stepId, newData) => {
    for (let i = 0; i < steps.length; i++) {
        if (steps[i].id === stepId) {
            steps[i] = { ...steps[i], ...newData };
            return true;
        }
        if (steps[i].yes_branch && updateStepInWorkflow(steps[i].yes_branch, stepId, newData)) return true;
        if (steps[i].no_branch && updateStepInWorkflow(steps[i].no_branch, stepId, newData)) return true;
        if (steps[i].children && updateStepInWorkflow(steps[i].children, stepId, newData)) return true;
    }
    return false;
};

// Helper: Remove step from workflow tree
const removeStepFromWorkflow = (steps, stepId) => {
    for (let i = 0; i < steps.length; i++) {
        if (steps[i].id === stepId) {
            steps.splice(i, 1);
            return true;
        }
        if (steps[i].yes_branch && removeStepFromWorkflow(steps[i].yes_branch, stepId)) return true;
        if (steps[i].no_branch && removeStepFromWorkflow(steps[i].no_branch, stepId)) return true;
        if (steps[i].children && removeStepFromWorkflow(steps[i].children, stepId)) return true;
    }
    return false;
};

// Helper: Add step to parent
const addStepToParent = (steps, parentId, newStep, branch) => {
    for (let i = 0; i < steps.length; i++) {
        if (steps[i].id === parentId) {
            if (branch === 'yes') {
                steps[i].yes_branch = steps[i].yes_branch || [];
                steps[i].yes_branch.push(newStep);
            } else if (branch === 'no') {
                steps[i].no_branch = steps[i].no_branch || [];
                steps[i].no_branch.push(newStep);
            } else {
                steps[i].children = steps[i].children || [];
                steps[i].children.push(newStep);
            }
            return true;
        }
        if (steps[i].yes_branch && addStepToParent(steps[i].yes_branch, parentId, newStep, branch)) return true;
        if (steps[i].no_branch && addStepToParent(steps[i].no_branch, parentId, newStep, branch)) return true;
        if (steps[i].children && addStepToParent(steps[i].children, parentId, newStep, branch)) return true;
    }
    return false;
};

// Close modal
const closeModal = () => {
    showStepModal.value = false;
    editingStep.value = null;
    insertPosition.value = null;
    insertParentId.value = null;
    insertBranch.value = null;
};

// Move step up/down
const moveStep = (index, direction) => {
    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex >= 0 && newIndex < localWorkflow.value.length) {
        const temp = localWorkflow.value[index];
        localWorkflow.value[index] = localWorkflow.value[newIndex];
        localWorkflow.value[newIndex] = temp;
        emit('update', localWorkflow.value);
    }
};
</script>

<template>
    <div class="automation-builder">
        <!-- Step palette -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <h3 class="text-sm font-medium text-secondary-700 mb-3">Types d'étapes disponibles</h3>
            <div class="flex flex-wrap gap-2">
                <div
                    v-for="(info, type) in stepTypes"
                    :key="type"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg text-sm text-secondary-700 cursor-default"
                >
                    <span>{{ getStepIcon(type) }}</span>
                    <span>{{ info.label }}</span>
                </div>
            </div>
        </div>

        <!-- Workflow canvas -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 min-h-[400px]">
            <!-- Trigger indicator -->
            <div class="flex items-center justify-center mb-6">
                <div class="flex items-center gap-3 px-4 py-2 bg-primary-50 border-2 border-primary-200 rounded-lg">
                    <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="font-medium text-primary-700">Déclencheur</span>
                </div>
            </div>

            <!-- Connector line -->
            <div class="flex justify-center mb-4">
                <div class="w-0.5 h-8 bg-gray-300"></div>
            </div>

            <!-- Add first step button -->
            <div v-if="localWorkflow.length === 0" class="flex justify-center">
                <button
                    @click="addStepAt(0)"
                    class="flex items-center gap-2 px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg text-secondary-500 hover:border-primary-400 hover:text-primary-600 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajouter la première étape
                </button>
            </div>

            <!-- Steps list -->
            <div v-else class="space-y-4">
                <template v-for="(step, index) in localWorkflow" :key="step.id">
                    <!-- Step card -->
                    <StepCard
                        :step="step"
                        :step-types="stepTypes"
                        :index="index"
                        :total="localWorkflow.length"
                        @edit="editStep(step)"
                        @delete="deleteStep(step.id)"
                        @move-up="moveStep(index, 'up')"
                        @move-down="moveStep(index, 'down')"
                        @add-yes="addStepAt(0, step.id, 'yes')"
                        @add-no="addStepAt(0, step.id, 'no')"
                    />

                    <!-- Connector and add button -->
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-0.5 h-4 bg-gray-300"></div>
                        <button
                            @click="addStepAt(index + 1)"
                            class="w-8 h-8 flex items-center justify-center border-2 border-dashed border-gray-300 rounded-full text-secondary-400 hover:border-primary-400 hover:text-primary-600 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                        <div v-if="index < localWorkflow.length - 1" class="w-0.5 h-4 bg-gray-300"></div>
                    </div>
                </template>

                <!-- End indicator -->
                <div class="flex items-center justify-center mt-6">
                    <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 border-2 border-gray-200 rounded-lg">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="font-medium text-gray-500">Fin de l'automatisation</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step configuration modal -->
        <StepConfigModal
            v-if="showStepModal"
            :step="editingStep"
            :step-types="stepTypes"
            :condition-types="conditionTypes"
            :lists="lists"
            :tags="tags"
            :templates="templates"
            @save="saveStep"
            @close="closeModal"
        />
    </div>
</template>

<style scoped>
.automation-builder {
    @apply relative;
}
</style>

<script setup>
/**
 * Automation Edit Page
 * Configure workflow with visual builder
 */

import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';
import AutomationBuilder from '@/Components/Automations/AutomationBuilder.vue';

const props = defineProps({
    automation: Object,
    triggerTypes: Object,
    stepTypes: Object,
    conditionTypes: Object,
    lists: Array,
    tags: Array,
    templates: Array,
});

const activeTab = ref('workflow');
const workflow = ref(props.automation.workflow || []);
const isSaving = ref(false);

// Form for settings
const form = useForm({
    name: props.automation.name,
    description: props.automation.description,
    trigger_type: props.automation.trigger_type,
    trigger_config: props.automation.trigger_config || {},
    allow_reentry: props.automation.allow_reentry,
    reentry_delay_days: props.automation.reentry_delay_days,
    exit_on_goal: props.automation.exit_on_goal,
    goal_config: props.automation.goal_config,
    timezone: props.automation.timezone,
});

// Trigger icons
const triggerIcons = {
    list_subscription: '📋',
    tag_added: '🏷️',
    tag_removed: '🏷️',
    email_opened: '📧',
    link_clicked: '🔗',
    date_field: '📅',
    inactivity: '⏰',
    webhook: '🌐',
    manual: '👆',
};

// Save settings
const saveSettings = () => {
    form.put(route('automations.update', props.automation.id), {
        preserveScroll: true,
    });
};

// Save workflow
const saveWorkflow = () => {
    isSaving.value = true;
    router.post(route('automations.save-workflow', props.automation.id), {
        steps: workflow.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

// Handle workflow changes from builder
const handleWorkflowChange = (newWorkflow) => {
    workflow.value = newWorkflow;
};

// Activate automation
const activateAutomation = () => {
    if (confirm('Activer cette automatisation ?')) {
        router.post(route('automations.activate', props.automation.id));
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="`Modifier: ${automation.name}`" />

        <PageContainer size="wide">
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <Link
                                :href="route('automations.index')"
                                class="text-secondary-400 hover:text-secondary-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                            </Link>
                            <h1 class="text-2xl font-bold text-secondary-900">{{ automation.name }}</h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                Brouillon
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('automations.show', automation.id)"
                            class="px-4 py-2 text-secondary-700 hover:text-secondary-900"
                        >
                            Voir les statistiques
                        </Link>
                        <button
                            v-if="automation.status === 'draft' || automation.status === 'paused'"
                            @click="activateAutomation"
                            :disabled="workflow.length === 0"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Activer
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex gap-6">
                        <button
                            @click="activeTab = 'workflow'"
                            :class="[
                                'pb-3 text-sm font-medium border-b-2 -mb-px transition-colors',
                                activeTab === 'workflow'
                                    ? 'text-primary-600 border-primary-600'
                                    : 'text-secondary-500 border-transparent hover:text-secondary-700'
                            ]"
                        >
                            Workflow
                        </button>
                        <button
                            @click="activeTab = 'settings'"
                            :class="[
                                'pb-3 text-sm font-medium border-b-2 -mb-px transition-colors',
                                activeTab === 'settings'
                                    ? 'text-primary-600 border-primary-600'
                                    : 'text-secondary-500 border-transparent hover:text-secondary-700'
                            ]"
                        >
                            Paramètres
                        </button>
                    </nav>
                </div>

                <!-- Workflow tab -->
                <div v-if="activeTab === 'workflow'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-secondary-500">
                            Glissez et déposez les étapes pour créer votre workflow d'automatisation.
                        </p>
                        <button
                            @click="saveWorkflow"
                            :disabled="isSaving"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors"
                        >
                            <span v-if="isSaving">Sauvegarde...</span>
                            <span v-else>Sauvegarder le workflow</span>
                        </button>
                    </div>

                    <AutomationBuilder
                        :workflow="workflow"
                        :step-types="stepTypes"
                        :condition-types="conditionTypes"
                        :lists="lists"
                        :tags="tags"
                        :templates="templates"
                        @update="handleWorkflowChange"
                    />
                </div>

                <!-- Settings tab -->
                <div v-if="activeTab === 'settings'" class="space-y-6">
                    <form @submit.prevent="saveSettings" class="space-y-6">
                        <!-- Basic info -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                            <h2 class="text-lg font-medium text-secondary-900">Informations de base</h2>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1">
                                    Nom de l'automatisation *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1">
                                    Description
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                        </div>

                        <!-- Trigger -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                            <h2 class="text-lg font-medium text-secondary-900">Déclencheur</h2>

                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                                <span class="text-2xl">{{ triggerIcons[form.trigger_type] }}</span>
                                <div>
                                    <div class="font-medium text-secondary-900">
                                        {{ triggerTypes[form.trigger_type] }}
                                    </div>
                                    <div v-if="form.trigger_config.list_id" class="text-sm text-secondary-500">
                                        Liste: {{ lists.find(l => l.id === form.trigger_config.list_id)?.name }}
                                    </div>
                                    <div v-if="form.trigger_config.tag_id" class="text-sm text-secondary-500">
                                        Tag: {{ tags.find(t => t.id === form.trigger_config.tag_id)?.name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced settings -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                            <h2 class="text-lg font-medium text-secondary-900">Paramètres avancés</h2>

                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-secondary-900">Permettre la réentrée</div>
                                    <div class="text-sm text-secondary-500">
                                        Un contact peut entrer plusieurs fois dans cette automatisation
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        v-model="form.allow_reentry"
                                        type="checkbox"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>

                            <div v-if="form.allow_reentry" class="pl-4 border-l-2 border-gray-200">
                                <label class="block text-sm font-medium text-secondary-700 mb-2">
                                    Délai minimum avant réentrée
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="form.reentry_delay_days"
                                        type="number"
                                        min="1"
                                        max="365"
                                        class="w-20 px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                    <span class="text-sm text-secondary-600">jours</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div>
                                    <div class="font-medium text-secondary-900">Sortir si objectif atteint</div>
                                    <div class="text-sm text-secondary-500">
                                        Les contacts sortent automatiquement quand ils atteignent un objectif
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        v-model="form.exit_on_goal"
                                        type="checkbox"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Save button -->
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors"
                            >
                                <span v-if="form.processing">Sauvegarde...</span>
                                <span v-else>Sauvegarder les paramètres</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

<script setup>
/**
 * Automation Show Page
 * View automation details, stats, and workflow
 */

import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    automation: Object,
    recentEnrollments: Array,
    stepStats: Array,
});

// Status colors
const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-700',
        active: 'bg-emerald-100 text-emerald-700',
        paused: 'bg-amber-100 text-amber-700',
        archived: 'bg-red-100 text-red-700',
    };
    return colors[status] || colors.draft;
};

// Enrollment status colors
const getEnrollmentStatusColor = (status) => {
    const colors = {
        active: 'bg-blue-100 text-blue-700',
        waiting: 'bg-amber-100 text-amber-700',
        completed: 'bg-emerald-100 text-emerald-700',
        exited: 'bg-gray-100 text-gray-700',
        failed: 'bg-red-100 text-red-700',
    };
    return colors[status] || colors.active;
};

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

// Actions
const activateAutomation = () => {
    if (confirm('Activer cette automatisation ?')) {
        router.post(route('automations.activate', props.automation.id));
    }
};

const pauseAutomation = () => {
    if (confirm('Mettre en pause cette automatisation ?')) {
        router.post(route('automations.pause', props.automation.id));
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="automation.name" />

        <PageContainer>
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
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    getStatusColor(automation.status)
                                ]"
                            >
                                {{ automation.status_label }}
                            </span>
                        </div>
                        <p v-if="automation.description" class="text-secondary-500 mt-1">
                            {{ automation.description }}
                        </p>
                        <div class="flex items-center gap-4 mt-2 text-sm text-secondary-400">
                            <span>{{ automation.trigger_label }}</span>
                            <span v-if="automation.activated_at">• Activé le {{ automation.activated_at }}</span>
                            <span v-if="automation.created_by">• Créé par {{ automation.created_by }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="automation.status === 'draft' || automation.status === 'paused'"
                            @click="activateAutomation"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
                        >
                            Activer
                        </button>
                        <button
                            v-if="automation.status === 'active'"
                            @click="pauseAutomation"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors"
                        >
                            Mettre en pause
                        </button>
                        <Link
                            :href="route('automations.edit', automation.id)"
                            class="px-4 py-2 bg-white border border-gray-300 text-secondary-700 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Modifier
                        </Link>
                        <Link
                            :href="route('automations.enrollments', automation.id)"
                            class="px-4 py-2 bg-white border border-gray-300 text-secondary-700 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Voir les inscrits
                        </Link>
                    </div>
                </div>

                <!-- Stats cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="text-3xl font-bold text-secondary-900">{{ automation.total_enrolled }}</div>
                        <div class="text-sm text-secondary-500">Total inscrits</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="text-3xl font-bold text-primary-600">{{ automation.currently_active }}</div>
                        <div class="text-sm text-secondary-500">Actuellement actifs</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="text-3xl font-bold text-emerald-600">{{ automation.completed }}</div>
                        <div class="text-sm text-secondary-500">Terminés</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="text-3xl font-bold text-amber-600">{{ automation.exited }}</div>
                        <div class="text-sm text-secondary-500">Sortis</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="text-3xl font-bold text-violet-600">{{ automation.conversion_rate }}%</div>
                        <div class="text-sm text-secondary-500">Taux de conversion</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Step statistics -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-medium text-secondary-900 mb-4">Performance des étapes</h2>

                        <div v-if="stepStats.length > 0" class="space-y-4">
                            <div
                                v-for="step in stepStats"
                                :key="step.id"
                                class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg"
                            >
                                <span class="text-xl">{{ getStepIcon(step.type) }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-secondary-900 truncate">
                                        {{ step.name || step.type_label }}
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-secondary-500">
                                        <span>{{ step.entered_count }} entrées</span>
                                        <span>{{ step.completed_count }} complétés</span>
                                        <span class="text-emerald-600">{{ step.completion_rate }}%</span>
                                    </div>
                                    <div v-if="step.type === 'send_email'" class="flex items-center gap-4 text-sm text-secondary-500 mt-1">
                                        <span>{{ step.emails_sent }} envoyés</span>
                                        <span class="text-blue-600">{{ step.open_rate }}% ouverture</span>
                                        <span class="text-primary-600">{{ step.click_rate }}% clics</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-secondary-400">
                            Aucune étape configurée
                        </div>
                    </div>

                    <!-- Recent enrollments -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-secondary-900">Inscriptions récentes</h2>
                            <Link
                                :href="route('automations.enrollments', automation.id)"
                                class="text-sm text-primary-600 hover:text-primary-700"
                            >
                                Voir tout
                            </Link>
                        </div>

                        <div v-if="recentEnrollments.length > 0" class="space-y-3">
                            <div
                                v-for="enrollment in recentEnrollments"
                                :key="enrollment.id"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div>
                                    <div class="font-medium text-secondary-900">
                                        {{ enrollment.contact.name || enrollment.contact.email }}
                                    </div>
                                    <div class="text-sm text-secondary-500">
                                        {{ enrollment.current_step ? enrollment.current_step.name : 'Terminé' }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-xs font-medium',
                                            getEnrollmentStatusColor(enrollment.status)
                                        ]"
                                    >
                                        {{ enrollment.status_label }}
                                    </span>
                                    <div class="text-xs text-secondary-400 mt-1">
                                        {{ enrollment.duration }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-secondary-400">
                            Aucune inscription récente
                        </div>
                    </div>
                </div>

                <!-- Workflow visualization placeholder -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-medium text-secondary-900 mb-4">Workflow</h2>

                    <div v-if="automation.workflow && automation.workflow.length > 0" class="space-y-2">
                        <div
                            v-for="(step, index) in automation.workflow"
                            :key="step.id"
                            class="flex items-center gap-4"
                        >
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-medium text-sm">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1 flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <span class="text-xl">{{ getStepIcon(step.type) }}</span>
                                <div>
                                    <div class="font-medium text-secondary-900">
                                        {{ step.name || step.type }}
                                    </div>
                                    <div class="text-sm text-secondary-500">
                                        {{ step.stats.completed }}/{{ step.stats.entered }} complétés
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <p class="text-secondary-400 mb-4">Aucune étape configurée</p>
                        <Link
                            :href="route('automations.edit', automation.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                        >
                            Configurer le workflow
                        </Link>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

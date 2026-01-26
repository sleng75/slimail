<script setup>
/**
 * Create Automation Page
 * First step: Configure trigger and basic settings
 */

import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    triggerTypes: Object,
    lists: Array,
    tags: Array,
});

const form = useForm({
    name: '',
    description: '',
    trigger_type: '',
    trigger_config: {},
    allow_reentry: false,
    reentry_delay_days: null,
    exit_on_goal: false,
    goal_config: null,
    timezone: 'Africa/Abidjan',
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

// Trigger descriptions
const triggerDescriptions = {
    list_subscription: 'Déclenché lorsqu\'un contact s\'inscrit à une liste spécifique.',
    tag_added: 'Déclenché lorsqu\'un tag est ajouté à un contact.',
    tag_removed: 'Déclenché lorsqu\'un tag est retiré d\'un contact.',
    email_opened: 'Déclenché lorsqu\'un contact ouvre un email.',
    link_clicked: 'Déclenché lorsqu\'un contact clique sur un lien.',
    date_field: 'Déclenché à une date spécifique (anniversaire, etc.).',
    inactivity: 'Déclenché après une période d\'inactivité.',
    webhook: 'Déclenché par un appel webhook externe.',
    manual: 'Contacts ajoutés manuellement à l\'automatisation.',
};

// Selected trigger needs config?
const needsListConfig = computed(() => form.trigger_type === 'list_subscription');
const needsTagConfig = computed(() => ['tag_added', 'tag_removed'].includes(form.trigger_type));
const needsDateConfig = computed(() => form.trigger_type === 'date_field');
const needsInactivityConfig = computed(() => form.trigger_type === 'inactivity');

// Reset config when trigger changes
watch(() => form.trigger_type, () => {
    form.trigger_config = {};
});

const submit = () => {
    form.post(route('automations.store'));
};
</script>

<template>
    <AppLayout>
        <Head title="Nouvelle automatisation" />

        <PageContainer size="narrow">
            <div class="space-y-6">
                <!-- Header -->
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900">Nouvelle automatisation</h1>
                    <p class="text-secondary-500 mt-1">Configurez le déclencheur et les paramètres de base</p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
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
                                placeholder="Ex: Bienvenue aux nouveaux inscrits"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">
                                Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Décrivez l'objectif de cette automatisation..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>

                    <!-- Trigger selection -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                        <h2 class="text-lg font-medium text-secondary-900">Déclencheur</h2>
                        <p class="text-sm text-secondary-500">Qu'est-ce qui déclenche cette automatisation ?</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <button
                                v-for="(label, type) in triggerTypes"
                                :key="type"
                                type="button"
                                @click="form.trigger_type = type"
                                :class="[
                                    'flex items-start gap-3 p-4 rounded-lg border-2 text-left transition-colors',
                                    form.trigger_type === type
                                        ? 'border-primary-500 bg-primary-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <span class="text-2xl">{{ triggerIcons[type] }}</span>
                                <div>
                                    <div class="font-medium text-secondary-900">{{ label }}</div>
                                    <div class="text-sm text-secondary-500">{{ triggerDescriptions[type] }}</div>
                                </div>
                            </button>
                        </div>
                        <p v-if="form.errors.trigger_type" class="text-sm text-red-600">{{ form.errors.trigger_type }}</p>

                        <!-- Trigger config: List -->
                        <div v-if="needsListConfig" class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-secondary-700 mb-2">
                                Liste à surveiller *
                            </label>
                            <select
                                v-model="form.trigger_config.list_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">Sélectionnez une liste</option>
                                <option v-for="list in lists" :key="list.id" :value="list.id">
                                    {{ list.name }} ({{ list.contact_count }} contacts)
                                </option>
                            </select>
                        </div>

                        <!-- Trigger config: Tag -->
                        <div v-if="needsTagConfig" class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-secondary-700 mb-2">
                                Tag à surveiller *
                            </label>
                            <select
                                v-model="form.trigger_config.tag_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">Sélectionnez un tag</option>
                                <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                                    {{ tag.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Trigger config: Date field -->
                        <div v-if="needsDateConfig" class="mt-4 p-4 bg-gray-50 rounded-lg space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-2">
                                    Champ date *
                                </label>
                                <select
                                    v-model="form.trigger_config.date_field"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                                >
                                    <option value="">Sélectionnez un champ</option>
                                    <option value="birthday">Date d'anniversaire</option>
                                    <option value="created_at">Date d'inscription</option>
                                    <option value="custom">Champ personnalisé</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="text-sm font-medium text-secondary-700">Déclencher</label>
                                <input
                                    v-model="form.trigger_config.days_before"
                                    type="number"
                                    min="0"
                                    max="30"
                                    class="w-20 px-3 py-2 border border-gray-300 rounded-lg"
                                />
                                <span class="text-sm text-secondary-600">jours avant la date</span>
                            </div>
                        </div>

                        <!-- Trigger config: Inactivity -->
                        <div v-if="needsInactivityConfig" class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-secondary-700 mb-2">
                                Période d'inactivité
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-secondary-600">Après</span>
                                <input
                                    v-model="form.trigger_config.inactivity_days"
                                    type="number"
                                    min="1"
                                    max="365"
                                    class="w-20 px-3 py-2 border border-gray-300 rounded-lg"
                                />
                                <span class="text-sm text-secondary-600">jours sans activité</span>
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

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4">
                        <button
                            type="button"
                            @click="$inertia.visit(route('automations.index'))"
                            class="px-4 py-2 text-secondary-700 hover:text-secondary-900"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.name || !form.trigger_type"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span v-if="form.processing">Création...</span>
                            <span v-else>Créer et configurer les étapes</span>
                        </button>
                    </div>
                </form>
            </div>
        </PageContainer>
    </AppLayout>
</template>

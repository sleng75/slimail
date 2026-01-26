<script setup>
import { ref, onMounted, onBeforeUnmount, defineAsyncComponent } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

// Lazy load the heavy EmailEditor component (contains GrapesJS)
const EmailEditor = defineAsyncComponent({
    loader: () => import('@/Components/EmailEditor/EmailEditor.vue'),
    loadingComponent: {
        template: `
            <div class="flex items-center justify-center h-full bg-gray-50">
                <div class="text-center">
                    <div class="w-12 h-12 border-4 border-gray-200 border-t-indigo-600 rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-gray-600">Chargement de l'éditeur...</p>
                </div>
            </div>
        `
    },
    delay: 100,
});

const props = defineProps({
    template: Object,
    categories: Object,
});

const emailEditor = ref(null);
const showSettingsPanel = ref(false);
const lastSaved = ref(props.template.updated_at);
const isSaving = ref(false);
const hasUnsavedChanges = ref(false);

const form = useForm({
    name: props.template.name,
    description: props.template.description || '',
    category: props.template.category || '',
    default_subject: props.template.default_subject || '',
    default_from_name: props.template.default_from_name || '',
    default_from_email: props.template.default_from_email || '',
    is_active: props.template.is_active,
    html_content: props.template.html_content || '',
    design_json: props.template.design_json || null,
});

// Autosave functionality
const autosave = debounce(() => {
    if (!emailEditor.value || isSaving.value) return;

    const content = emailEditor.value.getContent();
    if (!content.html) return;

    isSaving.value = true;

    fetch(`/templates/${props.template.id}/autosave`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            html_content: content.html,
            design_json: content.mjml,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            lastSaved.value = data.saved_at;
            hasUnsavedChanges.value = false;
        }
    })
    .catch(error => {
        console.error('Autosave failed:', error);
    })
    .finally(() => {
        isSaving.value = false;
    });
}, 3000);

const handleContentUpdate = () => {
    hasUnsavedChanges.value = true;
    autosave();
};

const saveTemplate = () => {
    if (!emailEditor.value) return;

    const content = emailEditor.value.getContent();

    form.html_content = content.html;
    form.design_json = content.mjml;

    form.put(`/templates/${props.template.id}`, {
        onSuccess: () => {
            hasUnsavedChanges.value = false;
            lastSaved.value = new Date().toISOString();
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Warn before leaving with unsaved changes
onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
});

const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};
</script>

<template>
    <AppLayout :fullWidth="true">
        <Head :title="`Éditer: ${template.name}`" />

        <div class="h-screen flex flex-col bg-gray-100">
            <!-- Top Toolbar -->
            <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-4">
                    <a
                        href="/templates"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg"
                        title="Retour aux templates"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>

                    <div class="h-6 w-px bg-gray-300"></div>

                    <div>
                        <h1 class="text-lg font-semibold text-gray-900">{{ template.name }}</h1>
                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                            <span v-if="isSaving" class="flex items-center text-yellow-600">
                                <svg class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Enregistrement...
                            </span>
                            <span v-else-if="hasUnsavedChanges" class="text-yellow-600">
                                Modifications non sauvegardées
                            </span>
                            <span v-else class="text-green-600">
                                Sauvegardé {{ formatDate(lastSaved) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <button
                        @click="showSettingsPanel = !showSettingsPanel"
                        :class="[
                            'p-2 rounded-lg transition-colors',
                            showSettingsPanel ? 'bg-primary-100 text-primary-700' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'
                        ]"
                        title="Paramètres du template"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>

                    <button
                        @click="saveTemplate"
                        :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Sauvegarder
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex overflow-hidden">
                <!-- Email Editor -->
                <div class="flex-1 overflow-hidden">
                    <EmailEditor
                        ref="emailEditor"
                        :initial-content="template.html_content"
                        :initial-mjml="template.design_json"
                        @update:content="handleContentUpdate"
                    />
                </div>

                <!-- Settings Panel (Slide-over) -->
                <transition
                    enter-active-class="transition-transform duration-300 ease-out"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition-transform duration-200 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <div
                        v-if="showSettingsPanel"
                        class="w-80 bg-white border-l border-gray-200 overflow-y-auto flex-shrink-0"
                    >
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">Paramètres</h2>
                                <button
                                    @click="showSettingsPanel = false"
                                    class="p-1 text-gray-400 hover:text-gray-600 rounded"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-4 space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="2"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                ></textarea>
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                                <select
                                    v-model="form.category"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option value="">Sans catégorie</option>
                                    <option v-for="(label, value) in categories" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <hr class="border-gray-200" />

                            <h3 class="text-sm font-semibold text-gray-900">Valeurs par défaut</h3>

                            <!-- Default Subject -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Objet</label>
                                <input
                                    v-model="form.default_subject"
                                    type="text"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="Objet de l'email"
                                />
                            </div>

                            <!-- Default From Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'expéditeur</label>
                                <input
                                    v-model="form.default_from_name"
                                    type="text"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="Ex: Mon Entreprise"
                                />
                            </div>

                            <!-- Default From Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email de l'expéditeur</label>
                                <input
                                    v-model="form.default_from_email"
                                    type="email"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="Ex: contact@monentreprise.com"
                                />
                            </div>

                            <hr class="border-gray-200" />

                            <!-- Active Status -->
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium text-gray-700">Template actif</label>
                                <button
                                    @click="form.is_active = !form.is_active"
                                    :class="[
                                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                        form.is_active ? 'bg-primary-600' : 'bg-gray-200'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                            form.is_active ? 'translate-x-6' : 'translate-x-1'
                                        ]"
                                    ></span>
                                </button>
                            </div>

                            <!-- Info -->
                            <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-500">
                                <p><strong>Créé par:</strong> {{ template.creator?.name || 'Système' }}</p>
                                <p><strong>Créé le:</strong> {{ formatDate(template.created_at) }}</p>
                                <p><strong>Utilisations:</strong> {{ template.usage_count || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </AppLayout>
</template>

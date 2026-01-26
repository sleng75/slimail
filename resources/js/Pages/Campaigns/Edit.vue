<script setup>
import { ref, computed, watch, defineAsyncComponent } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ABTestCreator from '@/Components/Campaigns/ABTestCreator.vue';

// Lazy load the email editor
const EmailEditor = defineAsyncComponent({
    loader: () => import('@/Components/EmailEditor/EmailEditor.vue'),
    loadingComponent: {
        template: `
            <div class="flex items-center justify-center h-96 bg-gray-50 rounded-lg">
                <div class="text-center">
                    <div class="w-10 h-10 border-4 border-gray-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-3"></div>
                    <p class="text-gray-600 text-sm">Chargement de l'éditeur...</p>
                </div>
            </div>
        `
    },
    delay: 100,
});

const props = defineProps({
    campaign: Object,
    lists: Array,
    templates: Array,
    defaultFromName: String,
    defaultFromEmail: String,
    step: Number,
    variants: {
        type: Array,
        default: () => []
    },
});

const currentStep = ref(props.step || 1);
const emailEditor = ref(null);
const showTestEmailModal = ref(false);
const testEmail = ref('');
const showScheduleModal = ref(false);
const scheduleDate = ref('');
const scheduleTime = ref('');

const steps = [
    { id: 1, name: 'Configuration', description: 'Nom, objet et expéditeur' },
    { id: 2, name: 'Destinataires', description: 'Listes de contacts' },
    { id: 3, name: 'Contenu', description: 'Design de l\'email' },
    { id: 4, name: 'Test A/B', description: 'Optimiser votre campagne' },
    { id: 5, name: 'Révision', description: 'Vérifier et envoyer' },
];

// A/B Test config
const abTestConfig = ref({
    enabled: props.campaign.type === 'ab_test',
    test_type: props.campaign.ab_test_config?.test_type || 'subject',
    test_percentage: props.campaign.ab_test_config?.test_percentage || 20,
    winning_criteria: props.campaign.ab_test_config?.winning_criteria || 'opens',
    wait_hours: props.campaign.ab_test_config?.wait_hours || 4,
    auto_send_winner: props.campaign.ab_test_config?.auto_send_winner !== false,
    variants: []
});

// Step 1: Configuration form
const configForm = useForm({
    name: props.campaign.name || '',
    subject: props.campaign.subject || '',
    preview_text: props.campaign.preview_text || '',
    from_name: props.campaign.from_name || props.defaultFromName || '',
    from_email: props.campaign.from_email || props.defaultFromEmail || '',
    reply_to: props.campaign.reply_to || '',
});

// Step 2: Recipients form
const recipientsForm = useForm({
    list_ids: props.campaign.list_ids || [],
    excluded_list_ids: props.campaign.excluded_list_ids || [],
});

// Step 3: Content form
const contentForm = useForm({
    template_id: props.campaign.template_id || null,
    html_content: props.campaign.html_content || '',
});

const selectedTemplate = ref(null);

// Computed
const totalRecipients = computed(() => {
    return props.campaign.recipients_count || 0;
});

const selectedLists = computed(() => {
    return props.lists.filter(list => recipientsForm.list_ids.includes(list.id));
});

const isReadyToSend = computed(() => {
    return configForm.name
        && configForm.subject
        && configForm.from_name
        && configForm.from_email
        && recipientsForm.list_ids.length > 0
        && (contentForm.html_content || contentForm.template_id);
});

const stepProgress = computed(() => {
    let completed = 0;
    if (configForm.name && configForm.subject && configForm.from_email) completed++;
    if (recipientsForm.list_ids.length > 0) completed++;
    if (contentForm.html_content || contentForm.template_id) completed++;
    return completed;
});

// Methods
const goToStep = (step) => {
    if (step < currentStep.value || stepProgress.value >= step - 1) {
        currentStep.value = step;
        router.get(`/campaigns/${props.campaign.id}/edit`, { step }, {
            preserveState: true,
            replace: true,
        });
    }
};

const saveConfig = () => {
    configForm.put(`/campaigns/${props.campaign.id}/config`, {
        onSuccess: () => {
            currentStep.value = 2;
        },
    });
};

const saveRecipients = () => {
    recipientsForm.put(`/campaigns/${props.campaign.id}/recipients`, {
        onSuccess: () => {
            currentStep.value = 3;
        },
    });
};

const saveContent = () => {
    // Get content from editor if available
    if (emailEditor.value) {
        const content = emailEditor.value.getContent();
        contentForm.html_content = content.html;
    }

    contentForm.put(`/campaigns/${props.campaign.id}/content`, {
        onSuccess: () => {
            currentStep.value = 4; // Go to A/B Test step
        },
    });
};

const saveAbTest = () => {
    router.put(`/campaigns/${props.campaign.id}/ab-test`, {
        enabled: abTestConfig.value.enabled,
        config: {
            test_type: abTestConfig.value.test_type,
            test_percentage: abTestConfig.value.test_percentage,
            winning_criteria: abTestConfig.value.winning_criteria,
            wait_hours: abTestConfig.value.wait_hours,
            auto_send_winner: abTestConfig.value.auto_send_winner,
        },
        variants: abTestConfig.value.variants,
    }, {
        onSuccess: () => {
            currentStep.value = 5; // Go to Review step
        },
    });
};

const skipAbTest = () => {
    // Disable A/B test and go directly to review
    abTestConfig.value.enabled = false;
    router.put(`/campaigns/${props.campaign.id}/ab-test`, {
        enabled: false,
    }, {
        onSuccess: () => {
            currentStep.value = 5;
        },
    });
};

const selectTemplate = (template) => {
    selectedTemplate.value = template;
    contentForm.template_id = template.id;
    contentForm.html_content = template.html_content;

    // Also prefill subject if empty
    if (!configForm.subject && template.default_subject) {
        configForm.subject = template.default_subject;
    }
};

const clearTemplate = () => {
    selectedTemplate.value = null;
    contentForm.template_id = null;
};

const handleEditorUpdate = () => {
    if (emailEditor.value) {
        const content = emailEditor.value.getContent();
        contentForm.html_content = content.html;
    }
};

const sendTestEmail = () => {
    if (!testEmail.value) return;

    router.post(`/campaigns/${props.campaign.id}/send-test`, {
        email: testEmail.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showTestEmailModal.value = false;
            testEmail.value = '';
        },
    });
};

const scheduleCampaign = () => {
    if (!scheduleDate.value || !scheduleTime.value) return;

    router.post(`/campaigns/${props.campaign.id}/schedule`, {
        scheduled_at: `${scheduleDate.value} ${scheduleTime.value}`,
        timezone: 'Africa/Abidjan',
    });
};

const sendNow = () => {
    if (confirm('Voulez-vous vraiment envoyer cette campagne maintenant ?')) {
        router.post(`/campaigns/${props.campaign.id}/send`);
    }
};

const toggleListSelection = (listId) => {
    const index = recipientsForm.list_ids.indexOf(listId);
    if (index === -1) {
        recipientsForm.list_ids.push(listId);
    } else {
        recipientsForm.list_ids.splice(index, 1);
    }
};

const formatNumber = (num) => {
    return (num || 0).toLocaleString('fr-FR');
};
</script>

<template>
    <AppLayout :fullWidth="currentStep === 3">
        <Head :title="`Campagne: ${campaign.name}`" />

        <div class="min-h-screen bg-gray-50">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center">
                            <Link href="/campaigns" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                            </Link>
                            <div>
                                <h1 class="text-lg font-semibold text-gray-900">{{ campaign.name }}</h1>
                                <p class="text-sm text-gray-500">Brouillon</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                @click="showTestEmailModal = true"
                                :disabled="!contentForm.html_content"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                            >
                                Envoyer un test
                            </button>
                            <button
                                v-if="isReadyToSend"
                                @click="showScheduleModal = true"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                            >
                                Programmer l'envoi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Steps Navigation -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-4">
                    <nav class="flex space-x-4">
                        <button
                            v-for="step in steps"
                            :key="step.id"
                            @click="goToStep(step.id)"
                            :class="[
                                'flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                                currentStep === step.id
                                    ? 'bg-primary-100 text-primary-800'
                                    : step.id < currentStep || stepProgress >= step.id - 1
                                        ? 'text-gray-700 hover:bg-gray-100 cursor-pointer'
                                        : 'text-gray-400 cursor-not-allowed'
                            ]"
                        >
                            <span :class="[
                                'flex items-center justify-center w-6 h-6 mr-2 rounded-full text-xs font-semibold',
                                currentStep === step.id
                                    ? 'bg-primary-600 text-white'
                                    : step.id <= stepProgress
                                        ? 'bg-green-500 text-white'
                                        : 'bg-gray-200 text-gray-600'
                            ]">
                                <svg v-if="step.id <= stepProgress && step.id !== currentStep" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else>{{ step.id }}</span>
                            </span>
                            {{ step.name }}
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Step Content -->
            <div :class="currentStep === 3 ? '' : 'max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8'">
                <!-- Step 1: Configuration -->
                <div v-if="currentStep === 1" class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Configuration de la campagne</h2>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la campagne *</label>
                                <input
                                    v-model="configForm.name"
                                    type="text"
                                    placeholder="Ex: Newsletter Janvier 2026"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                                <p class="mt-1 text-sm text-gray-500">Usage interne uniquement, non visible par vos destinataires</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Objet de l'email *</label>
                                <input
                                    v-model="configForm.subject"
                                    type="text"
                                    placeholder="Ex: Découvrez nos offres du mois !"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Texte de prévisualisation</label>
                                <input
                                    v-model="configForm.preview_text"
                                    type="text"
                                    placeholder="Texte affiché après l'objet dans certaines messageries"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>

                            <hr class="border-gray-200" />

                            <h3 class="text-md font-semibold text-gray-900">Expéditeur</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'expéditeur *</label>
                                    <input
                                        v-model="configForm.from_name"
                                        type="text"
                                        placeholder="Ex: Mon Entreprise"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email de l'expéditeur *</label>
                                    <input
                                        v-model="configForm.from_email"
                                        type="email"
                                        placeholder="Ex: newsletter@monentreprise.com"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email de réponse (optionnel)</label>
                                <input
                                    v-model="configForm.reply_to"
                                    type="email"
                                    placeholder="Ex: contact@monentreprise.com"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button
                                @click="saveConfig"
                                :disabled="configForm.processing"
                                class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                            >
                                Continuer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Recipients -->
                <div v-if="currentStep === 2" class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Sélectionnez vos destinataires</h2>
                        <p class="text-sm text-gray-500 mb-6">Choisissez les listes de contacts qui recevront cette campagne.</p>

                        <div class="space-y-3">
                            <label
                                v-for="list in lists"
                                :key="list.id"
                                :class="[
                                    'flex items-center justify-between p-4 border-2 rounded-xl cursor-pointer transition-colors',
                                    recipientsForm.list_ids.includes(list.id)
                                        ? 'border-primary-500 bg-primary-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        :checked="recipientsForm.list_ids.includes(list.id)"
                                        @change="toggleListSelection(list.id)"
                                        class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    />
                                    <div class="ml-4">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="w-3 h-3 rounded-full"
                                                :style="{ backgroundColor: list.color || '#6366f1' }"
                                            ></span>
                                            <span class="font-medium text-gray-900">{{ list.name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500">{{ formatNumber(list.contacts_count) }} contacts</span>
                            </label>
                        </div>

                        <!-- Summary -->
                        <div v-if="recipientsForm.list_ids.length > 0" class="mt-6 p-4 bg-primary-50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-medium text-primary-900">
                                        {{ recipientsForm.list_ids.length }} liste(s) sélectionnée(s)
                                    </span>
                                </div>
                                <div class="text-lg font-bold text-primary-700">
                                    ~{{ formatNumber(selectedLists.reduce((sum, l) => sum + l.contacts_count, 0)) }} destinataires
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button
                                @click="goToStep(1)"
                                class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Retour
                            </button>
                            <button
                                @click="saveRecipients"
                                :disabled="recipientsForm.processing || recipientsForm.list_ids.length === 0"
                                class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                            >
                                Continuer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Content -->
                <div v-if="currentStep === 3" class="h-[calc(100vh-180px)]">
                    <div class="h-full flex flex-col">
                        <!-- Template selector (if no content yet) -->
                        <div v-if="!contentForm.html_content && !selectedTemplate" class="flex-1 flex items-center justify-center p-8">
                            <div class="max-w-4xl w-full">
                                <h2 class="text-xl font-semibold text-gray-900 text-center mb-2">Choisissez un template</h2>
                                <p class="text-sm text-gray-500 text-center mb-8">Sélectionnez un template existant ou partez de zéro</p>

                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <!-- Start from scratch -->
                                    <button
                                        @click="selectedTemplate = { id: null }; contentForm.template_id = null"
                                        class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition-colors aspect-[3/4]"
                                    >
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span class="mt-3 text-sm font-medium text-gray-700">Créer de zéro</span>
                                    </button>

                                    <!-- Templates -->
                                    <button
                                        v-for="template in templates"
                                        :key="template.id"
                                        @click="selectTemplate(template)"
                                        class="relative group border-2 border-gray-200 rounded-xl hover:border-primary-500 transition-colors overflow-hidden aspect-[3/4]"
                                    >
                                        <div class="absolute inset-0 bg-gray-100 flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="absolute inset-x-0 bottom-0 bg-white/90 p-3">
                                            <span class="text-sm font-medium text-gray-900 line-clamp-1">{{ template.name }}</span>
                                            <span v-if="template.category" class="text-xs text-gray-500">{{ template.category }}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Email Editor -->
                        <div v-else class="flex-1 flex flex-col">
                            <div class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between">
                                <button
                                    @click="clearTemplate"
                                    class="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    ← Changer de template
                                </button>
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="goToStep(2)"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                                    >
                                        Retour
                                    </button>
                                    <button
                                        @click="saveContent"
                                        :disabled="contentForm.processing"
                                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                                    >
                                        Sauvegarder et continuer
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1">
                                <EmailEditor
                                    ref="emailEditor"
                                    :initial-content="contentForm.html_content"
                                    @update:content="handleEditorUpdate"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: A/B Testing -->
                <div v-if="currentStep === 4" class="space-y-6">
                    <ABTestCreator
                        :campaign="campaign"
                        :variants="variants"
                        v-model="abTestConfig"
                    />

                    <div class="flex items-center justify-between">
                        <button
                            @click="goToStep(3)"
                            class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Retour
                        </button>
                        <div class="flex items-center gap-3">
                            <button
                                v-if="!abTestConfig.enabled"
                                @click="skipAbTest"
                                class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Passer cette étape
                            </button>
                            <button
                                @click="saveAbTest"
                                class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                            >
                                {{ abTestConfig.enabled ? 'Sauvegarder le test A/B' : 'Continuer' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Review -->
                <div v-if="currentStep === 5" class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Vérifiez votre campagne</h2>

                        <!-- Configuration Summary -->
                        <div class="space-y-6">
                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-xl">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Configuration</h3>
                                    <div class="mt-2 space-y-1 text-sm text-gray-600">
                                        <p><span class="font-medium">Nom:</span> {{ campaign.name }}</p>
                                        <p><span class="font-medium">Objet:</span> {{ campaign.subject }}</p>
                                        <p><span class="font-medium">Expéditeur:</span> {{ campaign.from_name }} &lt;{{ campaign.from_email }}&gt;</p>
                                    </div>
                                </div>
                                <button @click="goToStep(1)" class="text-sm text-primary-600 hover:text-primary-800">
                                    Modifier
                                </button>
                            </div>

                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-xl">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Destinataires</h3>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <p><span class="font-medium">{{ formatNumber(campaign.recipients_count) }}</span> contacts ciblés</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ campaign.list_ids?.length || 0 }} liste(s) sélectionnée(s)
                                        </p>
                                    </div>
                                </div>
                                <button @click="goToStep(2)" class="text-sm text-primary-600 hover:text-primary-800">
                                    Modifier
                                </button>
                            </div>

                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-xl">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Contenu</h3>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <p v-if="campaign.template_id">Template utilisé</p>
                                        <p v-else>Contenu personnalisé</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <a
                                        :href="`/campaigns/${campaign.id}/preview`"
                                        target="_blank"
                                        class="text-sm text-primary-600 hover:text-primary-800"
                                    >
                                        Prévisualiser
                                    </a>
                                    <button @click="goToStep(3)" class="text-sm text-primary-600 hover:text-primary-800">
                                        Modifier
                                    </button>
                                </div>
                            </div>

                            <!-- A/B Test Summary -->
                            <div v-if="abTestConfig.enabled" class="flex items-start justify-between p-4 bg-purple-50 rounded-xl">
                                <div>
                                    <h3 class="text-sm font-semibold text-purple-900">Test A/B</h3>
                                    <div class="mt-2 text-sm text-purple-700">
                                        <p>Test sur: <span class="font-medium">{{ abTestConfig.test_type === 'subject' ? 'Objet' : abTestConfig.test_type === 'from_name' ? 'Expéditeur' : 'Contenu' }}</span></p>
                                        <p>{{ abTestConfig.test_percentage }}% en test, {{ 100 - abTestConfig.test_percentage }}% au gagnant</p>
                                        <p class="text-xs text-purple-600 mt-1">
                                            Critère: {{ abTestConfig.winning_criteria === 'clicks' ? 'Taux de clic' : 'Taux d\'ouverture' }}
                                            &bull; Durée: {{ abTestConfig.wait_hours }}h
                                        </p>
                                    </div>
                                </div>
                                <button @click="goToStep(4)" class="text-sm text-purple-600 hover:text-purple-800">
                                    Modifier
                                </button>
                            </div>
                        </div>

                        <!-- Validation Checks -->
                        <div class="mt-8">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Vérifications</h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 text-sm">
                                    <svg v-if="campaign.subject" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span :class="campaign.subject ? 'text-gray-700' : 'text-red-600'">Objet de l'email défini</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <svg v-if="campaign.from_email" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span :class="campaign.from_email ? 'text-gray-700' : 'text-red-600'">Expéditeur configuré</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <svg v-if="campaign.recipients_count > 0" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span :class="campaign.recipients_count > 0 ? 'text-gray-700' : 'text-red-600'">Destinataires sélectionnés</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <svg v-if="campaign.html_content" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span :class="campaign.html_content ? 'text-gray-700' : 'text-red-600'">Contenu de l'email créé</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 flex items-center justify-between pt-6 border-t border-gray-200">
                            <button
                                @click="goToStep(4)"
                                class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Retour
                            </button>
                            <div class="flex items-center gap-3">
                                <button
                                    @click="showTestEmailModal = true"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    Envoyer un test
                                </button>
                                <button
                                    @click="showScheduleModal = true"
                                    :disabled="!isReadyToSend"
                                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                >
                                    Programmer
                                </button>
                                <button
                                    @click="sendNow"
                                    :disabled="!isReadyToSend"
                                    class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                                >
                                    Envoyer maintenant
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Email Modal -->
        <div v-if="showTestEmailModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showTestEmailModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Envoyer un email test</h3>
                    <p class="mt-1 text-sm text-gray-500">Testez votre campagne avant de l'envoyer à vos contacts.</p>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                        <input
                            v-model="testEmail"
                            type="email"
                            placeholder="votre@email.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            @keyup.enter="sendTestEmail"
                        />
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="showTestEmailModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="sendTestEmail"
                            :disabled="!testEmail"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            Envoyer le test
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Modal -->
        <div v-if="showScheduleModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showScheduleModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Programmer l'envoi</h3>
                    <p class="mt-1 text-sm text-gray-500">Choisissez la date et l'heure d'envoi de votre campagne.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input
                                v-model="scheduleDate"
                                type="date"
                                :min="new Date().toISOString().split('T')[0]"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Heure</label>
                            <input
                                v-model="scheduleTime"
                                type="time"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <p class="text-xs text-gray-500">Fuseau horaire: Africa/Abidjan (GMT)</p>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="showScheduleModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="scheduleCampaign"
                            :disabled="!scheduleDate || !scheduleTime"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            Programmer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

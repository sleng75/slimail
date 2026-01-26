<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    lists: Array,
    tags: Array,
});

const step = ref(1);
const file = ref(null);
const hasHeader = ref(true);
const headers = ref([]);
const preview = ref([]);
const isLoading = ref(false);

const form = useForm({
    file: null,
    has_header: true,
    mapping: {
        email: null,
        first_name: null,
        last_name: null,
        phone: null,
        company: null,
        job_title: null,
        address: null,
        city: null,
        country: null,
        postal_code: null,
    },
    lists: [],
    tags: [],
    update_existing: false,
});

const fields = [
    { key: 'email', label: 'Email', required: true },
    { key: 'first_name', label: 'Prénom', required: false },
    { key: 'last_name', label: 'Nom', required: false },
    { key: 'phone', label: 'Téléphone', required: false },
    { key: 'company', label: 'Entreprise', required: false },
    { key: 'job_title', label: 'Poste', required: false },
    { key: 'address', label: 'Adresse', required: false },
    { key: 'city', label: 'Ville', required: false },
    { key: 'country', label: 'Pays', required: false },
    { key: 'postal_code', label: 'Code postal', required: false },
];

const canProceedStep1 = computed(() => file.value !== null);
const canProceedStep2 = computed(() => form.mapping.email !== null);

const handleFileSelect = (event) => {
    const selectedFile = event.target.files[0];
    if (selectedFile) {
        file.value = selectedFile;
        form.file = selectedFile;
    }
};

const handleFileDrop = (event) => {
    const droppedFile = event.dataTransfer.files[0];
    if (droppedFile && (droppedFile.type === 'text/csv' || droppedFile.name.endsWith('.csv'))) {
        file.value = droppedFile;
        form.file = droppedFile;
    }
};

const uploadAndPreview = async () => {
    if (!file.value) return;

    isLoading.value = true;

    const formData = new FormData();
    formData.append('file', file.value);
    formData.append('has_header', hasHeader.value ? '1' : '0');

    try {
        const response = await fetch('/contacts/import/preview', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();
        headers.value = data.headers;
        preview.value = data.preview;
        form.has_header = hasHeader.value;

        // Auto-map common column names
        autoMapColumns();

        step.value = 2;
    } catch (error) {
        console.error('Error previewing file:', error);
    } finally {
        isLoading.value = false;
    }
};

const autoMapColumns = () => {
    const lowercaseHeaders = headers.value.map(h => h.toLowerCase().trim());

    const mappings = {
        email: ['email', 'e-mail', 'mail', 'courriel'],
        first_name: ['first_name', 'firstname', 'prénom', 'prenom', 'first name'],
        last_name: ['last_name', 'lastname', 'nom', 'last name', 'name'],
        phone: ['phone', 'téléphone', 'telephone', 'tel', 'mobile'],
        company: ['company', 'entreprise', 'société', 'societe', 'organization'],
        job_title: ['job_title', 'poste', 'titre', 'position', 'job', 'title'],
        address: ['address', 'adresse'],
        city: ['city', 'ville'],
        country: ['country', 'pays'],
        postal_code: ['postal_code', 'code_postal', 'zip', 'zipcode', 'code postal'],
    };

    for (const [field, keywords] of Object.entries(mappings)) {
        for (let i = 0; i < lowercaseHeaders.length; i++) {
            if (keywords.includes(lowercaseHeaders[i])) {
                form.mapping[field] = i;
                break;
            }
        }
    }
};

const goToStep3 = () => {
    step.value = 3;
};

const submitImport = () => {
    form.post('/contacts/import', {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const resetForm = () => {
    step.value = 1;
    file.value = null;
    headers.value = [];
    preview.value = [];
    form.reset();
};
</script>

<template>
    <AppLayout>
        <Head title="Importer des contacts" />

        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    href="/contacts"
                    class="inline-flex items-center text-sm text-secondary-500 hover:text-secondary-700"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux contacts
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-secondary-900">Importer des contacts</h1>
                <p class="mt-1 text-sm text-secondary-500">Importez vos contacts depuis un fichier CSV</p>
            </div>

            <!-- Steps Indicator -->
            <div class="mb-8">
                <div class="flex items-center">
                    <div
                        :class="[
                            'flex items-center justify-center w-8 h-8 rounded-full',
                            step >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600'
                        ]"
                    >
                        1
                    </div>
                    <div :class="['flex-1 h-1 mx-2', step >= 2 ? 'bg-primary-600' : 'bg-gray-200']"></div>
                    <div
                        :class="[
                            'flex items-center justify-center w-8 h-8 rounded-full',
                            step >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600'
                        ]"
                    >
                        2
                    </div>
                    <div :class="['flex-1 h-1 mx-2', step >= 3 ? 'bg-primary-600' : 'bg-gray-200']"></div>
                    <div
                        :class="[
                            'flex items-center justify-center w-8 h-8 rounded-full',
                            step >= 3 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600'
                        ]"
                    >
                        3
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-sm text-secondary-500">
                    <span>Fichier</span>
                    <span>Mappage</span>
                    <span>Options</span>
                </div>
            </div>

            <!-- Step 1: File Upload -->
            <div v-if="step === 1" class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Sélectionner un fichier CSV</h2>

                    <div
                        class="border-2 border-dashed border-secondary-300 rounded-xl p-8 text-center hover:border-primary-500 transition-colors"
                        @dragover.prevent
                        @drop.prevent="handleFileDrop"
                    >
                        <svg class="mx-auto w-12 h-12 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>

                        <p v-if="file" class="mt-4 text-sm text-secondary-900 font-medium">
                            {{ file.name }}
                            <span class="text-secondary-500">({{ (file.size / 1024).toFixed(1) }} Ko)</span>
                        </p>
                        <p v-else class="mt-4 text-sm text-secondary-500">
                            Glissez-déposez votre fichier CSV ici, ou
                        </p>

                        <label class="mt-4 inline-block">
                            <span class="px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 cursor-pointer">
                                Parcourir
                            </span>
                            <input
                                type="file"
                                accept=".csv"
                                class="hidden"
                                @change="handleFileSelect"
                            />
                        </label>
                    </div>

                    <div class="mt-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="hasHeader"
                                class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                            />
                            <span class="ml-2 text-sm text-secondary-700">La première ligne contient les en-têtes</span>
                        </label>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <a
                            href="/contacts/import/template"
                            class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                        >
                            Télécharger le modèle CSV
                        </a>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        @click="uploadAndPreview"
                        :disabled="!canProceedStep1 || isLoading"
                        class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                    >
                        <span v-if="isLoading">Chargement...</span>
                        <span v-else>Continuer</span>
                    </button>
                </div>
            </div>

            <!-- Step 2: Column Mapping -->
            <div v-if="step === 2" class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Mapper les colonnes</h2>
                    <p class="text-sm text-secondary-500 mb-6">
                        Associez les colonnes de votre fichier aux champs de contact.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="field in fields"
                            :key="field.key"
                            class="flex items-center space-x-3"
                        >
                            <label class="w-32 text-sm font-medium text-secondary-700">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.mapping[field.key]"
                                class="flex-1 px-3 py-2 text-sm border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option :value="null">-- Ignorer --</option>
                                <option v-for="(header, index) in headers" :key="index" :value="index">
                                    {{ header }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h3 class="text-sm font-semibold text-secondary-900 mb-4">Aperçu des données</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        v-for="(header, index) in headers"
                                        :key="index"
                                        class="px-3 py-2 text-left text-xs font-medium text-secondary-500 uppercase"
                                    >
                                        {{ header }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(row, rowIndex) in preview.slice(0, 5)" :key="rowIndex">
                                    <td
                                        v-for="(cell, cellIndex) in row"
                                        :key="cellIndex"
                                        class="px-3 py-2 text-secondary-700 whitespace-nowrap"
                                    >
                                        {{ cell }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button
                        @click="step = 1"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        Retour
                    </button>
                    <button
                        @click="goToStep3"
                        :disabled="!canProceedStep2"
                        class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                    >
                        Continuer
                    </button>
                </div>
            </div>

            <!-- Step 3: Options -->
            <div v-if="step === 3" class="space-y-6">
                <!-- Lists Selection -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Ajouter à des listes</h2>
                    <p class="text-sm text-secondary-500 mb-4">
                        Les contacts importés seront ajoutés aux listes sélectionnées.
                    </p>

                    <div v-if="lists.length > 0" class="space-y-2">
                        <label
                            v-for="list in lists"
                            :key="list.id"
                            class="flex items-center p-3 rounded-lg border border-secondary-200 hover:bg-gray-50 cursor-pointer"
                            :class="{ 'border-primary-500 bg-primary-50': form.lists.includes(list.id) }"
                        >
                            <input
                                type="checkbox"
                                :value="list.id"
                                v-model="form.lists"
                                class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                            />
                            <div class="ml-3 flex items-center">
                                <span
                                    v-if="list.color"
                                    :style="{ backgroundColor: list.color }"
                                    class="w-3 h-3 rounded-full mr-2"
                                ></span>
                                <span class="text-sm font-medium text-secondary-900">{{ list.name }}</span>
                            </div>
                        </label>
                    </div>
                    <p v-else class="text-sm text-secondary-500">Aucune liste disponible.</p>
                </div>

                <!-- Tags Selection -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Ajouter des tags</h2>

                    <div v-if="tags.length > 0" class="flex flex-wrap gap-2">
                        <label
                            v-for="tag in tags"
                            :key="tag.id"
                            class="cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="tag.id"
                                v-model="form.tags"
                                class="sr-only peer"
                            />
                            <span
                                :style="{
                                    backgroundColor: form.tags.includes(tag.id) ? tag.color : tag.color + '20',
                                    color: form.tags.includes(tag.id) ? 'white' : tag.color
                                }"
                                class="inline-flex px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
                            >
                                {{ tag.name }}
                            </span>
                        </label>
                    </div>
                    <p v-else class="text-sm text-secondary-500">Aucun tag disponible.</p>
                </div>

                <!-- Import Options -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Options d'import</h2>

                    <label class="flex items-start">
                        <input
                            type="checkbox"
                            v-model="form.update_existing"
                            class="mt-0.5 w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                        />
                        <span class="ml-3">
                            <span class="block text-sm font-medium text-secondary-900">Mettre à jour les contacts existants</span>
                            <span class="block text-sm text-secondary-500">
                                Si un contact avec le même email existe déjà, ses informations seront mises à jour.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="flex justify-between">
                    <button
                        @click="step = 2"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        Retour
                    </button>
                    <button
                        @click="submitImport"
                        :disabled="form.processing"
                        class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Import en cours...</span>
                        <span v-else>Importer les contacts</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

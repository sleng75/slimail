<script setup>
/**
 * Settings Page
 * Organization settings management
 */

import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    tenant: Object,
    timezones: Object,
    locales: Object,
    currencies: Object,
});

const activeTab = ref('general');
const showLogoModal = ref(false);
const logoPreview = ref(null);

// General settings form
const generalForm = useForm({
    name: props.tenant.name,
    email: props.tenant.email || '',
    phone: props.tenant.phone || '',
    address: props.tenant.address || '',
    city: props.tenant.city || '',
    country: props.tenant.country || '',
    website: props.tenant.website || '',
    tax_id: props.tenant.tax_id || '',
});

// Preferences form
const preferencesForm = useForm({
    timezone: props.tenant.timezone || 'Africa/Abidjan',
    locale: props.tenant.locale || 'fr',
    currency: props.tenant.currency || 'XOF',
});

// Logo form
const logoForm = useForm({
    logo: null,
});

const tabs = [
    { id: 'general', label: 'Informations', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { id: 'preferences', label: 'Préférences', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
    { id: 'branding', label: 'Logo', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
];

const submitGeneral = () => {
    generalForm.put(route('settings.update'), {
        preserveScroll: true,
    });
};

const submitPreferences = () => {
    preferencesForm.put(route('settings.update'), {
        preserveScroll: true,
    });
};

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoForm.logo = file;
        logoPreview.value = URL.createObjectURL(file);
        showLogoModal.value = true;
    }
};

const submitLogo = () => {
    logoForm.post(route('settings.logo'), {
        preserveScroll: true,
        onSuccess: () => {
            showLogoModal.value = false;
            logoPreview.value = null;
        },
    });
};

const deleteLogo = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer le logo ?')) {
        router.delete(route('settings.logo.delete'), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Paramètres" />

        <PageContainer>
            <div class="space-y-section">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-2xl lg:text-3xl font-bold text-secondary-900">Paramètres</h1>
                        <p class="text-secondary-500">Configurez les paramètres de votre organisation</p>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Link
                        :href="route('profile.index')"
                        class="card card-hover flex items-center gap-4"
                    >
                        <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-secondary-900">Mon compte</h3>
                            <p class="text-sm text-secondary-500">Profil personnel</p>
                        </div>
                    </Link>

                    <Link
                        :href="route('api-settings.index')"
                        class="card card-hover flex items-center gap-4"
                    >
                        <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-secondary-900">API</h3>
                            <p class="text-sm text-secondary-500">Clés et intégrations</p>
                        </div>
                    </Link>

                    <Link
                        :href="route('users.index')"
                        class="card card-hover flex items-center gap-4"
                    >
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-secondary-900">Équipe</h3>
                            <p class="text-sm text-secondary-500">Membres et rôles</p>
                        </div>
                    </Link>
                </div>

                <!-- Settings Card -->
                <div class="card !p-0">
                    <!-- Tabs -->
                    <div class="border-b border-gray-100">
                        <nav class="flex -mb-px overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    'flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                    activeTab === tab.id
                                        ? 'border-primary-600 text-primary-600'
                                        : 'border-transparent text-secondary-500 hover:text-secondary-700 hover:border-secondary-300'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                                </svg>
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <!-- General Tab -->
                    <div v-show="activeTab === 'general'" class="p-6">
                        <form @submit.prevent="submitGeneral" class="space-y-5 max-w-2xl">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900 mb-1">Informations de l'organisation</h3>
                                <p class="text-sm text-secondary-500 mb-5">Ces informations apparaîtront sur vos factures.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Nom de l'organisation *</label>
                                    <input
                                        v-model="generalForm.name"
                                        type="text"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                    <p v-if="generalForm.errors.name" class="mt-1 text-sm text-red-600">{{ generalForm.errors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Email</label>
                                    <input
                                        v-model="generalForm.email"
                                        type="email"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                        placeholder="contact@exemple.com"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Téléphone</label>
                                    <input
                                        v-model="generalForm.phone"
                                        type="tel"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                        placeholder="+225 XX XX XX XX XX"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Adresse</label>
                                    <input
                                        v-model="generalForm.address"
                                        type="text"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Ville</label>
                                    <input
                                        v-model="generalForm.city"
                                        type="text"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Pays</label>
                                    <input
                                        v-model="generalForm.country"
                                        type="text"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Site web</label>
                                    <input
                                        v-model="generalForm.website"
                                        type="url"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                        placeholder="https://www.exemple.com"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">N° fiscal / RCCM</label>
                                    <input
                                        v-model="generalForm.tax_id"
                                        type="text"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button
                                    type="submit"
                                    :disabled="generalForm.processing"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                                >
                                    Enregistrer
                                </button>
                                <span v-if="generalForm.recentlySuccessful" class="text-sm text-green-600">
                                    Enregistré !
                                </span>
                            </div>
                        </form>
                    </div>

                    <!-- Preferences Tab -->
                    <div v-show="activeTab === 'preferences'" class="p-6">
                        <form @submit.prevent="submitPreferences" class="space-y-5 max-w-xl">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900 mb-1">Préférences régionales</h3>
                                <p class="text-sm text-secondary-500 mb-5">Configurez les paramètres régionaux.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1.5">Fuseau horaire</label>
                                <select
                                    v-model="preferencesForm.timezone"
                                    class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option v-for="(label, value) in timezones" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-secondary-500">Utilisé pour planifier l'envoi des campagnes.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1.5">Langue</label>
                                <select
                                    v-model="preferencesForm.locale"
                                    class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option v-for="(label, value) in locales" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1.5">Devise</label>
                                <select
                                    v-model="preferencesForm.currency"
                                    class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option v-for="(label, value) in currencies" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button
                                    type="submit"
                                    :disabled="preferencesForm.processing"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                                >
                                    Enregistrer
                                </button>
                                <span v-if="preferencesForm.recentlySuccessful" class="text-sm text-green-600">
                                    Enregistré !
                                </span>
                            </div>
                        </form>
                    </div>

                    <!-- Branding Tab -->
                    <div v-show="activeTab === 'branding'" class="p-6">
                        <div class="max-w-xl">
                            <h3 class="text-lg font-semibold text-secondary-900 mb-1">Logo de l'organisation</h3>
                            <p class="text-sm text-secondary-500 mb-5">Ce logo apparaîtra dans vos emails.</p>

                            <div class="bg-gray-50 rounded-lg p-5">
                                <div class="flex items-center gap-5">
                                    <!-- Current Logo -->
                                    <div class="w-20 h-20 rounded-lg bg-white border-2 border-dashed border-secondary-300 flex items-center justify-center overflow-hidden">
                                        <img
                                            v-if="tenant.logo"
                                            :src="`/storage/${tenant.logo}`"
                                            :alt="tenant.name"
                                            class="w-full h-full object-contain"
                                        />
                                        <svg v-else class="w-8 h-8 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>

                                    <div class="flex-1">
                                        <div class="flex gap-3">
                                            <label class="px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 cursor-pointer">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                    </svg>
                                                    Changer
                                                </span>
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    class="hidden"
                                                    @change="handleLogoChange"
                                                />
                                            </label>
                                            <button
                                                v-if="tenant.logo"
                                                @click="deleteLogo"
                                                type="button"
                                                class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                        <p class="text-xs text-secondary-500 mt-2">
                                            PNG, JPG, SVG. Max 2 Mo.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>

        <!-- Logo Preview Modal -->
        <Teleport to="body">
            <div v-if="showLogoModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showLogoModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 text-center">
                        <h2 class="text-lg font-semibold text-secondary-900 mb-4">Nouveau logo</h2>

                        <div class="w-32 h-32 rounded-lg mx-auto mb-6 overflow-hidden bg-gray-100 border border-secondary-200">
                            <img
                                v-if="logoPreview"
                                :src="logoPreview"
                                class="w-full h-full object-contain"
                            />
                        </div>

                        <div class="flex justify-center gap-3">
                            <button
                                @click="showLogoModal = false; logoPreview = null;"
                                class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                            >
                                Annuler
                            </button>
                            <button
                                @click="submitLogo"
                                :disabled="logoForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

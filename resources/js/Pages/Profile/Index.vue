<script setup>
/**
 * Profile Page
 * User account management
 */

import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    user: Object,
    roles: Array,
    permissions: Array,
});

const showDeleteModal = ref(false);
const showAvatarModal = ref(false);
const activeTab = ref('profile');
const avatarPreview = ref(null);

// Profile form
const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
});

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Avatar form
const avatarForm = useForm({
    avatar: null,
});

// Delete account form
const deleteForm = useForm({
    password: '',
});

const tabs = [
    { id: 'profile', label: 'Informations', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 'security', label: 'Sécurité', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
    { id: 'permissions', label: 'Permissions', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { id: 'danger', label: 'Zone dangereuse', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
];

const initials = computed(() => {
    return props.user.name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const submitProfile = () => {
    profileForm.put(route('profile.update'), {
        preserveScroll: true,
    });
};

const submitPassword = () => {
    passwordForm.put(route('profile.password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

const handleAvatarChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        avatarForm.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
        showAvatarModal.value = true;
    }
};

const submitAvatar = () => {
    avatarForm.post(route('profile.avatar'), {
        preserveScroll: true,
        onSuccess: () => {
            showAvatarModal.value = false;
            avatarPreview.value = null;
        },
    });
};

const deleteAvatar = () => {
    router.delete(route('profile.avatar.delete'), {
        preserveScroll: true,
    });
};

const submitDelete = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
    });
};

const getRoleLabel = (role) => {
    const labels = {
        owner: 'Propriétaire',
        admin: 'Administrateur',
        editor: 'Éditeur',
        analyst: 'Analyste',
        developer: 'Développeur',
    };
    return labels[role] || role;
};

const getRoleColor = (role) => {
    const colors = {
        owner: 'bg-purple-100 text-purple-800',
        admin: 'bg-red-100 text-red-800',
        editor: 'bg-blue-100 text-blue-800',
        analyst: 'bg-green-100 text-green-800',
        developer: 'bg-amber-100 text-amber-800',
    };
    return colors[role] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AppLayout>
        <Head title="Mon compte" />

        <PageContainer>
            <div class="space-y-section">
                <!-- Header -->
                <div class="space-y-1">
                    <h1 class="text-2xl lg:text-3xl font-bold text-secondary-900">Mon compte</h1>
                    <p class="text-secondary-500">Gérez vos informations personnelles et vos paramètres de sécurité</p>
                </div>

                <!-- Profile Card -->
                <div class="card">
                    <div class="flex flex-col sm:flex-row items-center gap-6 p-6 border-b border-gray-100">
                        <!-- Avatar -->
                        <div class="relative group">
                            <div class="w-20 h-20 rounded-xl bg-primary-100 flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="user.avatar"
                                    :src="`/storage/${user.avatar}`"
                                    :alt="user.name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-2xl font-bold text-primary-600">{{ initials }}</span>
                            </div>
                            <label class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-xl opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleAvatarChange"
                                />
                            </label>
                        </div>

                        <div class="text-center sm:text-left flex-1">
                            <h2 class="text-xl font-semibold text-secondary-900">{{ user.name }}</h2>
                            <p class="text-secondary-500 mt-0.5">{{ user.email }}</p>
                            <div class="flex flex-wrap justify-center sm:justify-start gap-2 mt-3">
                                <span
                                    v-for="role in roles"
                                    :key="role"
                                    :class="['px-2.5 py-1 rounded-full text-xs font-medium', getRoleColor(role)]"
                                >
                                    {{ getRoleLabel(role) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 text-sm text-secondary-500">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Membre depuis {{ user.created_at }}
                            </div>
                            <div v-if="user.email_verified_at" class="flex items-center gap-1.5 text-green-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Vérifié
                            </div>
                        </div>
                    </div>

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

                    <!-- Profile Tab -->
                    <div v-show="activeTab === 'profile'" class="p-6">
                        <form @submit.prevent="submitProfile" class="space-y-5 max-w-xl">
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1.5">Nom complet</label>
                                <input
                                    v-model="profileForm.name"
                                    type="text"
                                    class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                                <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">{{ profileForm.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1.5">Adresse email</label>
                                <input
                                    v-model="profileForm.email"
                                    type="email"
                                    class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                                <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">{{ profileForm.errors.email }}</p>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                                >
                                    Enregistrer
                                </button>
                                <span v-if="profileForm.recentlySuccessful" class="text-sm text-green-600">
                                    Enregistré !
                                </span>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div v-show="activeTab === 'security'" class="p-6">
                        <div class="max-w-xl">
                            <h3 class="text-lg font-semibold text-secondary-900 mb-1">Changer le mot de passe</h3>
                            <p class="text-sm text-secondary-500 mb-5">Assurez-vous d'utiliser un mot de passe long et unique.</p>

                            <form @submit.prevent="submitPassword" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Mot de passe actuel</label>
                                    <input
                                        v-model="passwordForm.current_password"
                                        type="password"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                    <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.current_password }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Nouveau mot de passe</label>
                                    <input
                                        v-model="passwordForm.password"
                                        type="password"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                    <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-1.5">Confirmer le mot de passe</label>
                                    <input
                                        v-model="passwordForm.password_confirmation"
                                        type="password"
                                        class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>

                                <div class="flex items-center gap-3 pt-2">
                                    <button
                                        type="submit"
                                        :disabled="passwordForm.processing"
                                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                                    >
                                        Mettre à jour
                                    </button>
                                    <span v-if="passwordForm.recentlySuccessful" class="text-sm text-green-600">
                                        Mot de passe mis à jour !
                                    </span>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Permissions Tab -->
                    <div v-show="activeTab === 'permissions'" class="p-6">
                        <div class="max-w-xl space-y-5">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900 mb-1">Vos permissions</h3>
                                <p class="text-sm text-secondary-500">Les permissions déterminent ce que vous pouvez faire dans l'application.</p>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-secondary-900 mb-3">Rôles attribués</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="role in roles"
                                        :key="role"
                                        :class="['px-3 py-1.5 rounded-lg text-sm font-medium', getRoleColor(role)]"
                                    >
                                        {{ getRoleLabel(role) }}
                                    </span>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-secondary-900 mb-3">Permissions actives</h4>
                                <div class="grid grid-cols-2 gap-2">
                                    <div
                                        v-for="perm in permissions"
                                        :key="perm"
                                        class="flex items-center gap-2 text-sm text-secondary-600"
                                    >
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ perm }}
                                    </div>
                                </div>
                                <p v-if="permissions.length === 0" class="text-sm text-secondary-500">
                                    Aucune permission spécifique.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone Tab -->
                    <div v-show="activeTab === 'danger'" class="p-6">
                        <div class="max-w-xl">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-5">
                                <div class="flex gap-4">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-red-900">Supprimer mon compte</h3>
                                        <p class="text-sm text-red-700 mt-1 mb-4">
                                            Une fois votre compte supprimé, toutes vos données seront définitivement effacées.
                                        </p>
                                        <button
                                            @click="showDeleteModal = true"
                                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                                        >
                                            Supprimer mon compte
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>

        <!-- Avatar Modal -->
        <Teleport to="body">
            <div v-if="showAvatarModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAvatarModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 text-center">
                        <h2 class="text-lg font-semibold text-secondary-900 mb-4">Nouvelle photo de profil</h2>

                        <div class="w-32 h-32 rounded-xl mx-auto mb-6 overflow-hidden bg-gray-100">
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                class="w-full h-full object-cover"
                            />
                        </div>

                        <div class="flex justify-center gap-3">
                            <button
                                @click="showAvatarModal = false; avatarPreview = null;"
                                class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                            >
                                Annuler
                            </button>
                            <button
                                @click="submitAvatar"
                                :disabled="avatarForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Account Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                        <div class="text-center mb-6">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h2 class="text-lg font-semibold text-secondary-900">Supprimer votre compte</h2>
                            <p class="text-secondary-500 text-sm mt-1">Cette action est irréversible.</p>
                        </div>

                        <form @submit.prevent="submitDelete" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1.5">Confirmez avec votre mot de passe</label>
                                <input
                                    v-model="deleteForm.password"
                                    type="password"
                                    class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                    placeholder="Votre mot de passe"
                                />
                                <p v-if="deleteForm.errors.password" class="mt-1 text-sm text-red-600">{{ deleteForm.errors.password }}</p>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="showDeleteModal = false"
                                    class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="deleteForm.processing"
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                                >
                                    Supprimer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

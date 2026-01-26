<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    roles: Object,
});

const showPasswordForm = ref(false);
const showDeleteModal = ref(false);

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
    phone: props.user.phone || '',
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(`/users/${props.user.id}`);
};

const updatePassword = () => {
    passwordForm.put(`/users/${props.user.id}/password`, {
        onSuccess: () => {
            passwordForm.reset();
            showPasswordForm.value = false;
        },
    });
};

const deleteUser = () => {
    router.delete(`/users/${props.user.id}`);
};

const getRoleDescription = (role) => {
    const descriptions = {
        owner: 'Accès complet à toutes les fonctionnalités, y compris la facturation et la gestion des utilisateurs.',
        admin: 'Gestion complète sauf la facturation. Peut inviter et gérer les membres de l\'équipe.',
        editor: 'Création et gestion de contacts, campagnes, templates et automatisations.',
        analyst: 'Visualisation des statistiques et des données. Accès en lecture seule.',
        developer: 'Gestion des clés API et intégrations techniques.',
    };
    return descriptions[role] || '';
};

const formatDate = (dateString) => {
    if (!dateString) return 'Jamais';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getInitials = (name) => {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().substring(0, 2);
};

const currentUser = usePage().props.auth?.user;
const isSelf = currentUser?.id === props.user.id;
</script>

<template>
    <AppLayout>
        <Head :title="`Modifier ${user.name}`" />

        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link href="/users" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div class="flex items-center gap-4">
                        <div v-if="user.avatar" class="w-12 h-12 rounded-full overflow-hidden">
                            <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-lg font-medium text-primary-700">{{ getInitials(user.name) }}</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ user.name }}</h1>
                            <p class="text-sm text-gray-500">{{ user.email }}</p>
                        </div>
                    </div>
                </div>
                <button
                    v-if="!isSelf"
                    @click="showDeleteModal = true"
                    class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200"
                >
                    Supprimer
                </button>
            </div>

            <!-- User Info Card -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="grid grid-cols-3 gap-6">
                    <div>
                        <span class="text-sm text-gray-500">Dernière connexion</span>
                        <p class="mt-1 font-medium text-gray-900">{{ formatDate(user.last_login_at) }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Membre depuis</span>
                        <p class="mt-1 font-medium text-gray-900">{{ formatDate(user.created_at) }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Rôle actuel</span>
                        <p class="mt-1 font-medium text-gray-900">{{ roles[user.role] }}</p>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900">Informations générales</h2>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nom complet *
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Adresse email *
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        :class="{ 'border-red-500': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Téléphone
                    </label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        :class="{ 'border-red-500': form.errors.phone }"
                    />
                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Rôle *
                    </label>
                    <div class="space-y-3">
                        <label
                            v-for="(label, value) in roles"
                            :key="value"
                            :class="[
                                'flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all',
                                form.role === value
                                    ? 'border-primary-500 bg-primary-50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <input
                                type="radio"
                                :value="value"
                                v-model="form.role"
                                class="mt-1 w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                            />
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">{{ label }}</span>
                                <span class="block text-xs text-gray-500 mt-1">{{ getRoleDescription(value) }}</span>
                            </div>
                        </label>
                    </div>
                    <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <Link
                        href="/users"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>

            <!-- Password Section -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Mot de passe</h2>
                        <p class="text-sm text-gray-500">Modifier le mot de passe de ce membre</p>
                    </div>
                    <button
                        v-if="!showPasswordForm"
                        @click="showPasswordForm = true"
                        class="px-4 py-2 text-sm font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50"
                    >
                        Changer le mot de passe
                    </button>
                </div>

                <form v-if="showPasswordForm" @submit.prevent="updatePassword" class="mt-6 space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Nouveau mot de passe *
                        </label>
                        <input
                            id="password"
                            v-model="passwordForm.password"
                            type="password"
                            placeholder="Minimum 8 caractères"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            :class="{ 'border-red-500': passwordForm.errors.password }"
                        />
                        <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmer le mot de passe *
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            placeholder="Répétez le mot de passe"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="showPasswordForm = false; passwordForm.reset()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? 'Mise à jour...' : 'Mettre à jour' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danger Zone -->
            <div v-if="!isSelf" class="bg-red-50 rounded-xl border border-red-200 p-6">
                <h2 class="text-lg font-semibold text-red-900">Zone dangereuse</h2>
                <p class="mt-1 text-sm text-red-700">
                    La suppression de ce membre est définitive. Il perdra immédiatement l'accès au compte.
                </p>
                <button
                    @click="showDeleteModal = true"
                    class="mt-4 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                >
                    Supprimer définitivement
                </button>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-red-100 rounded-lg">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Supprimer ce membre ?</h3>
                            <p class="text-sm text-gray-500">Cette action est irréversible</p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 mb-6">
                        Êtes-vous sûr de vouloir supprimer <strong>{{ user.name }}</strong> de l'équipe ?
                        Cette personne n'aura plus accès au compte.
                    </p>

                    <div class="flex justify-end gap-3">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="deleteUser"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

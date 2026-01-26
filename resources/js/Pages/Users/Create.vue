<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    roles: Object,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'editor',
    phone: '',
});

const submit = () => {
    form.post('/users', {
        onSuccess: () => {
            form.reset();
        },
    });
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
</script>

<template>
    <AppLayout>
        <Head title="Inviter un membre" />

        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center">
                <Link href="/users" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Inviter un membre</h1>
                    <p class="mt-1 text-sm text-gray-500">Ajoutez un nouveau membre à votre équipe</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nom complet *
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        placeholder="Jean Dupont"
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
                        placeholder="jean.dupont@entreprise.com"
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
                        placeholder="+225 07 00 00 00 00"
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

                <hr class="border-gray-200" />

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Mot de passe *
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Minimum 8 caractères"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        :class="{ 'border-red-500': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirmer le mot de passe *
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        placeholder="Répétez le mot de passe"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>

                <!-- Info -->
                <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-xl text-sm text-blue-700">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p>
                        Le nouveau membre recevra un email avec ses identifiants de connexion. Il pourra modifier son mot de passe à sa première connexion.
                    </p>
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
                        {{ form.processing ? 'Création...' : 'Créer le membre' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

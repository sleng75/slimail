<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const form = useForm({
    name: '',
    company_name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Inscription" />

    <GuestLayout>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-secondary-900">Créer un compte</h2>
            <p class="mt-2 text-secondary-600">
                Commencez à envoyer des emails professionnels
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-secondary-700 mb-1.5">
                    Nom complet
                </label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all outline-none"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Votre nom"
                />
                <p v-if="form.errors.name" class="mt-1.5 text-sm text-primary-600">
                    {{ form.errors.name }}
                </p>
            </div>

            <div>
                <label for="company_name" class="block text-sm font-medium text-secondary-700 mb-1.5">
                    Nom de l'entreprise
                </label>
                <input
                    id="company_name"
                    v-model="form.company_name"
                    type="text"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all outline-none"
                    required
                    placeholder="Votre entreprise"
                />
                <p v-if="form.errors.company_name" class="mt-1.5 text-sm text-primary-600">
                    {{ form.errors.company_name }}
                </p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-secondary-700 mb-1.5">
                    Email professionnel
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all outline-none"
                    required
                    autocomplete="username"
                    placeholder="vous@votreentreprise.com"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-sm text-primary-600">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-secondary-700 mb-1.5">
                    Mot de passe
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all outline-none"
                    required
                    autocomplete="new-password"
                    placeholder="8 caractères minimum"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-sm text-primary-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-1.5">
                    Confirmer le mot de passe
                </label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all outline-none"
                    required
                    autocomplete="new-password"
                    placeholder="Répétez votre mot de passe"
                />
                <p v-if="form.errors.password_confirmation" class="mt-1.5 text-sm text-primary-600">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <div class="flex items-start pt-2">
                <input
                    id="terms"
                    type="checkbox"
                    required
                    class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <label for="terms" class="ml-2 text-sm text-secondary-600">
                    J'accepte les
                    <a href="/terms" class="text-primary-600 hover:text-primary-700 font-medium">conditions d'utilisation</a>
                    et la
                    <a href="/privacy" class="text-primary-600 hover:text-primary-700 font-medium">politique de confidentialité</a>
                </label>
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full flex items-center justify-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Créer mon compte
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-secondary-600">
                Déjà inscrit ?
                <Link href="/login" class="text-primary-600 hover:text-primary-700 font-medium">
                    Se connecter
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>

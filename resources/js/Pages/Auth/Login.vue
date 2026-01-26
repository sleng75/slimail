<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Connexion" />

    <GuestLayout>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-secondary-900">Connexion</h2>
            <p class="mt-2 text-secondary-600">
                Connectez-vous à votre compte SliMail
            </p>
        </div>

        <div v-if="status" class="mb-4 p-4 rounded-xl bg-green-50 border border-green-100 text-sm text-green-700">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-secondary-700 mb-1.5">
                    Email
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all outline-none"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="vous@example.com"
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
                    autocomplete="current-password"
                    placeholder="Votre mot de passe"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-sm text-primary-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-secondary-600">Se souvenir de moi</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    href="/forgot-password"
                    class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                >
                    Mot de passe oublié ?
                </Link>
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full flex items-center justify-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Se connecter
                </button>
            </div>
        </form>

        <div class="mt-8">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-white px-4 text-secondary-500">Nouveau sur SliMail ?</span>
                </div>
            </div>

            <div class="mt-6">
                <Link
                    href="/register"
                    class="w-full flex items-center justify-center px-6 py-3 border-2 border-secondary-200 text-secondary-700 font-semibold rounded-xl hover:border-primary-600 hover:text-primary-600 transition-all"
                >
                    Créer un compte
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>

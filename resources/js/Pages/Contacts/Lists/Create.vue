<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    colors: Array,
});

const form = useForm({
    name: '',
    description: '',
    color: props.colors[0],
    type: 'static',
    double_optin: false,
    default_from_name: '',
    default_from_email: '',
});

const submit = () => {
    form.post('/contact-lists');
};
</script>

<template>
    <AppLayout>
        <Head title="Créer une liste" />

        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    href="/contact-lists"
                    class="inline-flex items-center text-sm text-secondary-500 hover:text-secondary-700"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux listes
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-secondary-900">Créer une liste</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Informations de base</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700">
                                Nom de la liste <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Ex: Newsletter mensuelle"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Décrivez l'objectif de cette liste..."
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-2">Couleur</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="color in colors"
                                    :key="color"
                                    type="button"
                                    @click="form.color = color"
                                    :style="{ backgroundColor: color }"
                                    :class="[
                                        'w-8 h-8 rounded-full transition-transform',
                                        form.color === color ? 'ring-2 ring-offset-2 ring-secondary-400 scale-110' : 'hover:scale-110'
                                    ]"
                                ></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Type -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Type de liste</h2>

                    <div class="space-y-3">
                        <label class="flex items-start p-4 rounded-lg border border-secondary-200 cursor-pointer hover:bg-gray-50"
                            :class="{ 'border-primary-500 bg-primary-50': form.type === 'static' }"
                        >
                            <input
                                type="radio"
                                v-model="form.type"
                                value="static"
                                class="mt-0.5 w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500"
                            />
                            <span class="ml-3">
                                <span class="block text-sm font-medium text-secondary-900">Liste statique</span>
                                <span class="block text-sm text-secondary-500">Ajoutez manuellement des contacts à cette liste</span>
                            </span>
                        </label>
                        <label class="flex items-start p-4 rounded-lg border border-secondary-200 cursor-pointer hover:bg-gray-50"
                            :class="{ 'border-primary-500 bg-primary-50': form.type === 'dynamic' }"
                        >
                            <input
                                type="radio"
                                v-model="form.type"
                                value="dynamic"
                                class="mt-0.5 w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500"
                            />
                            <span class="ml-3">
                                <span class="block text-sm font-medium text-secondary-900">Segment dynamique</span>
                                <span class="block text-sm text-secondary-500">Les contacts sont automatiquement ajoutés selon des critères</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Paramètres</h2>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.double_optin"
                                class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                            />
                            <span class="ml-3">
                                <span class="block text-sm font-medium text-secondary-900">Double opt-in</span>
                                <span class="block text-sm text-secondary-500">Les nouveaux contacts doivent confirmer leur inscription</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Default Sender -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Expéditeur par défaut</h2>
                    <p class="text-sm text-secondary-500 mb-4">
                        Ces informations seront utilisées par défaut pour les campagnes envoyées à cette liste.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Nom</label>
                            <input
                                v-model="form.default_from_name"
                                type="text"
                                placeholder="Votre entreprise"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Email</label>
                            <input
                                v-model="form.default_from_email"
                                type="email"
                                placeholder="contact@exemple.com"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3">
                    <Link
                        href="/contact-lists"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Création...</span>
                        <span v-else>Créer la liste</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

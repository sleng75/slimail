<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    list: Object,
    colors: Array,
});

const form = useForm({
    name: props.list.name,
    description: props.list.description || '',
    color: props.list.color || props.colors[0],
    type: props.list.type,
    double_optin: props.list.double_optin,
    default_from_name: props.list.default_from_name || '',
    default_from_email: props.list.default_from_email || '',
});

const submit = () => {
    form.put(`/contact-lists/${props.list.id}`);
};
</script>

<template>
    <AppLayout>
        <Head :title="`Modifier ${list.name}`" />

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
                <h1 class="mt-2 text-2xl font-bold text-secondary-900">Modifier la liste</h1>
                <p class="mt-1 text-sm text-secondary-500">{{ list.contacts_count }} contacts dans cette liste</p>
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

                <!-- Type (read-only info) -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Type de liste</h2>

                    <div class="p-4 rounded-lg bg-gray-50">
                        <div class="flex items-center">
                            <span
                                :class="[
                                    'px-2.5 py-1 text-sm font-medium rounded',
                                    list.type === 'dynamic' ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-700'
                                ]"
                            >
                                {{ list.type === 'dynamic' ? 'Segment dynamique' : 'Liste statique' }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-secondary-500">
                            {{ list.type === 'dynamic'
                                ? 'Les contacts sont automatiquement ajoutés selon des critères.'
                                : 'Les contacts sont ajoutés manuellement.' }}
                        </p>
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
                        <span v-if="form.processing">Enregistrement...</span>
                        <span v-else>Enregistrer les modifications</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

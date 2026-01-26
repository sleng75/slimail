<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    categories: Object,
});

const form = useForm({
    name: '',
    description: '',
    category: '',
    default_subject: '',
    is_active: true,
});

const submit = () => {
    form.post('/templates', {
        onSuccess: () => {
            // Redirect is handled by controller
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Nouveau template" />

        <div class="py-6">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <nav class="flex items-center text-sm text-gray-500 mb-2">
                        <a href="/templates" class="hover:text-gray-700">Templates</a>
                        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-gray-900">Nouveau template</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Créer un nouveau template</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Configurez les informations de base, puis utilisez l'éditeur pour créer votre design.
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Nom du template <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="Ex: Newsletter mensuelle"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                Description
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="Décrivez l'utilisation de ce template..."
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">
                                Catégorie
                            </label>
                            <select
                                id="category"
                                v-model="form.category"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option value="">Sélectionner une catégorie</option>
                                <option v-for="(label, value) in categories" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                        </div>

                        <!-- Default Subject -->
                        <div>
                            <label for="default_subject" class="block text-sm font-medium text-gray-700">
                                Objet par défaut
                            </label>
                            <input
                                id="default_subject"
                                v-model="form.default_subject"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="Ex: Votre newsletter de {{current_month}}"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Vous pouvez utiliser des variables comme <code class="bg-gray-100 px-1 rounded">{{contact.first_name}}</code>
                            </p>
                            <p v-if="form.errors.default_subject" class="mt-1 text-sm text-red-600">{{ form.errors.default_subject }}</p>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                            />
                            <label for="is_active" class="ml-2 block text-sm text-gray-700">
                                Template actif
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl flex items-center justify-end space-x-3">
                        <a
                            href="/templates"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            Annuler
                        </a>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Création...
                            </span>
                            <span v-else>Créer et éditer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

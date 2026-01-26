<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    contact: Object,
    lists: Array,
    tags: Array,
});

const form = useForm({
    email: props.contact.email,
    first_name: props.contact.first_name || '',
    last_name: props.contact.last_name || '',
    phone: props.contact.phone || '',
    company: props.contact.company || '',
    job_title: props.contact.job_title || '',
    address: props.contact.address || '',
    city: props.contact.city || '',
    country: props.contact.country || '',
    postal_code: props.contact.postal_code || '',
    status: props.contact.status,
    lists: props.contact.lists.map(l => l.id),
    tags: props.contact.tags.map(t => t.id),
});

const submit = () => {
    form.put(`/contacts/${props.contact.id}`);
};

const getStatusLabel = (status) => {
    const labels = {
        subscribed: 'Abonné',
        unsubscribed: 'Désabonné',
        bounced: 'Rebond',
        complained: 'Plainte',
    };
    return labels[status] || status;
};
</script>

<template>
    <AppLayout>
        <Head :title="`Modifier ${contact.full_name}`" />

        <div class="max-w-3xl mx-auto">
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
                <h1 class="mt-2 text-2xl font-bold text-secondary-900">Modifier le contact</h1>
                <p class="mt-1 text-sm text-secondary-500">{{ contact.email }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Informations de base</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-secondary-700">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Prénom</label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Nom</label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Téléphone</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Entreprise</label>
                            <input
                                v-model="form.company"
                                type="text"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-secondary-700">Poste</label>
                            <input
                                v-model="form.job_title"
                                type="text"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Adresse</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-secondary-700">Adresse</label>
                            <input
                                v-model="form.address"
                                type="text"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Ville</label>
                            <input
                                v-model="form.city"
                                type="text"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700">Code postal</label>
                            <input
                                v-model="form.postal_code"
                                type="text"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-secondary-700">Pays</label>
                            <input
                                v-model="form.country"
                                type="text"
                                class="mt-1 w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Subscription Status -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Statut d'abonnement</h2>

                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input
                                type="radio"
                                v-model="form.status"
                                value="subscribed"
                                class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500"
                            />
                            <span class="ml-3">
                                <span class="block text-sm font-medium text-secondary-900">Abonné</span>
                                <span class="block text-sm text-secondary-500">Ce contact recevra vos emails marketing</span>
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                v-model="form.status"
                                value="unsubscribed"
                                class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500"
                            />
                            <span class="ml-3">
                                <span class="block text-sm font-medium text-secondary-900">Désabonné</span>
                                <span class="block text-sm text-secondary-500">Ce contact ne recevra pas vos emails marketing</span>
                            </span>
                        </label>
                        <label v-if="contact.status === 'bounced'" class="flex items-center opacity-50">
                            <input
                                type="radio"
                                v-model="form.status"
                                value="bounced"
                                disabled
                                class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500"
                            />
                            <span class="ml-3">
                                <span class="block text-sm font-medium text-secondary-900">Rebond</span>
                                <span class="block text-sm text-secondary-500">L'adresse email de ce contact est invalide</span>
                            </span>
                        </label>
                        <label v-if="contact.status === 'complained'" class="flex items-center opacity-50">
                            <input
                                type="radio"
                                v-model="form.status"
                                value="complained"
                                disabled
                                class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500"
                            />
                            <span class="ml-3">
                                <span class="block text-sm font-medium text-secondary-900">Plainte</span>
                                <span class="block text-sm text-secondary-500">Ce contact a signalé vos emails comme spam</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Lists -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Listes</h2>

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
                    <p v-else class="text-sm text-secondary-500">
                        Aucune liste disponible.
                    </p>
                </div>

                <!-- Tags -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-secondary-900 mb-4">Tags</h2>

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
                                class="inline-flex px-3 py-1.5 text-sm font-medium rounded-lg transition-colors peer-focus:ring-2 peer-focus:ring-offset-2"
                            >
                                {{ tag.name }}
                            </span>
                        </label>
                    </div>
                    <p v-else class="text-sm text-secondary-500">
                        Aucun tag disponible.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3">
                    <Link
                        href="/contacts"
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

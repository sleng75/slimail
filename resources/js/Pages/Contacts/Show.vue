<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    contact: Object,
});

const getStatusLabel = (status) => {
    const labels = {
        subscribed: 'Abonné',
        unsubscribed: 'Désabonné',
        bounced: 'Rebond',
        complained: 'Plainte',
    };
    return labels[status] || status;
};

const getStatusColor = (status) => {
    const colors = {
        subscribed: 'bg-green-100 text-green-800',
        unsubscribed: 'bg-gray-100 text-gray-800',
        bounced: 'bg-orange-100 text-orange-800',
        complained: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const deleteContact = () => {
    if (confirm(`Voulez-vous vraiment supprimer ${props.contact.full_name} ?`)) {
        router.delete(`/contacts/${props.contact.id}`);
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="contact.full_name" />

        <div class="max-w-4xl mx-auto">
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
            </div>

            <!-- Profile Header -->
            <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl font-bold text-primary-700">
                                {{ contact.first_name?.charAt(0) || contact.email.charAt(0).toUpperCase() }}{{ contact.last_name?.charAt(0) || '' }}
                            </span>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-2xl font-bold text-secondary-900">{{ contact.full_name }}</h1>
                            <p class="text-secondary-500">{{ contact.email }}</p>
                            <div class="mt-2">
                                <span :class="['px-2.5 py-1 text-sm font-medium rounded-full', getStatusColor(contact.status)]">
                                    {{ getStatusLabel(contact.status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Link
                            :href="`/contacts/${contact.id}/edit`"
                            class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50"
                        >
                            Modifier
                        </Link>
                        <button
                            @click="deleteContact"
                            class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Details -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-secondary-900 mb-4">Informations</h2>

                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="contact.phone">
                                <dt class="text-sm font-medium text-secondary-500">Téléphone</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ contact.phone }}</dd>
                            </div>
                            <div v-if="contact.company">
                                <dt class="text-sm font-medium text-secondary-500">Entreprise</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ contact.company }}</dd>
                            </div>
                            <div v-if="contact.job_title">
                                <dt class="text-sm font-medium text-secondary-500">Poste</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ contact.job_title }}</dd>
                            </div>
                            <div v-if="contact.city || contact.country">
                                <dt class="text-sm font-medium text-secondary-500">Localisation</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    {{ [contact.city, contact.country].filter(Boolean).join(', ') }}
                                </dd>
                            </div>
                            <div v-if="contact.address">
                                <dt class="text-sm font-medium text-secondary-500">Adresse</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ contact.address }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Source</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    {{ contact.source || 'Non spécifiée' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Email Stats -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-secondary-900 mb-4">Statistiques d'engagement</h2>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="text-2xl font-bold text-secondary-900">{{ contact.emails_sent }}</div>
                                <div class="text-sm text-secondary-500">Emails envoyés</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="text-2xl font-bold text-secondary-900">{{ contact.emails_opened }}</div>
                                <div class="text-sm text-secondary-500">Ouvertures</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="text-2xl font-bold text-secondary-900">{{ contact.emails_clicked }}</div>
                                <div class="text-sm text-secondary-500">Clics</div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-secondary-700">Score d'engagement</span>
                                <span class="text-sm font-semibold text-secondary-900">{{ contact.engagement_score }}%</span>
                            </div>
                            <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    :style="{ width: contact.engagement_score + '%' }"
                                    :class="[
                                        'h-full rounded-full transition-all',
                                        contact.engagement_score >= 70 ? 'bg-green-500' :
                                        contact.engagement_score >= 40 ? 'bg-yellow-500' : 'bg-red-500'
                                    ]"
                                ></div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <dt class="text-secondary-500">Dernier email envoyé</dt>
                                    <dd class="mt-1 font-medium text-secondary-900">{{ formatDate(contact.last_email_sent_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500">Dernière ouverture</dt>
                                    <dd class="mt-1 font-medium text-secondary-900">{{ formatDate(contact.last_email_opened_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-secondary-500">Dernier clic</dt>
                                    <dd class="mt-1 font-medium text-secondary-900">{{ formatDate(contact.last_email_clicked_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Lists -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-secondary-900 mb-4">Listes</h2>

                        <div v-if="contact.lists.length > 0" class="space-y-2">
                            <Link
                                v-for="list in contact.lists"
                                :key="list.id"
                                :href="`/contact-lists/${list.id}`"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-50"
                            >
                                <span
                                    v-if="list.color"
                                    :style="{ backgroundColor: list.color }"
                                    class="w-3 h-3 rounded-full mr-2"
                                ></span>
                                <span class="text-sm font-medium text-secondary-700">{{ list.name }}</span>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-secondary-500">
                            Ce contact n'appartient à aucune liste.
                        </p>
                    </div>

                    <!-- Tags -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-secondary-900 mb-4">Tags</h2>

                        <div v-if="contact.tags.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in contact.tags"
                                :key="tag.id"
                                :style="{ backgroundColor: tag.color + '20', color: tag.color }"
                                class="px-2.5 py-1 text-sm font-medium rounded-lg"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                        <p v-else class="text-sm text-secondary-500">
                            Aucun tag assigné.
                        </p>
                    </div>

                    <!-- Dates -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-secondary-900 mb-4">Historique</h2>

                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-secondary-500">Créé le</dt>
                                <dd class="mt-0.5 font-medium text-secondary-900">{{ formatDate(contact.created_at) }}</dd>
                            </div>
                            <div v-if="contact.subscribed_at">
                                <dt class="text-secondary-500">Inscrit le</dt>
                                <dd class="mt-0.5 font-medium text-secondary-900">{{ formatDate(contact.subscribed_at) }}</dd>
                            </div>
                            <div v-if="contact.unsubscribed_at">
                                <dt class="text-secondary-500">Désinscrit le</dt>
                                <dd class="mt-0.5 font-medium text-secondary-900">{{ formatDate(contact.unsubscribed_at) }}</dd>
                            </div>
                            <div v-if="contact.unsubscribe_reason">
                                <dt class="text-secondary-500">Raison</dt>
                                <dd class="mt-0.5 font-medium text-secondary-900">{{ contact.unsubscribe_reason }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

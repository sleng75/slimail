<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    users: Object,
    stats: Object,
    roles: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const showDeleteModal = ref(false);
const userToDelete = ref(null);

// Debounced search
const performSearch = debounce(() => {
    router.get('/users', {
        search: search.value || undefined,
        role: roleFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, roleFilter], () => {
    performSearch();
});

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const deleteUser = () => {
    router.delete(`/users/${userToDelete.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            userToDelete.value = null;
        },
    });
};

const getRoleColor = (role) => {
    const colors = {
        owner: 'bg-purple-100 text-purple-800',
        admin: 'bg-blue-100 text-blue-800',
        editor: 'bg-green-100 text-green-800',
        analyst: 'bg-yellow-100 text-yellow-800',
        developer: 'bg-orange-100 text-orange-800',
    };
    return colors[role] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    if (!dateString) return 'Jamais';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getInitials = (name) => {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().substring(0, 2);
};

const currentUser = usePage().props.auth?.user;
</script>

<template>
    <AppLayout>
        <Head title="Équipe" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Équipe</h1>
                    <p class="mt-1 text-sm text-gray-500">Gérez les membres de votre équipe et leurs permissions</p>
                </div>
                <Link
                    href="/users/create"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                >
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Inviter un membre
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-gray-500">Total</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-purple-600">Propriétaires</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.owners }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-blue-600">Admins</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.admins }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-green-600">Éditeurs</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.editors }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-yellow-600">Analystes</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.analysts }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-medium text-orange-600">Développeurs</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.developers }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-64">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher par nom ou email..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>

                    <!-- Role Filter -->
                    <select
                        v-model="roleFilter"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">Tous les rôles</option>
                        <option v-for="(label, value) in roles" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Users List -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="users.data.length === 0" class="p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Aucun membre trouvé</h3>
                    <p class="mt-2 text-gray-500">Invitez des membres à rejoindre votre équipe.</p>
                    <Link
                        href="/users/create"
                        class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                    >
                        Inviter un membre
                    </Link>
                </div>

                <table v-else class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Membre
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rôle
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dernière connexion
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Créé le
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div v-if="user.avatar" class="w-10 h-10 rounded-full overflow-hidden">
                                        <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary-700">{{ getInitials(user.name) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900">{{ user.name }}</span>
                                            <span v-if="user.id === currentUser?.id" class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                                                Vous
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['px-3 py-1 text-xs font-medium rounded-full', getRoleColor(user.role)]">
                                    {{ roles[user.role] || user.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(user.last_login_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="`/users/${user.id}/edit`"
                                        class="p-2 text-gray-400 hover:text-primary-600 hover:bg-gray-100 rounded-lg"
                                        title="Modifier"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        v-if="user.id !== currentUser?.id"
                                        @click="confirmDelete(user)"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-gray-100 rounded-lg"
                                        title="Supprimer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Affichage de {{ users.from }} à {{ users.to }} sur {{ users.total }} membres
                        </div>
                        <div class="flex items-center gap-2">
                            <Link
                                v-if="users.prev_page_url"
                                :href="users.prev_page_url"
                                class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
                                preserve-scroll
                            >
                                Précédent
                            </Link>
                            <Link
                                v-if="users.next_page_url"
                                :href="users.next_page_url"
                                class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
                                preserve-scroll
                            >
                                Suivant
                            </Link>
                        </div>
                    </div>
                </div>
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
                        Êtes-vous sûr de vouloir supprimer <strong>{{ userToDelete?.name }}</strong> de l'équipe ?
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

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    notifications: Object,
    unreadCount: Number,
});

const markAsRead = (id) => {
    router.post(`/notifications/${id}/read`, {}, {
        preserveScroll: true,
    });
};

const markAllAsRead = () => {
    router.post('/notifications/mark-all-read', {}, {
        preserveScroll: true,
    });
};

const deleteNotification = (id) => {
    router.delete(`/notifications/${id}`, {
        preserveScroll: true,
    });
};

const deleteAllRead = () => {
    router.delete('/notifications/read', {
        preserveScroll: true,
    });
};

const getNotificationIcon = (type) => {
    if (type.includes('Campaign')) return 'campaign';
    if (type.includes('Contact')) return 'contact';
    if (type.includes('Payment') || type.includes('Invoice')) return 'payment';
    if (type.includes('Subscription')) return 'subscription';
    return 'default';
};

const getNotificationColor = (type) => {
    const colors = {
        campaign: 'bg-blue-100 text-blue-600',
        contact: 'bg-green-100 text-green-600',
        payment: 'bg-purple-100 text-purple-600',
        subscription: 'bg-amber-100 text-amber-600',
        default: 'bg-gray-100 text-gray-600',
    };
    return colors[getNotificationIcon(type)] || colors.default;
};
</script>

<template>
    <AppLayout>
        <Head title="Notifications" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        <span v-if="unreadCount > 0">{{ unreadCount }} notification(s) non lue(s)</span>
                        <span v-else>Aucune notification non lue</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Tout marquer comme lu
                    </button>
                    <button
                        v-if="notifications.data?.some(n => n.read_at)"
                        @click="deleteAllRead"
                        class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50"
                    >
                        Supprimer les lues
                    </button>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="notifications.data?.length > 0" class="divide-y divide-gray-100">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        :class="[
                            'flex items-start gap-4 p-4 transition-colors',
                            !notification.read_at ? 'bg-blue-50/50' : 'bg-white hover:bg-gray-50'
                        ]"
                    >
                        <!-- Icon -->
                        <div :class="['p-2 rounded-lg', getNotificationColor(notification.type)]">
                            <!-- Campaign Icon -->
                            <svg v-if="getNotificationIcon(notification.type) === 'campaign'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <!-- Contact Icon -->
                            <svg v-else-if="getNotificationIcon(notification.type) === 'contact'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <!-- Payment Icon -->
                            <svg v-else-if="getNotificationIcon(notification.type) === 'payment'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <!-- Subscription Icon -->
                            <svg v-else-if="getNotificationIcon(notification.type) === 'subscription'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                            <!-- Default Icon -->
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ notification.data?.title || 'Notification' }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ notification.data?.message || '' }}
                                    </p>
                                    <p class="mt-2 text-xs text-gray-400">
                                        {{ notification.time_ago }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="!notification.read_at"
                                        @click="markAsRead(notification.id)"
                                        class="p-1 text-gray-400 hover:text-blue-600 rounded"
                                        title="Marquer comme lu"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteNotification(notification.id)"
                                        class="p-1 text-gray-400 hover:text-red-600 rounded"
                                        title="Supprimer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Action Link -->
                            <Link
                                v-if="notification.data?.action_url"
                                :href="notification.data.action_url"
                                class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-primary-600 hover:text-primary-700"
                            >
                                {{ notification.data?.action_text || 'Voir' }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>

                        <!-- Unread indicator -->
                        <div v-if="!notification.read_at" class="w-2 h-2 mt-2 bg-blue-500 rounded-full"></div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-16 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-gray-100 rounded-full">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Aucune notification</h3>
                    <p class="mt-1 text-sm text-gray-500">Vous n'avez pas encore de notifications.</p>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.data?.length > 0 && notifications.links?.length > 3" class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <nav class="flex items-center justify-between">
                        <p class="text-sm text-gray-700">
                            Affichage de
                            <span class="font-medium">{{ notifications.from }}</span>
                            à
                            <span class="font-medium">{{ notifications.to }}</span>
                            sur
                            <span class="font-medium">{{ notifications.total }}</span>
                            résultats
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in notifications.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded-lg',
                                    link.active ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

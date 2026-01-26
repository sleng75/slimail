<script setup>
/**
 * HeaderNotifications Component
 * Notifications dropdown with badge
 */

import { ref, computed, onMounted, onUnmounted } from 'vue';

const isOpen = ref(false);
const dropdownRef = ref(null);

// Mock notifications (in real app, this would come from props or API)
const notifications = ref([
    {
        id: 1,
        type: 'campaign',
        title: 'Campagne envoyée',
        message: 'Votre campagne "Newsletter Mars" a été envoyée à 1,234 contacts.',
        time: '2 min',
        read: false,
    },
    {
        id: 2,
        type: 'contact',
        title: 'Nouveaux contacts importés',
        message: '150 contacts ont été importés avec succès.',
        time: '1 heure',
        read: false,
    },
    {
        id: 3,
        type: 'alert',
        title: 'Taux de rebond élevé',
        message: 'Le taux de rebond de votre dernière campagne est supérieur à 5%.',
        time: '3 heures',
        read: true,
    },
]);

// Unread count
const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.read).length;
});

// Toggle dropdown
const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

// Mark notification as read
const markAsRead = (notification) => {
    notification.read = true;
};

// Mark all as read
const markAllAsRead = () => {
    notifications.value.forEach(n => n.read = true);
};

// Get icon based on notification type
const getIcon = (type) => {
    switch (type) {
        case 'campaign':
            return 'mail';
        case 'contact':
            return 'users';
        case 'alert':
            return 'alert';
        default:
            return 'bell';
    }
};

// Get icon background color
const getIconBg = (type) => {
    switch (type) {
        case 'campaign':
            return 'bg-primary-100 text-primary-600';
        case 'contact':
            return 'bg-emerald-100 text-emerald-600';
        case 'alert':
            return 'bg-amber-100 text-amber-600';
        default:
            return 'bg-secondary-100 text-secondary-600';
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Trigger Button -->
        <button
            @click="toggleDropdown"
            class="relative p-2.5 text-secondary-400 hover:text-secondary-600 rounded-xl hover:bg-gray-100 transition-colors"
            :class="{ 'bg-gray-100 text-secondary-600': isOpen }"
            title="Notifications"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <!-- Badge -->
            <span
                v-if="unreadCount > 0"
                class="notification-badge"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-dropdown border border-secondary-200/60 overflow-hidden z-dropdown"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-secondary-100">
                    <h3 class="text-sm font-semibold text-secondary-900">Notifications</h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-xs text-primary-600 hover:text-primary-700 font-medium"
                    >
                        Tout marquer comme lu
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="max-h-80 overflow-y-auto custom-scrollbar">
                    <div v-if="notifications.length > 0">
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            @click="markAsRead(notification)"
                            :class="[
                                'flex items-start gap-3 px-4 py-3 hover:bg-secondary-50 transition-colors cursor-pointer',
                                !notification.read ? 'bg-primary-50/50' : '',
                            ]"
                        >
                            <!-- Icon -->
                            <div :class="['w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0', getIconBg(notification.type)]">
                                <svg v-if="getIcon(notification.type) === 'mail'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <svg v-else-if="getIcon(notification.type) === 'users'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg v-else-if="getIcon(notification.type) === 'alert'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p :class="['text-sm truncate', !notification.read ? 'font-semibold text-secondary-900' : 'font-medium text-secondary-700']">
                                        {{ notification.title }}
                                    </p>
                                    <span class="text-xs text-secondary-400 flex-shrink-0">{{ notification.time }}</span>
                                </div>
                                <p class="text-xs text-secondary-500 mt-0.5 line-clamp-2">
                                    {{ notification.message }}
                                </p>
                            </div>

                            <!-- Unread indicator -->
                            <div v-if="!notification.read" class="w-2 h-2 bg-primary-500 rounded-full flex-shrink-0 mt-2" />
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="py-8 px-4 text-center">
                        <svg class="w-12 h-12 text-secondary-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <p class="text-sm text-secondary-500">Aucune notification</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 bg-secondary-50 border-t border-secondary-100">
                    <a
                        href="/notifications"
                        class="block text-center text-sm font-medium text-primary-600 hover:text-primary-700"
                        @click="isOpen = false"
                    >
                        Voir toutes les notifications
                    </a>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

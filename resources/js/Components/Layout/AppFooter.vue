<script setup>
/**
 * AppFooter Component
 * Professional footer with links, status indicator, and feedback
 */

import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

// App info
const appVersion = computed(() => page.props.app?.version || '1.0.0');
const currentYear = new Date().getFullYear();

// System status
const systemStatus = ref('operational'); // operational, degraded, outage
const statusChecking = ref(false);

const statusConfig = computed(() => {
    const configs = {
        operational: {
            color: 'bg-emerald-500',
            text: 'Opérationnel',
            textColor: 'text-emerald-600',
            bgColor: 'bg-emerald-50',
        },
        degraded: {
            color: 'bg-amber-500',
            text: 'Dégradé',
            textColor: 'text-amber-600',
            bgColor: 'bg-amber-50',
        },
        outage: {
            color: 'bg-red-500',
            text: 'Incident',
            textColor: 'text-red-600',
            bgColor: 'bg-red-50',
        },
    };
    return configs[systemStatus.value] || configs.operational;
});

// Quick links
const quickLinks = [
    { label: 'Documentation', href: 'https://docs.slimail.com', external: true },
    { label: 'Support', href: '/support', external: false },
    { label: 'API', href: 'https://api.slimail.com/docs', external: true },
    { label: 'Changelog', href: '/changelog', external: false },
];

// Feedback modal
const showFeedback = ref(false);
const feedbackType = ref('suggestion');
const feedbackMessage = ref('');
const feedbackSubmitting = ref(false);
const feedbackSuccess = ref(false);

const feedbackTypes = [
    { value: 'bug', label: 'Bug', icon: '🐛' },
    { value: 'suggestion', label: 'Suggestion', icon: '💡' },
    { value: 'question', label: 'Question', icon: '❓' },
    { value: 'other', label: 'Autre', icon: '📝' },
];

const openFeedback = () => {
    showFeedback.value = true;
    feedbackSuccess.value = false;
};

const closeFeedback = () => {
    showFeedback.value = false;
    feedbackMessage.value = '';
    feedbackType.value = 'suggestion';
};

const submitFeedback = async () => {
    if (!feedbackMessage.value.trim()) return;

    feedbackSubmitting.value = true;

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));

    feedbackSubmitting.value = false;
    feedbackSuccess.value = true;
    feedbackMessage.value = '';

    // Auto close after success
    setTimeout(() => {
        closeFeedback();
    }, 2000);
};

// Keyboard shortcut to close modal
const handleKeydown = (e) => {
    if (e.key === 'Escape' && showFeedback.value) {
        closeFeedback();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <footer class="fixed bottom-0 right-0 z-20 bg-white border-t border-gray-100 h-footer transition-all duration-300">
        <div class="h-full px-page flex items-center justify-between">
            <!-- Left section - Copyright & Version -->
            <div class="flex items-center gap-4 text-sm">
                <span class="text-secondary-500">
                    © {{ currentYear }} SliMail
                </span>
                <Link
                    href="/changelog"
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-medium text-secondary-600 bg-secondary-100 hover:bg-secondary-200 rounded-full transition-colors"
                >
                    <span>v{{ appVersion }}</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </Link>
            </div>

            <!-- Center section - Quick Links (desktop) -->
            <nav class="hidden md:flex items-center gap-1">
                <template v-for="(link, index) in quickLinks" :key="link.label">
                    <component
                        :is="link.external ? 'a' : Link"
                        :href="link.href"
                        :target="link.external ? '_blank' : undefined"
                        :rel="link.external ? 'noopener noreferrer' : undefined"
                        class="px-3 py-1.5 text-sm text-secondary-500 hover:text-secondary-700 hover:bg-gray-50 rounded-lg transition-colors"
                    >
                        {{ link.label }}
                        <svg
                            v-if="link.external"
                            class="inline-block w-3 h-3 ml-0.5 opacity-50"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </component>
                    <span
                        v-if="index < quickLinks.length - 1"
                        class="text-gray-200"
                    >·</span>
                </template>
            </nav>

            <!-- Right section - Status & Feedback -->
            <div class="flex items-center gap-3">
                <!-- System Status -->
                <a
                    href="https://status.slimail.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="[
                        'hidden sm:inline-flex items-center gap-2 px-2.5 py-1 rounded-lg text-xs font-medium transition-colors',
                        statusConfig.bgColor,
                        statusConfig.textColor,
                        'hover:opacity-80'
                    ]"
                >
                    <span
                        :class="[
                            'w-2 h-2 rounded-full',
                            statusConfig.color,
                            systemStatus === 'operational' ? 'animate-pulse' : ''
                        ]"
                    />
                    <span>{{ statusConfig.text }}</span>
                </a>

                <!-- Feedback Button -->
                <button
                    @click="openFeedback"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="hidden sm:inline">Feedback</span>
                </button>
            </div>
        </div>

        <!-- Feedback Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showFeedback"
                    class="fixed inset-0 z-modal bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-4"
                    @click.self="closeFeedback"
                >
                    <Transition
                        enter-active-class="transition-all duration-200"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="transition-all duration-150"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        appear
                    >
                        <div
                            v-if="showFeedback"
                            class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                                <h3 class="text-lg font-semibold text-secondary-900">
                                    Envoyer un feedback
                                </h3>
                                <button
                                    @click="closeFeedback"
                                    class="p-1.5 text-secondary-400 hover:text-secondary-600 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="p-6">
                                <!-- Success State -->
                                <div v-if="feedbackSuccess" class="text-center py-8">
                                    <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-secondary-900 mb-1">
                                        Merci pour votre feedback !
                                    </h4>
                                    <p class="text-secondary-500">
                                        Nous l'avons bien reçu et l'examinerons attentivement.
                                    </p>
                                </div>

                                <!-- Form -->
                                <form v-else @submit.prevent="submitFeedback" class="space-y-4">
                                    <!-- Type Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-700 mb-2">
                                            Type de feedback
                                        </label>
                                        <div class="grid grid-cols-4 gap-2">
                                            <button
                                                v-for="type in feedbackTypes"
                                                :key="type.value"
                                                type="button"
                                                @click="feedbackType = type.value"
                                                :class="[
                                                    'flex flex-col items-center gap-1 p-3 rounded-xl border-2 transition-all',
                                                    feedbackType === type.value
                                                        ? 'border-primary-500 bg-primary-50 text-primary-700'
                                                        : 'border-gray-200 hover:border-gray-300 text-secondary-600'
                                                ]"
                                            >
                                                <span class="text-xl">{{ type.icon }}</span>
                                                <span class="text-xs font-medium">{{ type.label }}</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Message -->
                                    <div>
                                        <label for="feedback-message" class="block text-sm font-medium text-secondary-700 mb-2">
                                            Votre message
                                        </label>
                                        <textarea
                                            id="feedback-message"
                                            v-model="feedbackMessage"
                                            rows="4"
                                            placeholder="Décrivez votre feedback en détail..."
                                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-secondary-900 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none transition-shadow"
                                            required
                                        />
                                    </div>

                                    <!-- Submit Button -->
                                    <button
                                        type="submit"
                                        :disabled="feedbackSubmitting || !feedbackMessage.trim()"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white font-medium rounded-xl transition-colors"
                                    >
                                        <svg
                                            v-if="feedbackSubmitting"
                                            class="w-5 h-5 animate-spin"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        <span>{{ feedbackSubmitting ? 'Envoi en cours...' : 'Envoyer le feedback' }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </footer>
</template>

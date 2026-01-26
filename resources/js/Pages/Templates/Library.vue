<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    templates: Object,
    categories: Object,
});

const selectedCategory = ref('all');
const showPreviewModal = ref(false);
const previewTemplate = ref(null);

const filteredTemplates = computed(() => {
    if (selectedCategory.value === 'all') {
        return props.templates;
    }
    return { [selectedCategory.value]: props.templates[selectedCategory.value] || [] };
});

const hasTemplates = computed(() => {
    return Object.values(props.templates).some(arr => arr.length > 0);
});

const previewInModal = (template) => {
    previewTemplate.value = template;
    showPreviewModal.value = true;
};

const useTemplate = (template) => {
    router.post(`/templates/${template.id}/use`);
};

const getCategoryColor = (category) => {
    const colors = {
        newsletter: 'bg-blue-500',
        promotional: 'bg-green-500',
        transactional: 'bg-purple-500',
        notification: 'bg-yellow-500',
        welcome: 'bg-pink-500',
        abandoned_cart: 'bg-orange-500',
        event: 'bg-indigo-500',
        survey: 'bg-teal-500',
        other: 'bg-gray-500',
    };
    return colors[category] || colors.other;
};
</script>

<template>
    <AppLayout>
        <Head title="Bibliothèque de templates" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <nav class="flex items-center text-sm text-gray-500 mb-2">
                            <Link href="/templates" class="hover:text-gray-700">Templates</Link>
                            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="text-gray-900">Bibliothèque</span>
                        </nav>
                        <h1 class="text-2xl font-bold text-gray-900">Bibliothèque de templates</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Choisissez parmi nos modèles pré-conçus et personnalisez-les selon vos besoins.
                        </p>
                    </div>
                    <Link
                        href="/templates"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour
                    </Link>
                </div>

                <!-- Category Filter -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex items-center space-x-2 overflow-x-auto">
                        <button
                            @click="selectedCategory = 'all'"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors',
                                selectedCategory === 'all'
                                    ? 'bg-primary-100 text-primary-700'
                                    : 'text-gray-600 hover:bg-gray-100'
                            ]"
                        >
                            Tous
                        </button>
                        <button
                            v-for="(label, key) in categories"
                            :key="key"
                            @click="selectedCategory = key"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors',
                                selectedCategory === key
                                    ? 'bg-primary-100 text-primary-700'
                                    : 'text-gray-600 hover:bg-gray-100'
                            ]"
                        >
                            {{ label }}
                        </button>
                    </div>
                </div>

                <!-- Templates by Category -->
                <div v-if="hasTemplates" class="space-y-8">
                    <div
                        v-for="(categoryTemplates, categoryKey) in filteredTemplates"
                        :key="categoryKey"
                        v-show="categoryTemplates && categoryTemplates.length > 0"
                    >
                        <!-- Category Header -->
                        <div class="flex items-center space-x-3 mb-4">
                            <div :class="['w-1 h-6 rounded-full', getCategoryColor(categoryKey)]"></div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                {{ categories[categoryKey] || categoryKey }}
                            </h2>
                            <span class="text-sm text-gray-500">({{ categoryTemplates.length }})</span>
                        </div>

                        <!-- Templates Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <div
                                v-for="template in categoryTemplates"
                                :key="template.id"
                                class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group"
                            >
                                <!-- Thumbnail -->
                                <div class="aspect-[4/3] bg-gray-100 relative overflow-hidden">
                                    <img
                                        v-if="template.thumbnail"
                                        :src="template.thumbnail"
                                        :alt="template.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>

                                    <!-- Hover Overlay -->
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                        <button
                                            @click="previewInModal(template)"
                                            class="px-3 py-1.5 bg-white text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors mr-2"
                                        >
                                            Aperçu
                                        </button>
                                        <button
                                            @click="useTemplate(template)"
                                            class="px-3 py-1.5 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors"
                                        >
                                            Utiliser
                                        </button>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="p-4">
                                    <h3 class="text-sm font-semibold text-gray-900 truncate">
                                        {{ template.name }}
                                    </h3>
                                    <p v-if="template.description" class="mt-1 text-xs text-gray-500 line-clamp-2">
                                        {{ template.description }}
                                    </p>
                                    <div class="mt-3 flex items-center justify-between">
                                        <span class="text-xs text-gray-400">
                                            {{ template.usage_count || 0 }} utilisations
                                        </span>
                                        <button
                                            @click="useTemplate(template)"
                                            class="text-xs font-medium text-primary-600 hover:text-primary-700"
                                        >
                                            Utiliser ce template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Aucun template dans la bibliothèque</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        La bibliothèque sera bientôt enrichie de nouveaux modèles.
                    </p>
                    <div class="mt-6">
                        <Link
                            href="/templates/create"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700"
                        >
                            Créer votre propre template
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <Teleport to="body">
            <div v-if="showPreviewModal && previewTemplate" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black bg-opacity-50" @click="showPreviewModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-4 border-b border-gray-200">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ previewTemplate.name }}</h3>
                                <p v-if="previewTemplate.description" class="text-sm text-gray-500">
                                    {{ previewTemplate.description }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="useTemplate(previewTemplate); showPreviewModal = false;"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700"
                                >
                                    Utiliser ce template
                                </button>
                                <button
                                    @click="showPreviewModal = false"
                                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Preview Content -->
                        <div class="p-4 bg-gray-100 overflow-auto" style="max-height: calc(90vh - 100px);">
                            <div class="bg-white rounded-lg shadow-sm mx-auto max-w-2xl">
                                <div
                                    v-if="previewTemplate.html_content"
                                    v-html="previewTemplate.html_content"
                                    class="p-4"
                                ></div>
                                <div v-else class="p-12 text-center text-gray-400">
                                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <p>Aperçu non disponible</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

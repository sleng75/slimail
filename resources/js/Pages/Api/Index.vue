<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    apiKeys: Array,
    availablePermissions: Object,
    stats: {
        type: Object,
        default: () => ({
            total_requests: 0,
            today_requests: 0,
            success_rate: 100,
            avg_response_time: 0
        })
    }
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showNewKeyModal = ref(false);
const editingKey = ref(null);
const deletingKey = ref(null);
const newApiKey = ref(null);
const copiedKey = ref(false);
const activeCodeTab = ref('curl');

// Check for flash data with new API key
onMounted(() => {
    const flashNewKey = usePage().props.flash?.newApiKey;
    if (flashNewKey) {
        newApiKey.value = flashNewKey;
        showNewKeyModal.value = true;
    }
});

const createForm = useForm({
    name: '',
    permissions: ['send'],
    ip_whitelist: '',
    rate_limit: '',
    expires_at: '',
});

const editForm = useForm({
    name: '',
    permissions: [],
    ip_whitelist: '',
    rate_limit: '',
    expires_at: '',
    is_active: true,
});

const codeTabs = [
    { id: 'curl', label: 'cURL' },
    { id: 'php', label: 'PHP' },
    { id: 'javascript', label: 'JavaScript' },
    { id: 'python', label: 'Python' },
];

const appUrl = computed(() => usePage().props.appUrl || 'https://api.slimail.com');

const codeExamples = computed(() => ({
    curl: `curl -X POST ${appUrl.value}/api/v1/send \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -d '{
    "from_email": "noreply@votredomaine.com",
    "from_name": "Mon Application",
    "to_email": "destinataire@exemple.com",
    "to_name": "Jean Dupont",
    "subject": "Bienvenue sur notre plateforme !",
    "html_content": "<h1>Bonjour</h1><p>Bienvenue !</p>",
    "tags": ["welcome", "onboarding"]
  }'`,
    php: `<?php
use GuzzleHttp\\Client;

$client = new Client();

$response = $client->post('${appUrl.value}/api/v1/send', [
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Content-Type' => 'application/json',
    ],
    'json' => [
        'from_email' => 'noreply@votredomaine.com',
        'from_name' => 'Mon Application',
        'to_email' => 'destinataire@exemple.com',
        'to_name' => 'Jean Dupont',
        'subject' => 'Bienvenue sur notre plateforme !',
        'html_content' => '<h1>Bonjour</h1><p>Bienvenue !</p>',
        'tags' => ['welcome', 'onboarding'],
    ],
]);

$result = json_decode($response->getBody(), true);`,
    javascript: `const response = await fetch('${appUrl.value}/api/v1/send', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    from_email: 'noreply@votredomaine.com',
    from_name: 'Mon Application',
    to_email: 'destinataire@exemple.com',
    to_name: 'Jean Dupont',
    subject: 'Bienvenue sur notre plateforme !',
    html_content: '<h1>Bonjour</h1><p>Bienvenue !</p>',
    tags: ['welcome', 'onboarding'],
  }),
});

const result = await response.json();`,
    python: `import requests

response = requests.post(
    '${appUrl.value}/api/v1/send',
    headers={
        'Authorization': 'Bearer YOUR_API_KEY',
        'Content-Type': 'application/json',
    },
    json={
        'from_email': 'noreply@votredomaine.com',
        'from_name': 'Mon Application',
        'to_email': 'destinataire@exemple.com',
        'to_name': 'Jean Dupont',
        'subject': 'Bienvenue sur notre plateforme !',
        'html_content': '<h1>Bonjour</h1><p>Bienvenue !</p>',
        'tags': ['welcome', 'onboarding'],
    }
)

result = response.json()`,
}));

const submitCreate = () => {
    createForm.post('/api-settings', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const openEditModal = (key) => {
    editingKey.value = key;
    editForm.name = key.name;
    editForm.permissions = key.permissions || [];
    editForm.ip_whitelist = key.ip_whitelist?.join(', ') || '';
    editForm.rate_limit = key.rate_limit || '';
    editForm.expires_at = key.expires_at || '';
    editForm.is_active = key.is_active;
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(`/api-settings/${editingKey.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingKey.value = null;
        },
    });
};

const confirmDelete = (key) => {
    deletingKey.value = key;
    showDeleteModal.value = true;
};

const deleteKey = () => {
    router.delete(`/api-settings/${deletingKey.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingKey.value = null;
        },
    });
};

const regenerateKey = (key) => {
    if (confirm('Êtes-vous sûr de vouloir régénérer cette clé ? L\'ancienne clé sera invalidée immédiatement.')) {
        router.post(`/api-settings/${key.id}/regenerate`, {}, {
            preserveScroll: true,
        });
    }
};

const toggleKey = (key) => {
    router.post(`/api-settings/${key.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

const copyToClipboard = async (text) => {
    await navigator.clipboard.writeText(text);
    copiedKey.value = true;
    setTimeout(() => copiedKey.value = false, 2000);
};

const copyCode = async () => {
    await navigator.clipboard.writeText(codeExamples.value[activeCodeTab.value]);
};

const getPermissionLabel = (permission) => {
    return props.availablePermissions[permission] || permission;
};

const getPermissionIcon = (permission) => {
    const icons = {
        send: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        templates: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z',
        contacts: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        stats: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        webhooks: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
    };
    return icons[permission] || 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
};

const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num?.toString() || '0';
};

const activeKeysCount = computed(() => props.apiKeys.filter(k => k.is_active).length);
</script>

<template>
    <Head title="API & Intégrations" />

    <AppLayout>
        <PageContainer size="wide">
            <div class="space-y-section">
                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-2">
                        <h1 class="text-2xl lg:text-3xl font-bold text-secondary-900">
                            API & Intégrations
                        </h1>
                        <p class="text-secondary-500">
                            Envoyez des emails transactionnels depuis vos applications.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('api.docs')"
                            class="btn btn-secondary"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            Documentation
                        </Link>
                        <button
                            @click="showCreateModal = true"
                            class="btn btn-primary"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouvelle clé API
                        </button>
                    </div>
                </div>

                <!-- Stats cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="card card-hover group">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center group-hover:bg-primary-200 transition-colors">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-secondary-500">Clés actives</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ activeKeysCount }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card card-hover group">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-secondary-500">Requêtes (24h)</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ formatNumber(stats.today_requests) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card card-hover group">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-secondary-500">Taux de succès</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ stats.success_rate }}%</p>
                            </div>
                        </div>
                    </div>

                    <div class="card card-hover group">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-secondary-500">Total requêtes</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ formatNumber(stats.total_requests) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Status -->
                <div class="flex items-center gap-4 p-4 bg-emerald-50 border border-emerald-100 rounded-xl">
                    <div class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium text-emerald-700">API opérationnelle</span>
                    <span class="text-sm text-emerald-600">Latence ~50ms</span>
                    <span class="text-sm text-emerald-600">99.9% SLA</span>
                </div>

                <!-- Features -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="card card-hover group">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center group-hover:bg-primary-200 transition-colors flex-shrink-0">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-secondary-900">Emails Transactionnels</h3>
                                <p class="text-sm text-secondary-500 mt-1">Envoyez des emails de confirmation, réinitialisation de mot de passe, notifications.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card card-hover group">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center group-hover:bg-violet-200 transition-colors flex-shrink-0">
                                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-secondary-900">Statistiques Temps Réel</h3>
                                <p class="text-sm text-secondary-500 mt-1">Suivez les ouvertures, clics et rebonds via webhooks ou notre dashboard.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card card-hover group">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-200 transition-colors flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-secondary-900">Haute Délivrabilité</h3>
                                <p class="text-sm text-secondary-500 mt-1">Infrastructure optimisée avec DKIM, SPF et DMARC.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Keys Section -->
                <div class="card">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-secondary-900">Vos clés API</h2>
                            <p class="text-sm text-secondary-500 mt-1">Gérez vos clés d'accès à l'API</p>
                        </div>
                        <button
                            @click="showCreateModal = true"
                            class="text-sm font-medium text-primary-600 hover:text-primary-700"
                        >
                            + Ajouter
                        </button>
                    </div>

                    <!-- Empty State -->
                    <div v-if="apiKeys.length === 0" class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto">
                            <svg class="w-8 h-8 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-sm font-medium text-secondary-900">Aucune clé API</h3>
                        <p class="mt-1 text-sm text-secondary-500">
                            Créez votre première clé API pour commencer à envoyer des emails.
                        </p>
                        <div class="mt-6">
                            <button
                                @click="showCreateModal = true"
                                class="btn btn-primary"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Créer ma première clé
                            </button>
                        </div>
                    </div>

                    <!-- API Keys List -->
                    <div v-else class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="key in apiKeys"
                                :key="key.id"
                                class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-medium text-secondary-900">{{ key.name }}</h4>
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full',
                                                    key.is_active
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-gray-200 text-secondary-600'
                                                ]"
                                            >
                                                {{ key.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1">
                                            <code class="px-2 py-0.5 bg-white rounded text-xs font-mono text-secondary-600 border">
                                                {{ key.key_preview }}
                                            </code>
                                            <span class="text-xs text-secondary-500">
                                                {{ formatNumber(key.usage_count || 0) }} requêtes
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="flex flex-wrap gap-1 mr-4">
                                        <span
                                            v-for="perm in key.permissions"
                                            :key="perm"
                                            class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-primary-50 text-primary-700 rounded"
                                        >
                                            {{ getPermissionLabel(perm) }}
                                        </span>
                                    </div>

                                    <button
                                        @click="toggleKey(key)"
                                        :class="[
                                            'p-2 rounded-lg transition-colors',
                                            key.is_active
                                                ? 'text-amber-600 hover:bg-amber-50'
                                                : 'text-emerald-600 hover:bg-emerald-50'
                                        ]"
                                        :title="key.is_active ? 'Désactiver' : 'Activer'"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="key.is_active" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openEditModal(key)"
                                        class="p-2 text-secondary-400 hover:text-secondary-600 rounded-lg hover:bg-gray-200 transition-colors"
                                        title="Modifier"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="regenerateKey(key)"
                                        class="p-2 text-secondary-400 hover:text-primary-600 rounded-lg hover:bg-primary-50 transition-colors"
                                        title="Régénérer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(key)"
                                        class="p-2 text-secondary-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                                        title="Supprimer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Code Examples -->
                <div class="card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-secondary-900">
                        <div>
                            <h3 class="text-lg font-semibold text-white">Exemple d'utilisation</h3>
                            <p class="text-sm text-secondary-400 mt-0.5">Intégrez notre API en quelques minutes</p>
                        </div>
                        <button
                            @click="copyCode"
                            class="inline-flex items-center px-3 py-1.5 text-sm text-secondary-300 hover:text-white hover:bg-secondary-800 rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Copier
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="px-6 pt-4 flex gap-2 border-b border-gray-100 bg-secondary-900">
                        <button
                            v-for="tab in codeTabs"
                            :key="tab.id"
                            @click="activeCodeTab = tab.id"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-t-lg transition-colors -mb-px',
                                activeCodeTab === tab.id
                                    ? 'bg-secondary-800 text-white border-b-2 border-primary-500'
                                    : 'text-secondary-400 hover:text-secondary-200'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <div class="p-6 bg-secondary-900">
                        <pre class="text-sm text-emerald-400 font-mono overflow-x-auto leading-relaxed"><code>{{ codeExamples[activeCodeTab] }}</code></pre>
                    </div>

                    <!-- Endpoint info -->
                    <div class="px-6 py-4 bg-secondary-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 bg-emerald-500/20 text-emerald-400 text-xs font-bold rounded">POST</span>
                            <code class="text-sm text-secondary-300 font-mono">{{ appUrl }}/api/v1/send</code>
                        </div>
                        <Link
                            :href="route('api.docs')"
                            class="inline-flex items-center text-sm text-primary-400 hover:text-primary-300"
                        >
                            Voir tous les endpoints
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="card">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-secondary-900">Besoin d'aide ?</h4>
                                <p class="text-sm text-secondary-500 mt-1 mb-3">Consultez notre documentation ou contactez le support.</p>
                                <div class="flex gap-4">
                                    <Link
                                        :href="route('api.docs')"
                                        class="text-sm font-medium text-primary-600 hover:text-primary-700"
                                    >
                                        Documentation
                                    </Link>
                                    <a href="mailto:support@slimail.com" class="text-sm font-medium text-secondary-600 hover:text-secondary-700">
                                        Contacter le support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-secondary-900">Bonnes pratiques</h4>
                                <p class="text-sm text-secondary-500 mt-1 mb-3">Gardez vos clés API confidentielles.</p>
                                <ul class="text-sm text-secondary-600 space-y-1">
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Utilisez des variables d'environnement
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Limitez les IPs autorisées
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h2 class="text-lg font-semibold text-secondary-900">Créer une clé API</h2>
                            <p class="text-sm text-secondary-500">Configurez les paramètres de votre nouvelle clé</p>
                        </div>

                        <form @submit.prevent="submitCreate" class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-2">Nom de la clé</label>
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    class="input"
                                    placeholder="Ex: Production, Développement..."
                                />
                                <p v-if="createForm.errors.name" class="mt-1.5 text-sm text-red-600">{{ createForm.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-3">Permissions</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label
                                        v-for="(label, key) in availablePermissions"
                                        :key="key"
                                        :class="[
                                            'flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-all',
                                            createForm.permissions.includes(key)
                                                ? 'border-primary-500 bg-primary-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="createForm.permissions"
                                            :value="key"
                                            class="sr-only"
                                        />
                                        <div :class="[
                                            'w-8 h-8 rounded-lg flex items-center justify-center',
                                            createForm.permissions.includes(key) ? 'bg-primary-100' : 'bg-gray-100'
                                        ]">
                                            <svg :class="[
                                                'w-4 h-4',
                                                createForm.permissions.includes(key) ? 'text-primary-600' : 'text-secondary-500'
                                            ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getPermissionIcon(key)" />
                                            </svg>
                                        </div>
                                        <span :class="[
                                            'text-sm font-medium',
                                            createForm.permissions.includes(key) ? 'text-primary-700' : 'text-secondary-700'
                                        ]">{{ label }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                                        Limite requêtes/min
                                    </label>
                                    <input
                                        v-model="createForm.rate_limit"
                                        type="number"
                                        min="1"
                                        max="10000"
                                        class="input"
                                        placeholder="Ex: 100"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                                        Expiration
                                    </label>
                                    <input
                                        v-model="createForm.expires_at"
                                        type="date"
                                        class="input"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-2">
                                    IPs autorisées (optionnel)
                                </label>
                                <input
                                    v-model="createForm.ip_whitelist"
                                    type="text"
                                    class="input"
                                    placeholder="192.168.1.1, 10.0.0.1"
                                />
                                <p class="mt-1.5 text-xs text-secondary-500">Séparez les IPs par des virgules.</p>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button
                                    type="button"
                                    @click="showCreateModal = false"
                                    class="btn btn-secondary"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="createForm.processing"
                                    class="btn btn-primary"
                                >
                                    Créer la clé
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showEditModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h2 class="text-lg font-semibold text-secondary-900">Modifier la clé API</h2>
                        </div>

                        <form @submit.prevent="submitEdit" class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-2">Nom</label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="input"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-3">Permissions</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label
                                        v-for="(label, key) in availablePermissions"
                                        :key="key"
                                        :class="[
                                            'flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-all',
                                            editForm.permissions.includes(key)
                                                ? 'border-primary-500 bg-primary-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="editForm.permissions"
                                            :value="key"
                                            class="sr-only"
                                        />
                                        <div :class="[
                                            'w-8 h-8 rounded-lg flex items-center justify-center',
                                            editForm.permissions.includes(key) ? 'bg-primary-100' : 'bg-gray-100'
                                        ]">
                                            <svg :class="[
                                                'w-4 h-4',
                                                editForm.permissions.includes(key) ? 'text-primary-600' : 'text-secondary-500'
                                            ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getPermissionIcon(key)" />
                                            </svg>
                                        </div>
                                        <span :class="[
                                            'text-sm font-medium',
                                            editForm.permissions.includes(key) ? 'text-primary-700' : 'text-secondary-700'
                                        ]">{{ label }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">Limite/min</label>
                                    <input
                                        v-model="editForm.rate_limit"
                                        type="number"
                                        min="1"
                                        max="10000"
                                        class="input"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-secondary-700 mb-2">Expiration</label>
                                    <input
                                        v-model="editForm.expires_at"
                                        type="date"
                                        class="input"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-2">IPs autorisées</label>
                                <input
                                    v-model="editForm.ip_whitelist"
                                    type="text"
                                    class="input"
                                    placeholder="192.168.1.1, 10.0.0.1"
                                />
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button
                                    type="button"
                                    @click="showEditModal = false"
                                    class="btn btn-secondary"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="editForm.processing"
                                    class="btn btn-primary"
                                >
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-secondary-900 mb-2">Supprimer la clé API</h2>
                        <p class="text-secondary-500 mb-6">
                            Êtes-vous sûr de vouloir supprimer la clé <span class="font-semibold text-secondary-900">"{{ deletingKey?.name }}"</span> ?
                            Cette action est irréversible.
                        </p>
                        <div class="flex justify-center gap-3">
                            <button
                                @click="showDeleteModal = false"
                                class="btn btn-secondary"
                            >
                                Annuler
                            </button>
                            <button
                                @click="deleteKey"
                                class="btn bg-red-600 text-white hover:bg-red-700"
                            >
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- New Key Modal -->
        <Teleport to="body">
            <div v-if="showNewKeyModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg p-6 text-center">
                        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-5">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-secondary-900 mb-2">Clé API créée avec succès !</h2>
                        <p class="text-secondary-500 mb-6">
                            Copiez cette clé maintenant. Elle ne sera plus jamais affichée.
                        </p>

                        <div class="bg-gray-100 rounded-xl p-4 mb-6">
                            <div class="flex items-center justify-between gap-3">
                                <code class="text-sm font-mono text-secondary-900 break-all text-left">{{ newApiKey }}</code>
                                <button
                                    @click="copyToClipboard(newApiKey)"
                                    class="p-2.5 text-secondary-500 hover:text-secondary-700 hover:bg-gray-200 rounded-lg flex-shrink-0 transition-colors"
                                >
                                    <svg v-if="!copiedKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl mb-6 text-left">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-amber-800">Important</p>
                                    <p class="text-sm text-amber-700 mt-1">
                                        Stockez cette clé dans un endroit sécurisé. Ne la partagez jamais.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="showNewKeyModal = false; newApiKey = null;"
                            class="btn btn-primary w-full"
                        >
                            J'ai copié ma clé
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

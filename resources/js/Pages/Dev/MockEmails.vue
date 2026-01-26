<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    recentEmails: Array,
    sesStatus: Object,
})

const page = usePage()

// Flash messages
const flashSuccess = computed(() => page.props.flash?.success)
const flashError = computed(() => page.props.flash?.error)

const testForm = useForm({
    to_email: '',
    subject: 'Test SliMail - Mode Mock',
    content: '<h1>Test Email</h1><p>Ceci est un email de test envoyé via le mode simulation de SliMail.</p><p>Le service fonctionne correctement!</p>',
})

const sendTestEmail = () => {
    testForm.post('/dev/mock-emails/send-test', {
        preserveScroll: true,
        onSuccess: () => {
            testForm.reset('to_email')
        },
    })
}

const simulateEvent = (emailId, eventType) => {
    router.post(`/dev/mock-emails/${emailId}/simulate-event`, {
        event_type: eventType,
    }, {
        preserveScroll: true,
    })
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'sent': 'bg-blue-100 text-blue-800',
        'delivered': 'bg-green-100 text-green-800',
        'opened': 'bg-purple-100 text-purple-800',
        'clicked': 'bg-indigo-100 text-indigo-800',
        'bounced': 'bg-red-100 text-red-800',
        'complained': 'bg-orange-100 text-orange-800',
        'failed': 'bg-gray-100 text-gray-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <AppLayout>
        <Head title="Mode Mock - Test Emails" />

        <div class="space-y-6">
            <!-- Flash Messages -->
            <div v-if="flashSuccess" class="p-4 bg-green-50 border border-green-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-sm font-medium text-green-800">{{ flashSuccess }}</p>
                </div>
            </div>

            <div v-if="flashError" class="p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-red-800">{{ flashError }}</p>
                </div>
            </div>

            <!-- Form Errors -->
            <div v-if="testForm.hasErrors" class="p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">Erreurs de validation :</p>
                        <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                            <li v-for="(error, key) in testForm.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Mode Mock - Test Emails</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Interface de développement pour tester l'envoi d'emails sans Amazon SES
                    </p>
                </div>
            </div>

            <!-- Status Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Statut du Service Email</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Mock Mode Status -->
                    <div class="p-4 rounded-lg" :class="sesStatus.is_mock_mode ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-50 border border-green-200'">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-full" :class="sesStatus.is_mock_mode ? 'bg-yellow-100' : 'bg-green-100'">
                                <svg v-if="sesStatus.is_mock_mode" class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <svg v-else class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium" :class="sesStatus.is_mock_mode ? 'text-yellow-800' : 'text-green-800'">
                                    {{ sesStatus.is_mock_mode ? 'Mode Simulation' : 'Mode Production' }}
                                </p>
                                <p class="text-xs" :class="sesStatus.is_mock_mode ? 'text-yellow-600' : 'text-green-600'">
                                    {{ sesStatus.is_mock_mode ? 'Les emails ne sont pas réellement envoyés' : 'Connecté à Amazon SES' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quota -->
                    <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-full bg-blue-100">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-blue-800">Quota 24h</p>
                                <p class="text-xs text-blue-600">
                                    {{ sesStatus.quota?.sent_last_24_hours || 0 }} / {{ sesStatus.quota?.max_24_hour_send || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Rate Limit -->
                    <div class="p-4 rounded-lg bg-purple-50 border border-purple-200">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-full bg-purple-100">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-purple-800">Débit max</p>
                                <p class="text-xs text-purple-600">
                                    {{ sesStatus.quota?.max_send_rate || 0 }} emails/seconde
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mock Mode Notice -->
                <div v-if="sesStatus.is_mock_mode" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">Mode Simulation Actif</p>
                            <p class="text-sm text-yellow-700 mt-1">
                                Les emails sont simulés et enregistrés dans les logs. Pour activer le vrai envoi via Amazon SES,
                                configurez vos credentials AWS dans le fichier <code class="bg-yellow-100 px-1 rounded">.env</code>
                                et définissez <code class="bg-yellow-100 px-1 rounded">SES_MOCK_MODE=false</code>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Email Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Envoyer un Email de Test</h2>

                <form @submit.prevent="sendTestEmail" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email destinataire</label>
                            <input
                                v-model="testForm.to_email"
                                type="email"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                placeholder="test@example.com"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
                            <input
                                v-model="testForm.subject"
                                type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contenu HTML</label>
                        <textarea
                            v-model="testForm.content"
                            rows="4"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 font-mono text-sm"
                        ></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="testForm.processing"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 flex items-center gap-2"
                        >
                            <svg v-if="testForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Envoyer le test
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Emails Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Emails Récents</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destinataire</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sujet</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campagne</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="email in recentEmails" :key="email.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ email.to }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">{{ email.subject }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ email.campaign }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadgeClass(email.status)]">
                                        {{ email.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ email.sent_at }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="sesStatus.is_mock_mode" class="flex gap-1">
                                        <button
                                            @click="simulateEvent(email.id, 'delivery')"
                                            class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200"
                                            title="Simuler livraison"
                                        >
                                            📬
                                        </button>
                                        <button
                                            @click="simulateEvent(email.id, 'open')"
                                            class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded hover:bg-purple-200"
                                            title="Simuler ouverture"
                                        >
                                            👁️
                                        </button>
                                        <button
                                            @click="simulateEvent(email.id, 'click')"
                                            class="px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200"
                                            title="Simuler clic"
                                        >
                                            👆
                                        </button>
                                        <button
                                            @click="simulateEvent(email.id, 'bounce')"
                                            class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200"
                                            title="Simuler rebond"
                                        >
                                            ❌
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                            </tr>
                            <tr v-if="recentEmails.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <p>Aucun email envoyé pour le moment</p>
                                    <p class="text-sm mt-1">Utilisez le formulaire ci-dessus pour envoyer un email de test</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

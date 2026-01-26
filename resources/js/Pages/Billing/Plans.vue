<script setup>
/**
 * Plans Selection Page
 * Display available plans with pricing
 */

import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageContainer from '@/Components/Layout/PageContainer.vue';

const props = defineProps({
    plans: Array,
    currentPlanId: Number,
    billingCycle: String,
});

const selectedCycle = ref(props.billingCycle || 'monthly');
const isSubmitting = ref(false);

// Feature labels
const featureLabels = {
    email_editor: "Éditeur d'emails drag & drop",
    custom_domain: 'Domaine personnalisé',
    api_access: 'Accès API',
    automation: 'Automatisations',
    ab_testing: 'Tests A/B',
    advanced_analytics: 'Statistiques avancées',
    priority_support: 'Support prioritaire',
    dedicated_ip: 'IP dédiée',
    white_label: 'Marque blanche',
    custom_branding: 'Branding personnalisé',
};

// Toggle billing cycle
const toggleCycle = () => {
    selectedCycle.value = selectedCycle.value === 'monthly' ? 'yearly' : 'monthly';
};

// Get price based on cycle
const getPrice = (plan) => {
    return selectedCycle.value === 'yearly' ? plan.price_yearly_formatted : plan.price_monthly_formatted;
};

// Get monthly equivalent for yearly
const getMonthlyEquivalent = (plan) => {
    if (selectedCycle.value !== 'yearly') return null;
    return Math.round(plan.price_yearly / 12);
};

// Subscribe to plan
const subscribeToPlan = (plan) => {
    if (plan.is_current || isSubmitting.value) return;

    isSubmitting.value = true;

    router.post(route('billing.subscribe', plan.id), {
        billing_cycle: selectedCycle.value,
    }, {
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

// Get plan color classes
const getPlanColorClass = (color, type = 'border') => {
    const colors = {
        gray: { border: 'border-gray-200', bg: 'bg-gray-500', text: 'text-gray-600' },
        blue: { border: 'border-blue-200', bg: 'bg-blue-500', text: 'text-blue-600' },
        violet: { border: 'border-violet-200', bg: 'bg-violet-500', text: 'text-violet-600' },
        emerald: { border: 'border-emerald-200', bg: 'bg-emerald-500', text: 'text-emerald-600' },
        amber: { border: 'border-amber-200', bg: 'bg-amber-500', text: 'text-amber-600' },
    };
    return colors[color]?.[type] || colors.gray[type];
};
</script>

<template>
    <AppLayout>
        <Head title="Forfaits" />

        <PageContainer size="wide">
            <div class="space-y-8">
                <!-- Header -->
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-secondary-900">Choisissez votre forfait</h1>
                    <p class="mt-2 text-lg text-secondary-500">Des forfaits adaptés à tous vos besoins</p>

                    <!-- Billing cycle toggle -->
                    <div class="mt-6 flex items-center justify-center gap-4">
                        <span :class="['text-sm font-medium', selectedCycle === 'monthly' ? 'text-secondary-900' : 'text-secondary-400']">
                            Mensuel
                        </span>
                        <button
                            @click="toggleCycle"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                            :class="selectedCycle === 'yearly' ? 'bg-primary-600' : 'bg-gray-200'"
                        >
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                :class="selectedCycle === 'yearly' ? 'translate-x-6' : 'translate-x-1'"
                            />
                        </button>
                        <span :class="['text-sm font-medium', selectedCycle === 'yearly' ? 'text-secondary-900' : 'text-secondary-400']">
                            Annuel
                            <span class="ml-1 text-emerald-600 text-xs">(jusqu'à 17% d'économie)</span>
                        </span>
                    </div>
                </div>

                <!-- Plans grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        :class="[
                            'relative bg-white rounded-2xl border-2 shadow-sm overflow-hidden flex flex-col',
                            plan.is_popular ? 'border-violet-500 ring-2 ring-violet-500/20' : getPlanColorClass(plan.color, 'border'),
                            plan.is_current ? 'bg-gray-50' : ''
                        ]"
                    >
                        <!-- Popular badge -->
                        <div v-if="plan.is_popular" class="absolute top-0 right-0">
                            <div class="bg-violet-500 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">
                                POPULAIRE
                            </div>
                        </div>

                        <!-- Current badge -->
                        <div v-if="plan.is_current" class="absolute top-0 left-0">
                            <div class="bg-emerald-500 text-white text-xs font-bold px-3 py-1 rounded-br-lg">
                                ACTUEL
                            </div>
                        </div>

                        <div class="p-6 flex-1">
                            <!-- Plan name -->
                            <h3 class="text-xl font-bold text-secondary-900">{{ plan.name }}</h3>
                            <p class="mt-2 text-sm text-secondary-500 min-h-[40px]">{{ plan.description }}</p>

                            <!-- Price -->
                            <div class="mt-4">
                                <div class="flex items-baseline gap-1">
                                    <span v-if="plan.is_free" class="text-3xl font-bold text-secondary-900">Gratuit</span>
                                    <template v-else>
                                        <span class="text-3xl font-bold text-secondary-900">
                                            {{ new Intl.NumberFormat('fr-FR').format(selectedCycle === 'yearly' ? plan.price_yearly : plan.price_monthly) }}
                                        </span>
                                        <span class="text-secondary-500">XOF</span>
                                    </template>
                                </div>
                                <p v-if="!plan.is_free" class="text-sm text-secondary-400">
                                    {{ selectedCycle === 'yearly' ? 'par an' : 'par mois' }}
                                    <span v-if="selectedCycle === 'yearly' && plan.yearly_savings > 0" class="text-emerald-600">
                                        (-{{ plan.yearly_savings }}%)
                                    </span>
                                </p>
                            </div>

                            <!-- Trial -->
                            <p v-if="plan.trial_days > 0 && !plan.is_current" class="mt-2 text-sm text-violet-600 font-medium">
                                {{ plan.trial_days }} jours d'essai gratuit
                            </p>

                            <!-- Limits -->
                            <div class="mt-6 space-y-3">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm text-secondary-600">{{ plan.limits.emails }} emails/mois</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm text-secondary-600">{{ plan.limits.contacts }} contacts</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm text-secondary-600">{{ plan.limits.users }} utilisateur(s)</span>
                                </div>
                            </div>

                            <!-- Features -->
                            <div class="mt-6 pt-6 border-t border-gray-100 space-y-3">
                                <div
                                    v-for="(enabled, feature) in plan.features"
                                    :key="feature"
                                    class="flex items-center gap-2"
                                >
                                    <svg
                                        v-if="enabled"
                                        class="w-4 h-4 text-emerald-500 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg
                                        v-else
                                        class="w-4 h-4 text-secondary-300 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span :class="['text-sm', enabled ? 'text-secondary-600' : 'text-secondary-400']">
                                        {{ featureLabels[feature] || feature }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action button -->
                        <div class="p-6 pt-0">
                            <button
                                @click="subscribeToPlan(plan)"
                                :disabled="plan.is_current || isSubmitting"
                                :class="[
                                    'w-full py-3 px-4 rounded-xl text-sm font-semibold transition-colors',
                                    plan.is_current
                                        ? 'bg-gray-100 text-secondary-400 cursor-not-allowed'
                                        : plan.is_popular
                                            ? 'bg-violet-600 text-white hover:bg-violet-700'
                                            : 'bg-secondary-900 text-white hover:bg-secondary-800'
                                ]"
                            >
                                <span v-if="plan.is_current">Forfait actuel</span>
                                <span v-else-if="plan.is_free">Commencer gratuitement</span>
                                <span v-else-if="plan.trial_days > 0">Essayer gratuitement</span>
                                <span v-else>Choisir ce forfait</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- FAQ or additional info -->
                <div class="text-center text-secondary-500 text-sm">
                    <p>Besoin d'un forfait personnalisé ? <a href="mailto:contact@slimail.com" class="text-primary-600 hover:underline">Contactez-nous</a></p>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>

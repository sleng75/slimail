<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Plateforme d'Emailing Professionnelle" />

    <div class="min-h-screen bg-white">
        <!-- Navigation -->
        <nav class="fixed w-full z-50 bg-white/95 backdrop-blur-sm border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-3">
                        <img
                            src="/images/logo.png"
                            alt="SliMail"
                            class="h-10 w-auto"
                        />
                        <span class="text-2xl font-bold text-secondary-900">Sli<span class="text-primary-600">Mail</span></span>
                    </div>

                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#features" class="text-secondary-600 hover:text-primary-600 font-medium transition-colors">
                            Fonctionnalités
                        </a>
                        <a href="#pricing" class="text-secondary-600 hover:text-primary-600 font-medium transition-colors">
                            Tarifs
                        </a>
                        <a href="#api" class="text-secondary-600 hover:text-primary-600 font-medium transition-colors">
                            API
                        </a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <Link
                            href="/login"
                            class="text-secondary-700 hover:text-primary-600 font-medium transition-colors"
                        >
                            Connexion
                        </Link>
                        <Link
                            href="/register"
                            class="bg-primary-600 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-primary-700 transition-colors shadow-sm"
                        >
                            Commencer
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative pt-16 overflow-hidden">
            <!-- Background pattern -->
            <div class="absolute inset-0 bg-gradient-to-br from-gray-50 via-white to-primary-50"></div>
            <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-primary-100/50 to-transparent"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="inline-flex items-center px-4 py-2 bg-primary-100 rounded-full mb-6">
                            <span class="w-2 h-2 bg-primary-600 rounded-full mr-2 animate-pulse"></span>
                            <span class="text-sm font-medium text-primary-700">Solution d'emailing africaine</span>
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-secondary-900 tracking-tight">
                            Vos emails,
                            <span class="text-primary-600">livrés</span>
                        </h1>
                        <p class="mt-6 text-xl text-secondary-600 leading-relaxed">
                            La plateforme d'emailing conçue pour les entreprises africaines.
                            API transactionnelle, campagnes marketing et paiement Mobile Money.
                        </p>

                        <div class="mt-10 flex flex-col sm:flex-row gap-4">
                            <Link
                                href="/register"
                                class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold rounded-xl text-white bg-primary-600 hover:bg-primary-700 transition-all shadow-lg shadow-primary-600/25 hover:shadow-xl hover:shadow-primary-600/30"
                            >
                                Essayer gratuitement
                                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <a
                                href="#api"
                                class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold rounded-xl text-secondary-700 bg-secondary-100 hover:bg-secondary-200 transition-all"
                            >
                                <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                                Documentation API
                            </a>
                        </div>

                        <!-- Stats -->
                        <div class="mt-12 grid grid-cols-3 gap-8">
                            <div>
                                <div class="text-3xl font-bold text-secondary-900">98%</div>
                                <div class="text-sm text-secondary-500">Délivrabilité</div>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-secondary-900">&lt;1s</div>
                                <div class="text-sm text-secondary-500">Temps de réponse</div>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-secondary-900">24/7</div>
                                <div class="text-sm text-secondary-500">Disponibilité</div>
                            </div>
                        </div>
                    </div>

                    <!-- Hero Image/Illustration -->
                    <div class="relative hidden lg:block">
                        <div class="absolute inset-0 bg-gradient-to-tr from-primary-600/20 to-secondary-900/10 rounded-3xl"></div>
                        <div class="relative bg-white rounded-3xl shadow-2xl p-8 border border-gray-100">
                            <!-- Email preview mockup -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-4 border-b border-gray-100">
                                    <div class="w-3 h-3 bg-primary-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs text-secondary-400">De:</span>
                                        <span class="text-sm text-secondary-700">contact@votreentreprise.com</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs text-secondary-400">À:</span>
                                        <span class="text-sm text-secondary-700">client@example.com</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs text-secondary-400">Objet:</span>
                                        <span class="text-sm font-medium text-secondary-900">Bienvenue chez nous!</span>
                                    </div>
                                </div>
                                <div class="pt-4 border-t border-gray-100">
                                    <div class="h-2 bg-gray-100 rounded w-full mb-2"></div>
                                    <div class="h-2 bg-gray-100 rounded w-3/4 mb-2"></div>
                                    <div class="h-2 bg-gray-100 rounded w-5/6"></div>
                                </div>
                                <div class="pt-4">
                                    <div class="inline-flex items-center px-4 py-2 bg-primary-600 rounded-lg text-white text-sm font-medium">
                                        Découvrir
                                    </div>
                                </div>
                            </div>

                            <!-- Floating stats -->
                            <div class="absolute -right-4 top-1/4 bg-white rounded-xl shadow-lg p-4 border border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs text-secondary-500">Livré</div>
                                        <div class="text-sm font-semibold text-secondary-900">100%</div>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute -left-4 bottom-1/4 bg-white rounded-xl shadow-lg p-4 border border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs text-secondary-500">Ouvert</div>
                                        <div class="text-sm font-semibold text-secondary-900">67%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div id="features" class="py-24 bg-secondary-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl sm:text-4xl font-bold text-white">
                        Tout ce dont vous avez besoin
                    </h2>
                    <p class="mt-4 text-xl text-secondary-300">
                        Une solution complète pour vos communications email
                    </p>
                </div>

                <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="relative p-8 bg-secondary-800 rounded-2xl border border-secondary-700 hover:border-primary-500/50 transition-colors group">
                        <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-white">API REST puissante</h3>
                        <p class="mt-2 text-secondary-400">
                            Intégrez facilement l'envoi d'emails dans vos applications avec notre API REST documentée.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="relative p-8 bg-secondary-800 rounded-2xl border border-secondary-700 hover:border-primary-500/50 transition-colors group">
                        <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-white">Délivrabilité optimale</h3>
                        <p class="mt-2 text-secondary-400">
                            Infrastructure Amazon SES pour un taux de délivrabilité supérieur à 98%.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="relative p-8 bg-secondary-800 rounded-2xl border border-secondary-700 hover:border-primary-500/50 transition-colors group">
                        <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-white">Éditeur drag & drop</h3>
                        <p class="mt-2 text-secondary-400">
                            Créez de magnifiques emails responsive sans connaissances techniques.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="relative p-8 bg-secondary-800 rounded-2xl border border-secondary-700 hover:border-primary-500/50 transition-colors group">
                        <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-white">Statistiques détaillées</h3>
                        <p class="mt-2 text-secondary-400">
                            Suivez les ouvertures, clics et conversions en temps réel.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="relative p-8 bg-secondary-800 rounded-2xl border border-secondary-700 hover:border-primary-500/50 transition-colors group">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-white">Paiement Mobile Money</h3>
                        <p class="mt-2 text-secondary-400">
                            Payez facilement avec Orange Money, Moov Money ou Wave.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="relative p-8 bg-secondary-800 rounded-2xl border border-secondary-700 hover:border-primary-500/50 transition-colors group">
                        <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-white">Automatisations</h3>
                        <p class="mt-2 text-secondary-400">
                            Créez des séquences d'emails automatiques pour engager vos contacts.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Section -->
        <div id="api" class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-secondary-900">
                            API simple et puissante
                        </h2>
                        <p class="mt-4 text-xl text-secondary-600">
                            Intégrez l'envoi d'emails en quelques lignes de code.
                            Documentation complète et SDKs disponibles.
                        </p>

                        <div class="mt-8 space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-secondary-900">REST API & Relais SMTP</h4>
                                    <p class="text-secondary-600">Choisissez l'intégration qui vous convient</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-secondary-900">Webhooks en temps réel</h4>
                                    <p class="text-secondary-600">Recevez les événements (delivery, bounce, open, click)</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-secondary-900">Templates dynamiques</h4>
                                    <p class="text-secondary-600">Variables et personnalisation avancée</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Code example -->
                    <div class="bg-secondary-900 rounded-2xl p-6 shadow-2xl">
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-3 h-3 bg-primary-500 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="ml-4 text-secondary-400 text-sm">api-example.js</span>
                        </div>
                        <pre class="text-sm text-secondary-300 overflow-x-auto"><code class="language-javascript"><span class="text-purple-400">const</span> response = <span class="text-purple-400">await</span> <span class="text-yellow-300">fetch</span>(<span class="text-green-400">'https://api.slimail.com/v1/send'</span>, {
  <span class="text-secondary-500">method</span>: <span class="text-green-400">'POST'</span>,
  <span class="text-secondary-500">headers</span>: {
    <span class="text-green-400">'Authorization'</span>: <span class="text-green-400">`Bearer ${<span class="text-secondary-200">API_KEY</span>}`</span>,
    <span class="text-green-400">'Content-Type'</span>: <span class="text-green-400">'application/json'</span>
  },
  <span class="text-secondary-500">body</span>: <span class="text-yellow-300">JSON</span>.<span class="text-yellow-300">stringify</span>({
    <span class="text-secondary-500">from</span>: <span class="text-green-400">'noreply@votreapp.com'</span>,
    <span class="text-secondary-500">to</span>: <span class="text-green-400">'client@example.com'</span>,
    <span class="text-secondary-500">subject</span>: <span class="text-green-400">'Bienvenue!'</span>,
    <span class="text-secondary-500">html</span>: <span class="text-green-400">'&lt;h1&gt;Bonjour {{name}}!&lt;/h1&gt;'</span>,
    <span class="text-secondary-500">variables</span>: { <span class="text-secondary-500">name</span>: <span class="text-green-400">'Jean'</span> }
  })
});

<span class="text-secondary-500">// Response</span>
{ <span class="text-secondary-500">id</span>: <span class="text-green-400">'msg_abc123'</span>, <span class="text-secondary-500">status</span>: <span class="text-green-400">'queued'</span> }</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Section -->
        <div id="pricing" class="bg-white py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl sm:text-4xl font-bold text-secondary-900">
                        Tarifs adaptés à vos besoins
                    </h2>
                    <p class="mt-4 text-xl text-secondary-600">
                        Commencez gratuitement et évoluez selon votre croissance
                    </p>
                </div>

                <div class="mt-16 grid grid-cols-1 gap-8 lg:grid-cols-4">
                    <!-- Starter -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 hover:shadow-lg hover:border-primary-200 transition-all">
                        <h3 class="text-lg font-semibold text-secondary-900">Starter</h3>
                        <p class="mt-2 text-sm text-secondary-500">Pour démarrer</p>
                        <p class="mt-6">
                            <span class="text-4xl font-bold text-secondary-900">5 900</span>
                            <span class="text-secondary-500">F/mois</span>
                        </p>
                        <ul class="mt-8 space-y-3">
                            <li class="flex items-center text-sm text-secondary-600">
                                <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                1 000 emails/mois
                            </li>
                            <li class="flex items-center text-sm text-secondary-600">
                                <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                500 contacts
                            </li>
                            <li class="flex items-center text-sm text-secondary-600">
                                <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                API REST
                            </li>
                        </ul>
                        <Link href="/register" class="mt-8 block w-full text-center px-4 py-3 border-2 border-secondary-200 text-secondary-700 rounded-xl font-medium hover:border-primary-600 hover:text-primary-600 transition-colors">
                            Commencer
                        </Link>
                    </div>

                    <!-- Pro -->
                    <div class="bg-primary-600 rounded-2xl shadow-xl p-8 relative transform lg:scale-105">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary-900 text-white text-xs font-bold px-4 py-1.5 rounded-full">
                            POPULAIRE
                        </div>
                        <h3 class="text-lg font-semibold text-white">Pro</h3>
                        <p class="mt-2 text-sm text-primary-200">Pour les PME</p>
                        <p class="mt-6">
                            <span class="text-4xl font-bold text-white">29 500</span>
                            <span class="text-primary-200">F/mois</span>
                        </p>
                        <ul class="mt-8 space-y-3">
                            <li class="flex items-center text-sm text-primary-100">
                                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                10 000 emails/mois
                            </li>
                            <li class="flex items-center text-sm text-primary-100">
                                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                5 000 contacts
                            </li>
                            <li class="flex items-center text-sm text-primary-100">
                                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Automatisations
                            </li>
                            <li class="flex items-center text-sm text-primary-100">
                                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Support prioritaire
                            </li>
                        </ul>
                        <Link href="/register" class="mt-8 block w-full text-center px-4 py-3 bg-white text-primary-700 rounded-xl font-semibold hover:bg-primary-50 transition-colors">
                            Commencer
                        </Link>
                    </div>

                    <!-- Business -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 hover:shadow-lg hover:border-primary-200 transition-all">
                        <h3 class="text-lg font-semibold text-secondary-900">Business</h3>
                        <p class="mt-2 text-sm text-secondary-500">Pour les grandes équipes</p>
                        <p class="mt-6">
                            <span class="text-4xl font-bold text-secondary-900">88 500</span>
                            <span class="text-secondary-500">F/mois</span>
                        </p>
                        <ul class="mt-8 space-y-3">
                            <li class="flex items-center text-sm text-secondary-600">
                                <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                50 000 emails/mois
                            </li>
                            <li class="flex items-center text-sm text-secondary-600">
                                <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                25 000 contacts
                            </li>
                            <li class="flex items-center text-sm text-secondary-600">
                                <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Tests A/B
                            </li>
                            <li class="flex items-center text-sm text-secondary-600">
                                <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                IP dédiée
                            </li>
                        </ul>
                        <Link href="/register" class="mt-8 block w-full text-center px-4 py-3 border-2 border-secondary-200 text-secondary-700 rounded-xl font-medium hover:border-primary-600 hover:text-primary-600 transition-colors">
                            Commencer
                        </Link>
                    </div>

                    <!-- Enterprise -->
                    <div class="bg-secondary-900 rounded-2xl shadow-sm p-8 hover:shadow-lg transition-all">
                        <h3 class="text-lg font-semibold text-white">Enterprise</h3>
                        <p class="mt-2 text-sm text-secondary-400">Sur mesure</p>
                        <p class="mt-6">
                            <span class="text-4xl font-bold text-white">Sur devis</span>
                        </p>
                        <ul class="mt-8 space-y-3">
                            <li class="flex items-center text-sm text-secondary-300">
                                <svg class="w-5 h-5 text-primary-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Emails illimités
                            </li>
                            <li class="flex items-center text-sm text-secondary-300">
                                <svg class="w-5 h-5 text-primary-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Contacts illimités
                            </li>
                            <li class="flex items-center text-sm text-secondary-300">
                                <svg class="w-5 h-5 text-primary-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                SLA garanti
                            </li>
                            <li class="flex items-center text-sm text-secondary-300">
                                <svg class="w-5 h-5 text-primary-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Account manager dédié
                            </li>
                        </ul>
                        <a href="mailto:contact@slimat.bf" class="mt-8 block w-full text-center px-4 py-3 bg-primary-600 text-white rounded-xl font-medium hover:bg-primary-700 transition-colors">
                            Nous contacter
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-primary-600 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white">
                    Prêt à améliorer vos communications email ?
                </h2>
                <p class="mt-4 text-xl text-primary-100">
                    Rejoignez les entreprises qui font confiance à SliMail
                </p>
                <div class="mt-8">
                    <Link
                        href="/register"
                        class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold rounded-xl text-primary-700 bg-white hover:bg-primary-50 transition-colors shadow-lg"
                    >
                        Créer un compte gratuit
                        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-secondary-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-4 gap-8">
                    <div class="md:col-span-2">
                        <div class="flex items-center space-x-3">
                            <img
                                src="/images/logo.png"
                                alt="SliMail"
                                class="h-10 w-auto"
                            />
                            <span class="text-2xl font-bold text-white">Sli<span class="text-primary-500">Mail</span></span>
                        </div>
                        <p class="mt-4 text-secondary-400 max-w-md">
                            La plateforme d'emailing conçue pour les entreprises africaines.
                            Envoyez des emails transactionnels et marketing avec une délivrabilité optimale.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white uppercase tracking-wider">Produit</h4>
                        <ul class="mt-4 space-y-2">
                            <li><a href="#features" class="text-secondary-400 hover:text-white transition-colors">Fonctionnalités</a></li>
                            <li><a href="#pricing" class="text-secondary-400 hover:text-white transition-colors">Tarifs</a></li>
                            <li><a href="#api" class="text-secondary-400 hover:text-white transition-colors">API</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white uppercase tracking-wider">Contact</h4>
                        <ul class="mt-4 space-y-2">
                            <li><a href="mailto:contact@slimat.bf" class="text-secondary-400 hover:text-white transition-colors">contact@slimat.bf</a></li>
                            <li><span class="text-secondary-400">Ouagadougou, Burkina Faso</span></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-12 pt-8 border-t border-secondary-800 flex flex-col md:flex-row justify-between items-center">
                    <p class="text-secondary-400 text-sm">
                        &copy; {{ new Date().getFullYear() }} SliMail by SLIMAT SARL (INOVE TECH). Tous droits réservés.
                    </p>
                    <div class="mt-4 md:mt-0 flex space-x-6">
                        <a href="#" class="text-secondary-400 hover:text-white transition-colors">
                            Conditions d'utilisation
                        </a>
                        <a href="#" class="text-secondary-400 hover:text-white transition-colors">
                            Politique de confidentialité
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

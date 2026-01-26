<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex">
        <!-- Left side - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-secondary-900 relative overflow-hidden">
            <!-- Background pattern -->
            <div class="absolute inset-0 bg-gradient-to-br from-secondary-800 via-secondary-900 to-black"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-primary-600/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-primary-600/10 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col justify-between p-12 w-full">
                <div>
                    <Link href="/" class="flex items-center space-x-3">
                        <img src="/images/logo.png" alt="SliMail" class="h-12 w-auto" />
                        <span class="text-3xl font-bold text-white">Sli<span class="text-primary-500">Mail</span></span>
                    </Link>
                </div>

                <div class="space-y-8">
                    <h1 class="text-4xl font-bold text-white leading-tight">
                        Vos emails,<br/>
                        <span class="text-primary-500">livrés.</span>
                    </h1>
                    <p class="text-xl text-secondary-300 max-w-md">
                        La plateforme d'emailing conçue pour les entreprises africaines.
                    </p>

                    <div class="grid grid-cols-2 gap-6 pt-8">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-600/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-secondary-300">98% délivrabilité</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-600/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-secondary-300">API rapide</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-600/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-secondary-300">Mobile Money</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-600/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <span class="text-secondary-300">Statistiques</span>
                        </div>
                    </div>
                </div>

                <p class="text-secondary-500 text-sm">
                    &copy; {{ new Date().getFullYear() }} SliMail by SLIMAT SARL (INOVE TECH)
                </p>
            </div>
        </div>

        <!-- Right side - Form -->
        <div class="flex-1 flex flex-col justify-center items-center p-8 bg-gray-50">
            <!-- Mobile logo -->
            <div class="lg:hidden mb-8">
                <Link href="/" class="flex items-center space-x-3">
                    <img src="/images/logo.png" alt="SliMail" class="h-10 w-auto" />
                    <span class="text-2xl font-bold text-secondary-900">Sli<span class="text-primary-600">Mail</span></span>
                </Link>
            </div>

            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <slot />
                </div>

                <p class="mt-8 text-center text-sm text-secondary-500 lg:hidden">
                    &copy; {{ new Date().getFullYear() }} SliMail by SLIMAT SARL
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * API Documentation Page
 * Displays OpenAPI documentation using Swagger UI
 */

import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    specUrl: String,
});

const swaggerContainer = ref(null);

onMounted(async () => {
    // Load Swagger UI CSS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css';
    document.head.appendChild(link);

    // Load Swagger UI JS
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js';
    script.onload = () => {
        window.SwaggerUIBundle({
            url: props.specUrl || '/api-docs/openapi.yaml',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                window.SwaggerUIBundle.presets.apis,
                window.SwaggerUIBundle.SwaggerUIStandalonePreset
            ],
            plugins: [
                window.SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: 'BaseLayout',
            defaultModelsExpandDepth: 1,
            defaultModelExpandDepth: 1,
            docExpansion: 'list',
            filter: true,
            showExtensions: true,
            showCommonExtensions: true,
            tryItOutEnabled: true,
        });
    };
    document.body.appendChild(script);
});
</script>

<template>
    <Head title="Documentation API" />

    <div class="min-h-screen bg-white">
        <!-- Header -->
        <header class="bg-gray-900 text-white py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">SliMail API</h1>
                        <p class="text-gray-400 mt-1">Documentation de l'API transactionnelle</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a
                            href="/api-docs/openapi.yaml"
                            download
                            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition-colors"
                        >
                            Télécharger OpenAPI
                        </a>
                        <a
                            href="/dashboard"
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-lg text-sm transition-colors"
                        >
                            Retour au Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Quick Start Guide -->
        <div class="bg-gray-50 border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Démarrage rapide</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Step 1 -->
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center font-bold">1</span>
                            <h3 class="font-medium">Obtenir une clé API</h3>
                        </div>
                        <p class="text-sm text-gray-600">
                            Générez une clé API depuis votre tableau de bord dans
                            <strong>Paramètres → Clés API</strong>.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center font-bold">2</span>
                            <h3 class="font-medium">Configurer l'authentification</h3>
                        </div>
                        <p class="text-sm text-gray-600">
                            Incluez votre clé dans le header:
                            <code class="bg-gray-100 px-1 rounded">Authorization: Bearer YOUR_API_KEY</code>
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center font-bold">3</span>
                            <h3 class="font-medium">Envoyer votre premier email</h3>
                        </div>
                        <p class="text-sm text-gray-600">
                            Utilisez l'endpoint <code class="bg-gray-100 px-1 rounded">POST /v1/send</code>
                            pour envoyer des emails transactionnels.
                        </p>
                    </div>
                </div>

                <!-- Code Example -->
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Exemple cURL</h3>
                    <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-sm overflow-x-auto"><code>curl -X POST https://api.slimail.com/v1/send \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "destinataire@example.com",
    "subject": "Test SliMail",
    "html": "&lt;h1&gt;Bonjour!&lt;/h1&gt;&lt;p&gt;Ceci est un test.&lt;/p&gt;"
  }'</code></pre>
                </div>
            </div>
        </div>

        <!-- Swagger UI Container -->
        <div id="swagger-ui" ref="swaggerContainer" class="max-w-7xl mx-auto"></div>
    </div>
</template>

<style>
/* Override Swagger UI styles */
.swagger-ui .topbar {
    display: none;
}

.swagger-ui .info {
    margin: 30px 0;
}

.swagger-ui .info .title {
    font-size: 2rem;
    font-weight: 700;
}

.swagger-ui .scheme-container {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    box-shadow: none;
}

.swagger-ui .opblock-tag {
    font-size: 1.25rem;
    font-weight: 600;
    border-bottom: 2px solid #e5e7eb;
}

.swagger-ui .opblock {
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.swagger-ui .opblock .opblock-summary {
    border-radius: 8px;
}

.swagger-ui .opblock.opblock-post {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.05);
}

.swagger-ui .opblock.opblock-post .opblock-summary-method {
    background: #10b981;
}

.swagger-ui .opblock.opblock-get {
    border-color: #3b82f6;
    background: rgba(59, 130, 246, 0.05);
}

.swagger-ui .opblock.opblock-get .opblock-summary-method {
    background: #3b82f6;
}

.swagger-ui .btn.execute {
    background: #4f46e5;
    border-color: #4f46e5;
}

.swagger-ui .btn.execute:hover {
    background: #4338ca;
}

.swagger-ui .btn.authorize {
    color: #10b981;
    border-color: #10b981;
}

.swagger-ui .btn.authorize svg {
    fill: #10b981;
}

.swagger-ui .model-box {
    background: #f9fafb;
    border-radius: 8px;
}

.swagger-ui table tbody tr td {
    padding: 12px 8px;
}

.swagger-ui .response-col_status {
    font-weight: 600;
}

.swagger-ui .responses-inner h4 {
    font-size: 0.875rem;
}
</style>

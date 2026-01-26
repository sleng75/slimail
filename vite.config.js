import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        // Increase chunk size warning limit for email editor
        chunkSizeWarningLimit: 600,
        rollupOptions: {
            output: {
                // Manual chunks for better code splitting
                manualChunks: {
                    // GrapesJS is large - separate it into its own chunk
                    'grapesjs': ['grapesjs'],
                    // Vendor chunk for common libraries
                    'vendor': ['vue', '@inertiajs/vue3', 'lodash/debounce'],
                },
            },
        },
    },
});

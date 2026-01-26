<template>
    <div class="loading-container" :class="[sizeClass, { 'overlay': overlay }]">
        <div class="spinner-wrapper">
            <svg class="spinner" viewBox="0 0 50 50">
                <circle
                    class="spinner-track"
                    cx="25"
                    cy="25"
                    r="20"
                    fill="none"
                    stroke-width="4"
                ></circle>
                <circle
                    class="spinner-path"
                    cx="25"
                    cy="25"
                    r="20"
                    fill="none"
                    stroke-width="4"
                ></circle>
            </svg>
            <p v-if="text" class="loading-text">{{ text }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (val) => ['sm', 'md', 'lg', 'xl'].includes(val)
    },
    text: {
        type: String,
        default: ''
    },
    overlay: {
        type: Boolean,
        default: false
    }
});

const sizeClass = computed(() => `size-${props.size}`);
</script>

<style scoped>
.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-container.overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px);
    z-index: 50;
}

.spinner-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.spinner {
    animation: rotate 1.4s linear infinite;
}

.size-sm .spinner { width: 24px; height: 24px; }
.size-md .spinner { width: 40px; height: 40px; }
.size-lg .spinner { width: 56px; height: 56px; }
.size-xl .spinner { width: 72px; height: 72px; }

.spinner-track {
    stroke: #e2e8f0;
}

.spinner-path {
    stroke: #6366f1;
    stroke-linecap: round;
    animation: dash 1.4s ease-in-out infinite;
}

.loading-text {
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
    margin: 0;
}

@keyframes rotate {
    100% {
        transform: rotate(360deg);
    }
}

@keyframes dash {
    0% {
        stroke-dasharray: 1, 150;
        stroke-dashoffset: 0;
    }
    50% {
        stroke-dasharray: 90, 150;
        stroke-dashoffset: -35;
    }
    100% {
        stroke-dasharray: 90, 150;
        stroke-dashoffset: -124;
    }
}
</style>

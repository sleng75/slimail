<script setup>
/**
 * SidebarToggleButton Component
 * Elegant floating button on sidebar edge
 */

import { computed } from 'vue';
import { useSidebar } from '@/Composables/useSidebar';

const sidebar = useSidebar();

// Unwrap refs
const isCollapsed = computed(() => sidebar.isCollapsed.value);
const isHidden = computed(() => sidebar.isHidden.value);

// Chevron rotation based on state
const shouldPointRight = computed(() => isHidden.value || isCollapsed.value);

// Button title/tooltip
const buttonTitle = computed(() => {
    if (isHidden.value) return 'Afficher le menu';
    if (isCollapsed.value) return 'Développer le menu';
    return 'Réduire le menu';
});

const handleClick = () => {
    sidebar.toggleMode();
};
</script>

<template>
    <button
        @click="handleClick"
        :title="buttonTitle"
        :aria-label="buttonTitle"
        class="toggle-btn"
    >
        <span class="toggle-btn-inner">
            <svg
                class="toggle-icon"
                :class="{ 'rotate-180': !shouldPointRight }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                    d="M9 5l7 7-7 7"
                />
            </svg>
        </span>
    </button>
</template>

<style scoped>
.toggle-btn {
    width: 24px;
    height: 48px;
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    outline: none;
}

.toggle-btn-inner {
    width: 20px;
    height: 40px;
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    box-shadow:
        0 1px 3px rgba(0, 0, 0, 0.08),
        0 1px 2px rgba(0, 0, 0, 0.06);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.toggle-btn:hover .toggle-btn-inner {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-color: #fca5a5;
    box-shadow:
        0 4px 6px rgba(220, 38, 38, 0.1),
        0 2px 4px rgba(220, 38, 38, 0.06);
    transform: scale(1.05);
}

.toggle-btn:active .toggle-btn-inner {
    transform: scale(0.95);
}

.toggle-btn:focus-visible .toggle-btn-inner {
    outline: 2px solid #dc2626;
    outline-offset: 2px;
}

.toggle-icon {
    width: 14px;
    height: 14px;
    color: #9ca3af;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.toggle-btn:hover .toggle-icon {
    color: #dc2626;
}

.rotate-180 {
    transform: rotate(180deg);
}
</style>

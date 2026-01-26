<script setup>
/**
 * PageContainer Component
 * Provides consistent page spacing and max-width constraints
 */

import { computed } from 'vue';

const props = defineProps({
    /**
     * Content width constraint
     * - narrow: 768px max (forms, settings)
     * - default: 1280px max (most pages)
     * - wide: 1536px max (dashboards)
     * - full: No max-width
     */
    size: {
        type: String,
        default: 'default',
        validator: (v) => ['narrow', 'default', 'wide', 'full'].includes(v),
    },

    /**
     * Disable horizontal padding
     */
    noPaddingX: {
        type: Boolean,
        default: false,
    },

    /**
     * Disable vertical padding
     */
    noPaddingY: {
        type: Boolean,
        default: false,
    },

    /**
     * Disable all padding
     */
    noPadding: {
        type: Boolean,
        default: false,
    },

    /**
     * Center the content horizontally
     */
    centered: {
        type: Boolean,
        default: true,
    },

    /**
     * Additional classes
     */
    class: {
        type: String,
        default: '',
    },
});

const containerClasses = computed(() => {
    const classes = [];

    // Max-width based on size
    switch (props.size) {
        case 'narrow':
            classes.push('max-w-content-narrow');
            break;
        case 'default':
            classes.push('max-w-content-default');
            break;
        case 'wide':
            classes.push('max-w-content-wide');
            break;
        case 'full':
            // No max-width
            break;
    }

    // Horizontal padding
    if (!props.noPadding && !props.noPaddingX) {
        classes.push('px-page');
    }

    // Vertical padding
    if (!props.noPadding && !props.noPaddingY) {
        classes.push('py-page');
    }

    // Centering
    if (props.centered && props.size !== 'full') {
        classes.push('mx-auto');
    }

    // Full width for inner content
    classes.push('w-full');

    return classes;
});
</script>

<template>
    <div :class="[containerClasses, props.class]">
        <slot />
    </div>
</template>

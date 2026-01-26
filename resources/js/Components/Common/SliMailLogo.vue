<script setup>
/**
 * SliMailLogo Component
 * Displays the SliMail logo with automatic color selection based on background
 */

import { computed } from 'vue';

const props = defineProps({
    /**
     * Logo color variant
     * - 'auto': Automatically select based on background prop
     * - 'white': White logo for dark backgrounds
     * - 'black': Black logo for light backgrounds
     * - 'red': Red logo (brand color)
     * - 'color': Full color logo (if available)
     */
    variant: {
        type: String,
        default: 'auto',
        validator: (v) => ['auto', 'white', 'black', 'red', 'color'].includes(v),
    },

    /**
     * Background context for auto variant selection
     * - 'light': Light background (use black or red logo)
     * - 'dark': Dark background (use white logo)
     * - 'brand': Brand red background (use white logo)
     */
    background: {
        type: String,
        default: 'light',
        validator: (v) => ['light', 'dark', 'brand'].includes(v),
    },

    /**
     * Logo size
     */
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v),
    },

    /**
     * Show text "SliMail" next to logo
     */
    showText: {
        type: Boolean,
        default: true,
    },

    /**
     * Show only icon without any text
     */
    iconOnly: {
        type: Boolean,
        default: false,
    },
});

// Logo source based on variant and background
const logoSrc = computed(() => {
    let selectedVariant = props.variant;

    if (selectedVariant === 'auto') {
        switch (props.background) {
            case 'dark':
            case 'brand':
                selectedVariant = 'white';
                break;
            default:
                selectedVariant = 'red';
        }
    }

    const variantMap = {
        white: '/images/logo-white.svg',
        black: '/images/logo-black.svg',
        red: '/images/logo-red.svg',
        color: '/images/logo.png',
    };

    return variantMap[selectedVariant] || variantMap.red;
});

// Size classes for the logo image
const sizeClasses = computed(() => {
    const sizes = {
        xs: 'h-5 w-5',
        sm: 'h-6 w-6',
        md: 'h-8 w-8',
        lg: 'h-10 w-10',
        xl: 'h-12 w-12',
    };
    return sizes[props.size];
});

// Text size classes
const textSizeClasses = computed(() => {
    const sizes = {
        xs: 'text-sm',
        sm: 'text-base',
        md: 'text-xl',
        lg: 'text-2xl',
        xl: 'text-3xl',
    };
    return sizes[props.size];
});

// Text color based on background
const textColorClasses = computed(() => {
    switch (props.background) {
        case 'dark':
        case 'brand':
            return 'text-white';
        default:
            return 'text-secondary-900';
    }
});

// Accent color for "Mail" part
const accentColorClasses = computed(() => {
    switch (props.background) {
        case 'dark':
            return 'text-primary-400';
        case 'brand':
            return 'text-white/90';
        default:
            return 'text-primary-600';
    }
});
</script>

<template>
    <div class="inline-flex items-center gap-2 group">
        <!-- Logo Icon -->
        <div class="relative flex-shrink-0">
            <img
                :src="logoSrc"
                alt="SliMail"
                :class="[sizeClasses, 'relative z-10 transition-transform duration-200 group-hover:scale-105']"
            />
            <!-- Subtle glow effect on hover -->
            <div
                v-if="background !== 'brand'"
                class="absolute inset-0 bg-primary-500/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            />
        </div>

        <!-- Text -->
        <span
            v-if="showText && !iconOnly"
            :class="[textSizeClasses, textColorClasses, 'font-bold tracking-tight transition-colors duration-200']"
        >
            Sli<span :class="accentColorClasses">Mail</span>
        </span>
    </div>
</template>

import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // Rouge SliMail - Couleur principale du logo
                primary: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5',
                    400: '#f87171',
                    500: '#ef4444',
                    600: '#dc2626',  // Rouge principal du logo
                    700: '#b91c1c',
                    800: '#991b1b',
                    900: '#7f1d1d',
                    950: '#450a0a',
                },
                // Noir/Gris foncé - Couleur secondaire du logo
                secondary: {
                    50: '#f9fafb',
                    100: '#f3f4f6',
                    200: '#e5e7eb',
                    300: '#d1d5db',
                    400: '#9ca3af',
                    500: '#6b7280',
                    600: '#4b5563',
                    700: '#374151',
                    800: '#1f2937',
                    900: '#111827',  // Noir du logo
                    950: '#030712',
                },
                // Couleur d'accent pour les étoiles/highlights
                accent: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['ui-monospace', 'SFMono-Regular', 'SF Mono', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
            },
            spacing: {
                // Sidebar widths
                'sidebar': '16rem',           // 256px - normal
                'sidebar-collapsed': '4.5rem', // 72px - collapsed
                // Header/Footer
                'header': '4rem',             // 64px
                'footer': '3.5rem',           // 56px
                // Page spacing (base - overridden by CSS variables for responsive)
                'page': '1.5rem',             // 24px base
                // Component spacing
                'section': '2rem',            // 32px
                'card': '1.5rem',             // 24px
            },
            maxWidth: {
                'content-narrow': '48rem',    // 768px
                'content-default': '80rem',   // 1280px
                'content-wide': '96rem',      // 1536px
            },
            minHeight: {
                'screen-header': 'calc(100vh - 4rem)',
            },
            backgroundImage: {
                'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                'hero-pattern': 'linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #111827 100%)',
                'sidebar-gradient': 'linear-gradient(180deg, #111827 0%, #0f172a 100%)',
            },
            boxShadow: {
                'sidebar': '4px 0 6px -1px rgba(0, 0, 0, 0.1)',
                'header': '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
                'dropdown': '0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
                'card-hover': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.04)',
                'primary-glow': '0 4px 14px 0 rgba(220, 38, 38, 0.39)',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            animation: {
                'float': 'float 6s ease-in-out infinite',
                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'fade-in': 'fadeIn 0.2s ease-out',
                'slide-up': 'slideUp 0.2s ease-out',
                'slide-down': 'slideDown 0.2s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                fadeIn: {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                slideUp: {
                    from: { opacity: '0', transform: 'translateY(10px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                slideDown: {
                    from: { opacity: '0', transform: 'translateY(-10px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                scaleIn: {
                    from: { opacity: '0', transform: 'scale(0.95)' },
                    to: { opacity: '1', transform: 'scale(1)' },
                },
            },
            transitionDuration: {
                'sidebar': '300ms',
            },
            transitionTimingFunction: {
                'sidebar': 'cubic-bezier(0.4, 0, 0.2, 1)',
            },
            zIndex: {
                'dropdown': '10',
                'sticky': '20',
                'header': '30',
                'sidebar': '40',
                'overlay': '50',
                'modal': '60',
                'popover': '70',
                'tooltip': '80',
                'toast': '90',
                'progress': '100',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};

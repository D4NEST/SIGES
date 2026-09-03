import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Integrar variables CSS personalizadas con Tailwind
                primary: {
                    50: 'var(--color-primary-50, #eff6ff)',
                    100: 'var(--color-primary-100, #dbeafe)',
                    200: 'var(--color-primary-200, #bfdbfe)',
                    DEFAULT: 'var(--color-primary, #2563eb)',
                    light: 'var(--color-primary-light, #3b82f6)',
                    dark: 'var(--color-primary-dark, #1d4ed8)',
                },
                secondary: {
                    50: 'var(--color-secondary-50, #f8fafc)',
                    100: 'var(--color-secondary-100, #f1f5f9)',
                    200: 'var(--color-secondary-200, #e2e8f0)',
                    DEFAULT: 'var(--color-secondary, #64748b)',
                    light: 'var(--color-secondary-light, #94a3b8)',
                    dark: 'var(--color-secondary-dark, #475569)',
                },
                success: {
                    DEFAULT: 'var(--color-success, #10b981)',
                    light: '#d1fae5',
                    dark: '#065f46',
                },
                warning: {
                    DEFAULT: 'var(--color-warning, #f59e0b)',
                    light: '#fef3c7',
                    dark: '#92400e',
                },
                error: {
                    DEFAULT: 'var(--color-error, #ef4444)',
                    light: '#fee2e2',
                    dark: '#991b1b',
                },
                info: {
                    DEFAULT: 'var(--color-info, #3b82f6)',
                    light: '#dbeafe',
                    dark: '#1e40af',
                },
            },
            fontSize: {
                'xs': 'var(--font-size-xs, 0.75rem)',
                'sm': 'var(--font-size-sm, 0.875rem)',
                'base': 'var(--font-size-base, 1rem)',
                'lg': 'var(--font-size-lg, 1.125rem)',
                'xl': 'var(--font-size-xl, 1.25rem)',
                '2xl': 'var(--font-size-2xl, 1.5rem)',
                '3xl': 'var(--font-size-3xl, 1.875rem)',
            },
            spacing: {
                'xs': 'var(--spacing-xs, 0.25rem)',
                'sm': 'var(--spacing-sm, 0.5rem)',
                'md': 'var(--spacing-md, 1rem)',
                'lg': 'var(--spacing-lg, 1.5rem)',
                'xl': 'var(--spacing-xl, 2rem)',
                '2xl': 'var(--spacing-2xl, 3rem)',
                '3xl': 'var(--spacing-3xl, 4rem)',
            },
            borderRadius: {
                'none': 'var(--border-radius-none, 0)',
                'sm': 'var(--border-radius-sm, 0.125rem)',
                'md': 'var(--border-radius-md, 0.375rem)',
                'lg': 'var(--border-radius-lg, 0.5rem)',
                'xl': 'var(--border-radius-xl, 0.75rem)',
                'full': 'var(--border-radius-full, 9999px)',
            },
            boxShadow: {
                'sm': 'var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05))',
                'md': 'var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06))',
                'lg': 'var(--shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05))',
                'dark': 'var(--shadow-dark, 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2))',
            },
            transitionDuration: {
                'fast': 'var(--transition-fast, 150ms)',
                'normal': 'var(--transition-normal, 300ms)',
                'slow': 'var(--transition-slow, 500ms)',
            },
            zIndex: {
                'dropdown': 'var(--z-dropdown, 1000)',
                'sticky': 'var(--z-sticky, 1020)',
                'fixed': 'var(--z-fixed, 1030)',
                'modal-backdrop': 'var(--z-modal-backdrop, 1040)',
                'modal': 'var(--z-modal, 1050)',
                'popover': 'var(--z-popover, 1060)',
                'tooltip': 'var(--z-tooltip, 1070)',
            },
        },
    },

    plugins: [forms],
};

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const token = (name) => `rgb(var(--c-${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Instrument Sans"', ...defaultTheme.fontFamily.sans],
                serif: ['Newsreader', 'Georgia', '"Times New Roman"', 'serif'],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            borderRadius: {
                sm: '6px',
                md: '12px',
                lg: '20px',
            },
            boxShadow: {
                card: 'var(--shadow-card)',
                float: 'var(--shadow-float)',
            },
            colors: {
                // Design-system tokens, by their own names.
                surface: { DEFAULT: token('surface'), raised: token('raised'), sunk: token('sunk') },
                ink: { DEFAULT: token('ink'), muted: token('muted') },
                line: { DEFAULT: token('line'), strong: token('line-strong') },
                scrub: { DEFAULT: token('scrub'), hover: token('scrub-hover'), soft: token('scrub-soft') },
                balm: { DEFAULT: token('balm'), soft: token('balm-soft') },
                dose: { DEFAULT: token('dose'), soft: token('dose-soft'), ink: token('dose-ink') },
                critical: { DEFAULT: token('critical'), soft: token('critical-soft') },

                // The utility families the views already use, remapped onto the tokens
                // so every page follows the theme (day or night) without edits.
                white: token('raised'),
                black: token('ink'),
                gray: {
                    50: token('sunk'),
                    100: token('surface'),
                    200: token('line'),
                    300: token('line-strong'),
                    400: token('muted'),
                    500: token('muted'),
                    600: token('muted'),
                    700: token('ink'),
                    800: token('ink'),
                    900: token('ink'),
                },
                indigo: {
                    50: token('scrub-soft'),
                    100: token('scrub-soft'),
                    400: token('scrub'),
                    500: token('scrub-hover'),
                    600: token('scrub'),
                    700: token('scrub'),
                },
                green: { 600: token('scrub') },
                red: {
                    500: token('critical'),
                    600: token('critical'),
                    700: token('critical'),
                },
            },
        },
    },

    plugins: [forms],
};

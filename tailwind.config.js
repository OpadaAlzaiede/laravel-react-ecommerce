import themes from 'daisyui/theme/object';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: "#f42c37",
                secondary: "#f42c37",
                brandYellow: "#fdc62e",
                brandGreen: "#2dcc6f",
                brandBlue: "#1376f4",
                brandWhite: "#eeeeee"
            }
        },
    },

    plugins: [require('daisyui')],
};

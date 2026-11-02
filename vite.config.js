import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // Font (Plus Jakarta Sans, Inter, Material Symbols) dimuat via
            // <link> Google Fonts di layout — sama dengan mockup PrdAndDesign.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/scan.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

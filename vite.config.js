import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // Bind ke semua interface + HMR nunjuk IP LAN laptop, biar asset (CSS/JS)
        // ke-load saat diakses dari HP di jaringan yang sama. Tanpa ini @vite
        // men-generate URL localhost:5173 yang di HP = HP itu sendiri → halaman polos.
        host: '0.0.0.0',
        hmr: { host: process.env.VITE_DEV_HOST || 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

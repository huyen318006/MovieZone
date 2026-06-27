import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: {
        host: '0.0.0.0',  // Cho phép truy cập từ máy khác trong mạng LAN
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/login.css', 'resources/css/booking/combo.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});

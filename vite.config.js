import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(path.dirname(new URL(import.meta.url).pathname), 'resources/js')
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: '0.0.0.0',
            protocol: 'ws',
        },
        watch: {
            usePolling: true,
        },
    },
});

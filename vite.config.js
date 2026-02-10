import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/ts/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: true, // escucha en 0.0.0.0/:: para evitar issues con [::1]
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
});

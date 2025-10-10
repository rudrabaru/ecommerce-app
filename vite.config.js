import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'Modules/Admin/resources/js/users.js',
                'Modules/Admin/resources/js/providers.js'
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@modules': resolve(__dirname, 'Modules')
        }
    }
});

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import fs from 'fs';

// Function to get all module entry points
const getModuleEntries = () => {
    const modulesPath = path.resolve(__dirname, 'Modules');
    if (!fs.existsSync(modulesPath)) return [];

    return fs.readdirSync(modulesPath)
        .filter(file => fs.statSync(path.join(modulesPath, file)).isDirectory())
        .map(module => `Modules/${module}/Resources/assets/js/app.js`)
        .filter(file => fs.existsSync(path.resolve(__dirname, file)));
};

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Add specific module entries
                ...getModuleEntries()
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@auth': '/Modules/Auth/resources/assets/js'
        },
    },
    optimizeDeps: {
        include: ['@inertiajs/react']
    }
});

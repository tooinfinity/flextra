import { svelte } from '@sveltejs/vite-plugin-svelte'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'
import { run } from 'vite-plugin-run'
import path from 'path';
import fs from 'fs';

// Function to get all module entry points
const getModuleEntries = () => {
    const modulesPath = path.resolve(__dirname, 'Modules');
    if (!fs.existsSync(modulesPath)) return [];

    return fs.readdirSync(modulesPath)
        .filter(file => fs.statSync(path.join(modulesPath, file)).isDirectory())
        .map(module => `Modules/${module}/Resources/assets/js/app.jsx`)
        .filter(file => fs.existsSync(path.resolve(__dirname, file)));
};

export default defineConfig({
    plugins: [
        laravel({
            input:  [
                'resources/js/app.js',
                ...getModuleEntries()
            ],
            refresh: true,
        }),
        run([
            {
                name: 'trail generate routes',
                pattern: 'routes/*.php',
                run: ['php', 'artisan', 'trail:generate'],
            },
            {
                name: 'clear compiled views',
                pattern: 'routes/*.php',
                run: ['php', 'artisan', 'view:clear'],
            },
        ]),
        svelte(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@auth': '/Modules/Auth/resources/assets/js'
        }
    },
    optimizeDeps: {
        include: ['@inertiajs/react']
    }
});

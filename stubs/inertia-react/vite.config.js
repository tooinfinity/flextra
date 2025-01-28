import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
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
            input: [
                'resources/js/app.jsx',
                ...getModuleEntries()
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@auth': '/Modules/{{moduleName}}/resources/assets/js'
        }
    },
    optimizeDeps: {
        include: ['@inertiajs/react']
    }
});

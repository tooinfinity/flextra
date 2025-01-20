import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob([
            './Pages/**/*.tsx',
            '../../Modules/**/Pages/**/*.tsx'
        ]);

        let pageUrl = `./Pages/${name}.tsx`;
        let errorDetails = {
            requestedName: name,
            attemptedPath: pageUrl,
            isModulePage: name.includes('::'),
            timestamp: new Date().toISOString()
        };

        if (errorDetails.isModulePage) {
            const [module, pageLocation] = name.split('::');
            if (!module || !pageLocation) {
                console.error('Module Page Resolution Error:', {
                    ...errorDetails,
                    module,
                    pageLocation,
                    error: 'Invalid module format'
                });
                throw new Error(
                    `Invalid module page format: ${name}\n` +
                    `Expected format: ModuleName::PagePath\n` +
                    `Received: module=${module}, pageLocation=${pageLocation}\n` +
                    `Timestamp: ${errorDetails.timestamp}`
                );
            }
            pageUrl = `../../Modules/${module}/resources/assets/js/Pages/${pageLocation}.tsx`;
            errorDetails.attemptedPath = pageUrl;
        }

        try {
            return resolvePageComponent(pageUrl, pages);
        } catch (error) {
            const availablePages = Object.keys(pages);
            const modulePages = availablePages.filter(p => p.includes('/Modules/'));
            const standardPages = availablePages.filter(p => !p.includes('/Modules/'));
            const suggestedPages = availablePages
                .filter(p => p.toLowerCase().includes(name.toLowerCase()))
                .map(p => `  - ${p}`);

            console.error('Page Resolution Error:', {
                ...errorDetails,
                availableModulePages: modulePages.length,
                availableStandardPages: standardPages.length,
                suggestionsFound: suggestedPages.length
            });

            const errorMessage = [
                `Page not found: ${pageUrl}`,
                `Requested page: ${name}`,
                `Type: ${errorDetails.isModulePage ? 'Module Page' : 'Standard Page'}`,
                `Timestamp: ${errorDetails.timestamp}`,
                '',
                'Standard Pages:',
                ...standardPages.map(p => `  - ${p}`),
                '',
                'Module Pages:',
                ...modulePages.map(p => `  - ${p}`),
                '',
                suggestedPages.length ? [
                    'Similar pages found:',
                    ...suggestedPages
                ].join('\n') : 'No similar pages found.'
            ].join('\n');

            throw new Error(errorMessage);
        }
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

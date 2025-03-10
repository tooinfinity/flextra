import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { route } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) => {
            const pages = import.meta.glob([
                './Pages/**/*.jsx',
                '../../Modules/**/Pages/**/*.jsx'
            ]);

            let pageUrl = `./Pages/${name}.jsx`;
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
                pageUrl = `../../Modules/${module}/resources/assets/js/Pages/${pageLocation}.jsx`;
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
        setup: ({ App, props }) => {
            global.route = (name, params, absolute) =>
                route(name, params, absolute, {
                    ...page.props.ziggy,
                    location: new URL(page.props.ziggy.location),
                });

            return <App {...props} />;
        },
    }),
);

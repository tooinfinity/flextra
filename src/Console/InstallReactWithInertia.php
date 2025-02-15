<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

trait InstallReactWithInertia
{
    /**
     * Install the inertia stack with laravel module.
     */
    public function installModuleInertiaReact(string $moduleName): ?int
    {
        $fileSystem = new Filesystem;
        // Install Laravel Modules Package
        $this->installModuleDependencies();

        // TODO:update composer packages
        // install inertia and laravel Modules
        if (! $this->requireComposerPackages([
            'inertiajs/inertia-laravel:^2.0',
            'tightenco/ziggy:^2.0',
            'laravel/sanctum:^4.0',
        ])) {
            return 1;
        }
        // TODO:update NPM packages versions and configurations
        // install or update NPM packages
        $this->updateNodePackages(fn ($packages) => [
            '@headlessui/react' => '^2.0.0',
            '@inertiajs/react' => '^2.0.0',
            '@tailwindcss/forms' => '^0.5.3',
            '@vitejs/plugin-react' => '^4.2.0',
            'autoprefixer' => '^10.4.12',
            'postcss' => '^8.4.31',
            'tailwindcss' => '^3.2.1',
            'react' => '^18.2.0',
            'react-dom' => '^18.2.0',
        ] + $packages);
        // install or update NPM packages for typescript
        if ($this->option('typescript')) {
            $this->updateNodePackages(fn ($packages) => [
                '@types/node' => '^18.13.0',
                '@types/react' => '^18.0.28',
                '@types/react-dom' => '^18.0.10',
                'typescript' => '^5.0.2',
            ] + $packages);
        }
        // install or update NPM packages for eslint
        if ($this->option('eslint')) {
            $this->updateNodePackages(fn ($packages) => [
                'eslint' => '^8.57.0',
                'eslint-plugin-react' => '^7.34.4',
                'eslint-plugin-react-hooks' => '^4.6.2',
                'eslint-plugin-prettier' => '^5.1.3',
                'eslint-config-prettier' => '^9.1.0',
                'prettier' => '^3.3.0',
                'prettier-plugin-organize-imports' => '^4.0.0',
                'prettier-plugin-tailwindcss' => '^0.6.5',
            ] + $packages);

            if ($this->option('typescript')) {
                $this->updateNodePackages(fn ($packages) => [
                    '@typescript-eslint/eslint-plugin' => '^7.16.0',
                    '@typescript-eslint/parser' => '^7.16.0',
                ] + $packages);

                $this->updateNodeScripts(fn ($scripts) => $scripts + [
                    'lint' => 'eslint resources/js --ext .js,.jsx,.ts,.tsx --ignore-path .gitignore --fix',
                ]);

                copy(__DIR__.'/../../stubs/inertia-react-ts/.eslintrc.json', base_path('.eslintrc.json'));
            } else {
                $this->updateNodeScripts(fn ($scripts) => $scripts + [
                    'lint' => 'eslint resources/js --ext .js,.jsx --ignore-path .gitignore --fix',
                ]);

                copy(__DIR__.'/../../stubs/inertia-react/.eslintrc.json', base_path('.eslintrc.json'));
            }

            copy(__DIR__.'/../../stubs/inertia-common/.prettierrc', base_path('.prettierrc'));
        }

        // Copy backend files
        $this->installAuthBackendFiles($moduleName);

        // Views...
        copy(__DIR__.'/../../stubs/inertia-react/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        @unlink(resource_path('views/welcome.blade.php'));
        @unlink(base_path('Modules/'.$moduleName.'/resources/views/welcome.blade.php'));

        // Components + Pages...
        $fileSystem->ensureDirectoryExists(resource_path('js/Components'));
        $fileSystem->ensureDirectoryExists(resource_path('js/Layouts'));
        $fileSystem->ensureDirectoryExists(resource_path('js/Pages'));

        $fileSystem->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Components'));
        $fileSystem->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
        $fileSystem->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));
        $fileSystem->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/css'));
        copy(__DIR__.'/../../stubs/inertia-module/css/module.css', base_path('Modules/'.$moduleName.'/resources/assets/css/module.css'));

        if ($this->option('typescript')) {
            $fileSystem->delete(base_path('Modules/'.$moduleName.'/resources/assets/js/app.js'));
            copy(__DIR__.'/../../stubs/inertia-module/react-ts/app.tsx', base_path('Modules/'.$moduleName.'/resources/assets/js/app.tsx'));
            $fileSystem->ensureDirectoryExists(resource_path('js/types'));
            $fileSystem->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/types'));
            $fileSystem->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Components', base_path('Modules/'.$moduleName.'/resources/assets/js/Components'));
            $fileSystem->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Layouts', base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
            $fileSystem->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Pages', base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));

            File::copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/types/index.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/index.d.ts'));
            File::copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/types/global.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/global.d.ts'));
            File::copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/types/vite-env.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/vite.d.ts'));
        } else {
            $fileSystem->delete(base_path('Modules/'.$moduleName.'/resources/assets/js/app.js'));
            copy(__DIR__.'/../../stubs/inertia-module/react/app.jsx', base_path('Modules/'.$moduleName.'/resources/assets/js/app.jsx'));
            $fileSystem->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Components', base_path('Modules/'.$moduleName.'/resources/assets/js/Components'));
            $fileSystem->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Layouts', base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
            $fileSystem->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Pages', base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));
        }

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(resource_path('js'))
                ->in(base_path('Modules/'.$moduleName.'/resources/assets/js'))
                ->name(['*.jsx', '*.tsx'])
                ->notName(['Welcome.jsx', 'Welcome.tsx'])
            );
        }

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        if ($this->option('pest')) {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-php/pest-tests/Feature', base_path('tests/Feature'));
        } else {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-php/tests/Feature', base_path('tests/Feature'));
        }

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/inertia-common/resources/css/app.css', resource_path('css/app.css'));
        copy(__DIR__.'/../../stubs/inertia-common/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/inertia-common/tailwind.config.js', base_path('tailwind.config.js'));
        $this->copyFileWithNamespace($moduleName, __DIR__.'/../../stubs/inertia-react/vite.config.js', base_path('vite.config.js'));

        if ($this->option('typescript')) {
            $this->copyFileWithNamespace($moduleName, __DIR__.'/../../stubs/inertia-react-ts/tsconfig.json', base_path('tsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/app.tsx', resource_path('js/app.tsx'));

            if (file_exists(resource_path('js/bootstrap.js'))) {
                rename(resource_path('js/bootstrap.js'), resource_path('js/bootstrap.ts'));
            }

            $this->replaceInFile('"vite build', '"tsc && vite build', base_path('package.json'));
            $this->replaceInFile('.jsx', '.tsx', base_path('vite.config.js'));
            $this->replaceInFile('.js', '.ts', resource_path('views/app.blade.php'));
            $this->replaceInFile('.vue', '.tsx', base_path('tailwind.config.js'));
        } else {
            $this->copyFileWithNamespace($moduleName, __DIR__.'/../../stubs/inertia-common/jsconfig.json', base_path('jsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-react/resources/js/app.jsx', resource_path('js/app.jsx'));

            $this->replaceInFile('.vue', '.jsx', base_path('tailwind.config.js'));
        }

        if (file_exists(resource_path('js/app.js'))) {
            unlink(resource_path('js/app.js'));
        }

        if ($this->option('ssr')) {
            $this->installModuleInertiaReactSsr($moduleName);
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } elseif (file_exists(base_path('deno.lock'))) {
            $this->runCommands(['deno install', 'deno task build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->components->info('Flextra React scaffolding installed successfully.');

        return 0;
    }

    /**
     * Install the Inertia React SSR stack into the application.
     */
    protected function installModuleInertiaReactSsr($moduleName): void
    {
        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/ssr.tsx', resource_path('js/ssr.tsx'));
            $this->replaceInFile("input: 'resources/js/app.tsx',", "input: 'resources/js/app.tsx',".PHP_EOL."            ssr: 'resources/js/ssr.tsx',", base_path('vite.config.js'));
            $this->configureReactHydrateRootForSsr(resource_path('js/app.tsx'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-react/resources/js/ssr.jsx', resource_path('js/ssr.jsx'));
            $this->replaceInFile("input: 'resources/js/app.jsx',", "input: 'resources/js/app.jsx',".PHP_EOL."            ssr: 'resources/js/ssr.jsx',", base_path('vite.config.js'));
            $this->configureReactHydrateRootForSsr(resource_path('js/app.jsx'));
        }

        $this->configureZiggyForSsr($moduleName);

        $this->replaceInFile('vite build', 'vite build && vite build --ssr', base_path('package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('.gitignore'));
    }

    /**
     * Configure the application JavaScript file to utilize hydrateRoot for SSR.
     */
    protected function configureReactHydrateRootForSsr(string $path): void
    {
        $this->replaceInFile(
            <<<'EOT'
            import { createRoot } from 'react-dom/client';
            EOT,
            <<<'EOT'
            import { createRoot, hydrateRoot } from 'react-dom/client';
            EOT,
            $path
        );

        $this->replaceInFile(
            <<<'EOT'
                    const root = createRoot(el);

                    root.render(<App {...props} />);
            EOT,
            <<<'EOT'
                    if (import.meta.env.SSR) {
                        hydrateRoot(el, <App {...props} />);
                        return;
                    }

                    createRoot(el).render(<App {...props} />);
            EOT,
            $path
        );
    }
}

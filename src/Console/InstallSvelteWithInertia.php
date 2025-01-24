<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

trait InstallSvelteWithInertia
{
    /**
     * Install the inertia stack with Laravel module using Svelte.
     */
    public function installModuleInertiaSvelte(string $moduleName): ?int
    {
        // Install Laravel Modules Package
        $this->installModuleDependencies();

        // Install Inertia and Laravel Modules
        if (! $this->requireComposerPackages([
            'inertiajs/inertia-laravel:^2.0',
            'tightenco/ziggy:^2.0',
            'laravel/sanctum:^4.0',
        ])) {
            return 1;
        }

        // install or update NPM packages
        $this->updateNodePackages(fn ($packages) => [
            '@sveltejs/vite-plugin-svelte' => '^5.0.3',
            '@inertiajs/svelte' => '^2.0.3',
            '@tailwindcss/forms' => '^0.5.3',
            'autoprefixer' => '^10.4.20',
            'postcss' => '^8.5.1',
            'tailwindcss' => '^3.4.17',
            'svelte' => '^5.0.0',
            'svelte-check' => '^4.1.4',
            'svelte-portal' => '^2.2.1',
            'svelte-preprocess' => '^6.0.3',
            'svelte-transition' => '^0.0.17',
        ] + $packages);
        // install or update NPM packages for typescript
        if ($this->option('typescript')) {
            $this->updateNodePackages(fn ($packages) => [
                'typescript' => '^5.0.2',
                '@tsconfig/svelte' => '^5.0.2',
            ] + $packages);
        }
        // install or update NPM packages for eslint
        if ($this->option('eslint')) {
            $this->updateNodePackages(fn ($packages) => [
                'eslint' => '^9.18.0',
                'eslint-config-prettier' => '^10.0.1',
                'eslint-plugin-svelte' => '^2.46.1',
                'globals' => '^15.14.0',
                'prettier' => '^3.4.2',
                'prettier-plugin-svelte' => '^3.3.3',
                'prettier-plugin-tailwindcss' => '^0.6.10',
            ] + $packages);

            if ($this->option('typescript')) {
                $this->updateNodePackages(fn ($packages) => [
                    '@typescript-eslint/eslint-plugin' => '^6.19.1',
                    '@typescript-eslint/parser' => '^6.19.1',
                ] + $packages);

                $this->updateNodeScripts(fn ($scripts) => $scripts + [
                    'lint' => 'eslint resources/js --ext .js,.jsx,.ts,.tsx --ignore-path .gitignore --fix',
                ]);

                copy(__DIR__.'/../../stubs/inertia-svelte-ts/eslint.config.js', base_path('eslint.config.js'));
            } else {
                $this->updateNodeScripts(fn ($scripts) => $scripts + [
                    'lint' => 'eslint resources/js --ext .js,.jsx --ignore-path .gitignore --fix',
                ]);

                copy(__DIR__.'/../../stubs/inertia-svelte/eslint.config.js', base_path('eslint.config.js'));
            }

            copy(__DIR__.'/../../stubs/inertia-svelte/.prettierrc', base_path('.prettierrc'));
            copy(__DIR__.'/../../stubs/inertia-svelte/..prettierignore', base_path('..prettierignore'));
        }
        // Copy backend files
        $this->installAuthBackendFiles($moduleName);

        // Views...
        copy(__DIR__.'/../../stubs/inertia-svelte/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        @unlink(resource_path('views/welcome.blade.php'));
        @unlink(base_path('Modules/'.$moduleName.'/resources/views/welcome.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Pages'));

        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Components', resource_path('js/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Layouts', resource_path('js/Layouts'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Pages', resource_path('js/Pages'));

        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Components'));
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/css'));
        copy(__DIR__.'/../../stubs/inertia-module/css/module.css', base_path('Modules/'.$moduleName.'/resources/assets/css/module.css'));

        if ($this->option('typescript')) {
            (new Filesystem)->delete(base_path('Modules/'.$moduleName.'/resources/assets/js/app.js'));
            copy(__DIR__.'/../../stubs/inertia-module/svelte-ts/app.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/app.ts'));
            (new Filesystem)->ensureDirectoryExists(resource_path('js/types'));
            (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/types'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte-ts/resources/js/Components', base_path('Modules/'.$moduleName.'/resources/assets/js/Components'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte-ts/resources/js/Layouts', base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte-ts/resources/js/Pages', base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));

            File::copy(__DIR__.'/../../stubs/inertia-svelte-ts/resources/js/types/index.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/index.d.ts'));
            File::copy(__DIR__.'/../../stubs/inertia-svelte-ts/resources/js/types/global.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/global.d.ts'));
            File::copy(__DIR__.'/../../stubs/inertia-svelte-ts/resources/js/types/vite-env.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/vite.d.ts'));
        } else {
            (new Filesystem)->delete(base_path('Modules/'.$moduleName.'/resources/assets/js/app.js'));
            copy(__DIR__.'/../../stubs/inertia-module/vue/app.js', base_path('Modules/'.$moduleName.'/resources/assets/js/app.js'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Components', base_path('Modules/'.$moduleName.'/resources/assets/js/Components'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Layouts', base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-svelte/resources/js/Pages', base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));
        }

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(resource_path('js'))
                ->in(base_path('Modules/'.$moduleName.'/resources/assets/js'))
                ->name('*.svelte')
                ->notName('Welcome.vue')
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
        copy(__DIR__.'/../../stubs/inertia-svelte/svelte.config.js', base_path('svelte.config.js'));
        copy(__DIR__.'/../../stubs/inertia-svelte/vite.config.js', base_path('vite.config.js'));

        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-svelte-ts/tsconfig.json', base_path('tsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-svelte-ts/resources/js/app.ts', resource_path('js/app.ts'));

            if (file_exists(resource_path('js/app.js'))) {
                unlink(resource_path('js/app.js'));
            }

            if (file_exists(resource_path('js/bootstrap.js'))) {
                rename(resource_path('js/bootstrap.js'), resource_path('js/bootstrap.ts'));
            }

            $this->replaceInFile('"vite build', '"vite build', base_path('package.json'));
            $this->replaceInFile('.js', '.ts', base_path('vite.config.js'));
            $this->replaceInFile('.js', '.ts', resource_path('views/app.blade.php'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-common/jsconfig.json', base_path('jsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-svelte/resources/js/app.js', resource_path('js/app.js'));
        }

        if ($this->option('ssr')) {
            $this->installModuleInertiaSvelteSsr($moduleName);
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
        $this->components->info('Flextra Svelte scaffolding installed successfully.');

        return 0;
    }

    /**
     * Install the Inertia Svelte SSR stack into the application.
     */
    protected function installModuleInertiaSvelteSsr($moduleName): void
    {
        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-svelte-ts/resources/js/ssr.ts', resource_path('js/ssr.ts'));
            $this->replaceInFile("input: 'resources/js/app.ts',", "input: 'resources/js/app.ts',".PHP_EOL."            ssr: 'resources/js/ssr.ts',", base_path('vite.config.js'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-svelte/resources/js/ssr.js', resource_path('js/ssr.js'));
            $this->replaceInFile("input: 'resources/js/app.js',", "input: 'resources/js/app.js',".PHP_EOL."            ssr: 'resources/js/ssr.js',", base_path('vite.config.js'));
        }

        $this->configureZiggyForSsr($moduleName);

        $this->replaceInFile('vite build', 'vite build && vite build --ssr', base_path('package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('.gitignore'));
    }
}

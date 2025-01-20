<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

trait InstallVueWithInertia
{
    /**
     * Install the Inertia Vue stack.
     */
    protected function installModuleInertiaVue(string $moduleName): int
    {
        // Install Module Dependencies first
        $this->installModuleDependencies();

        // Install Inertia and its dependencies...
        if (! $this->requireComposerPackages(['inertiajs/inertia-laravel:^2.0', 'laravel/sanctum:^4.0', 'tightenco/ziggy:^2.0'])) {
            return 1;
        }

        // NPM Packages...
        $this->updateNodePackages(fn ($packages) => [
            '@inertiajs/vue3' => '^2.0.0',
            '@tailwindcss/forms' => '^0.5.3',
            '@vitejs/plugin-vue' => '^5.0.0',
            'autoprefixer' => '^10.4.12',
            'postcss' => '^8.4.31',
            'tailwindcss' => '^3.2.1',
            'vue' => '^3.4.0',
        ] + $packages);

        if ($this->option('typescript')) {
            $this->updateNodePackages(fn ($packages) => [
                'typescript' => '~5.5.3',
                'vue-tsc' => '^2.0.24',
            ] + $packages);
        }

        if ($this->option('eslint')) {
            $this->updateNodePackages(fn ($packages) => [
                'eslint' => '^8.57.0',
                'eslint-plugin-vue' => '^9.23.0',
                '@rushstack/eslint-patch' => '^1.8.0',
                '@vue/eslint-config-prettier' => '^9.0.0',
                'prettier' => '^3.3.0',
                'prettier-plugin-organize-imports' => '^4.0.0',
                'prettier-plugin-tailwindcss' => '^0.6.5',
            ] + $packages);

            if ($this->option('typescript')) {
                $this->updateNodePackages(fn ($packages) => [
                    '@vue/eslint-config-typescript' => '^13.0.0',
                ] + $packages);

                $this->updateNodeScripts(fn ($scripts) => $scripts + [
                    'lint' => 'eslint resources/js --ext .js,.ts,.vue --ignore-path .gitignore --fix',
                ]);

                copy(__DIR__.'/../../stubs/inertia-vue-ts/.eslintrc.cjs', base_path('.eslintrc.cjs'));
            } else {
                $this->updateNodeScripts(fn ($scripts) => $scripts + [
                    'lint' => 'eslint resources/js --ext .js,.vue --ignore-path .gitignore --fix',
                ]);

                copy(__DIR__.'/../../stubs/inertia-vue/.eslintrc.cjs', base_path('.eslintrc.cjs'));
            }

            copy(__DIR__.'/../../stubs/inertia-common/.prettierrc', base_path('.prettierrc'));
        }

        // Copy backend files
        $this->installAuthBackendFiles($moduleName);

        // Views...
        copy(__DIR__.'/../../stubs/inertia-vue/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        @unlink(resource_path('views/welcome.blade.php'));
        @unlink(base_path('Modules/'.$moduleName.'/resources/views/welcome.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Pages'));

        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Components'));
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/css'));
        copy(__DIR__.'/../../stubs/inertia-module/css/module.css', base_path('Modules/'.$moduleName.'/resources/assets/css/module.css'));

        if ($this->option('typescript')) {
            (new Filesystem)->delete(base_path('Modules/'.$moduleName.'/resources/assets/js/app.js'));
            copy(__DIR__.'/../../stubs/inertia-module/vue/app.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/app.ts'));
            (new Filesystem)->ensureDirectoryExists(resource_path('js/types'));
            (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/assets/js/types'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/Components', base_path('Modules/'.$moduleName.'/resources/assets/js/Components'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/Layouts', base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/Pages', base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));

            File::copy(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/types/index.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/index.d.ts'));
            File::copy(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/types/global.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/global.d.ts'));
            File::copy(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/types/vite-env.d.ts', base_path('Modules/'.$moduleName.'/resources/assets/js/types/vite.d.ts'));
        } else {
            (new Filesystem)->delete(base_path('Modules/'.$moduleName.'/resources/assets/js/app.js'));
            copy(__DIR__.'/../../stubs/inertia-module/react/app.js', base_path('Modules/'.$moduleName.'/resources/assets/js/app.js'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Components', base_path('Modules/'.$moduleName.'/resources/assets/s/Components'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Layouts', base_path('Modules/'.$moduleName.'/resources/assets/js/Layouts'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Pages', base_path('Modules/'.$moduleName.'/resources/assets/js/Pages'));
        }

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(resource_path('js'))
                ->in(base_path('Modules/'.$moduleName.'/resources/assets/js'))
                ->name('*.vue')
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
        copy(__DIR__.'/../../stubs/inertia-vue/vite.config.js', base_path('vite.config.js'));

        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-vue-ts/tsconfig.json', base_path('tsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/app.ts', resource_path('js/app.ts'));

            if (file_exists(resource_path('js/app.js'))) {
                unlink(resource_path('js/app.js'));
            }

            if (file_exists(resource_path('js/bootstrap.js'))) {
                rename(resource_path('js/bootstrap.js'), resource_path('js/bootstrap.ts'));
            }

            $this->replaceInFile('"vite build', '"vue-tsc && vite build', base_path('package.json'));
            $this->replaceInFile('.js', '.ts', base_path('vite.config.js'));
            $this->replaceInFile('.js', '.ts', resource_path('views/app.blade.php'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-common/jsconfig.json', base_path('jsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-vue/resources/js/app.js', resource_path('js/app.js'));
        }

        if ($this->option('ssr')) {
            $this->installModuleInertiaVueSsr($moduleName);
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->components->info('Flextra Vue scaffolding installed successfully.');

        return 0;
    }

    /**
     * Install the Inertia Vue SSR stack into the application.
     */
    protected function installModuleInertiaVueSsr($moduleName): void
    {
        $this->updateNodePackages(fn ($packages) => [
            '@vue/server-renderer' => '^3.4.0',
        ] + $packages);

        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/ssr.ts', resource_path('js/ssr.ts'));
            $this->replaceInFile("input: 'resources/js/app.ts',", "input: 'resources/js/app.ts',".PHP_EOL."            ssr: 'resources/js/ssr.ts',", base_path('vite.config.js'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-vue/resources/js/ssr.js', resource_path('js/ssr.js'));
            $this->replaceInFile("input: 'resources/js/app.js',", "input: 'resources/js/app.js',".PHP_EOL."            ssr: 'resources/js/ssr.js',", base_path('vite.config.js'));
        }

        $this->configureZiggyForSsr($moduleName);

        $this->replaceInFile('vite build', 'vite build && vite build --ssr', base_path('package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('.gitignore'));
    }
}

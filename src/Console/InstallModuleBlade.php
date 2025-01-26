<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait InstallModuleBlade
{
    /**
     * Install the Blade Breeze stack.
     */
    protected function installBladeModule(string $moduleName): ?int
    {
        // Install Laravel Modules Package
        $this->installModuleDependencies();
        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@tailwindcss/forms' => '^0.5.2',
                'alpinejs' => '^3.4.2',
                'autoprefixer' => '^10.4.2',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.1.0',
            ] + $packages;
        });

        // delete unwanted blade files from Modules
        (new Filesystem)->deleteDirectory(base_path('Modules/'.$moduleName.'/resources/views'));

        // delete default app.blade.php
        // (new Filesystem)->delete(resource_path('views/app.blade.php'));

        // Controllers...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/blade-module/app/Http/Controllers',
            base_path('Modules/'.$moduleName.'/app/Http/Controllers')
        );

        // Requests...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/blade-module/app/Http/Requests',
            base_path('Modules/'.$moduleName.'/app/Http/Requests')
        );

        // Views...
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/views'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/blade-module/resources/views', base_path('Modules/'.$moduleName.'/resources/views'));

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(base_path('Modules'.$moduleName.'/resources/views'))
                ->name('*.blade.php')
                ->notPath('livewire/welcome/navigation.blade.php')
                ->notName('welcome.blade.php')
            );
        }

        // Components...
        (new Filesystem)->ensureDirectoryExists(app_path('Modules'.$moduleName.'/app/View/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/blade-module/app/View/Components', base_path('Modules'.$moduleName.'/app/View/Components'));

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        // Routes...
        copy(__DIR__.'/../../stubs/blade-module/routes/web.php', base_path('Modules/'.$moduleName.'/routes/web.php'));
        copy(__DIR__.'/../../stubs/blade-module/routes/auth.php', base_path('Modules/'.$moduleName.'/routes/auth.php'));

        // "Dashboard" Route...
        $this->replaceInFile('/home', '/dashboard', base_path('Modules/'.$moduleName.'/resources/views/welcome.blade.php'));
        $this->replaceInFile('Home', 'Dashboard', base_path('Modules/'.$moduleName.'/resources/views/welcome.blade.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/blade-module/tailwind.config.js', base_path('tailwind.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/vite.config.js', base_path('vite.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/resources/css/app.css', resource_path('css/app.css'));
        copy(__DIR__.'/../../stubs/blade-module/resources/js/app.js', resource_path('js/app.js'));

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
        $this->components->info('Flextra Blade for '.$moduleName.' Module scaffolding installed successfully.');
    }
}

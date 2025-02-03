<?php

declare(strict_types=1);

namespace TooInfinity\Flextra\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

trait InstallModuleLivewire
{
    /**
     * Install the Livewire flextra stack.
     */
    protected function installLivewireModule(string $moduleName, $functional): ?int
    {
        // Install Laravel Modules Package
        $this->installModuleDependencies();
        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@tailwindcss/forms' => '^0.5.2',
                'autoprefixer' => '^10.4.2',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.1.0',
            ] + $packages;
        });

        // Install Livewire...
        if (! $this->requireComposerPackages(['livewire/livewire:^3.4', 'livewire/volt:^1.0'])) {
            return 1;
        }

        // Install Volt...
        (new Process([$this->phpBinary(), 'artisan', 'volt:install'], base_path()))
            ->setTimeout(null)
            ->run();

        // Controllers
        $this->copyFileWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/blade-module/app/Http/Controllers/Auth/VerifyEmailController.php',
            base_path('Modules/'.$moduleName.'/app/Http/Controllers/VerifyEmailController.php')
        );

        // Views Livewire common ...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/resources/views/layouts',
            base_path('Modules/'.$moduleName.'/resources/views/layouts')
        );
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/resources/views/components',
            base_path('Modules/'.$moduleName.'/resources/views/components')
        );
        $this->copyFileWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/resources/views/dashboard.blade.php',
            base_path('Modules/'.$moduleName.'/resources/views/dashboard.blade.php')
        );
        $this->copyFileWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/resources/views/profile.blade.php',
            base_path('Modules/'.$moduleName.'/resources/views/profile.blade.php')
        );
        $this->copyFileWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/resources/views/welcome.blade.php',
            base_path('Modules/'.$moduleName.'/resources/views/welcome.blade.php')
        );

        // Livewire Components...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/'.($functional ? 'livewire-functional' : 'livewire')
            .'/resources/views/livewire/layout',
            base_path('Modules/'.$moduleName.'/resources/views/livewire/layout')
        );
        (new Filesystem)->ensureDirectoryExists(base_path('Modules/'.$moduleName.'/resources/views/livewire/pages'));
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/'.($functional ? 'livewire-functional' : 'livewire')
            .'/resources/views/livewire/pages/auth',
            base_path('Modules/'.$moduleName.'/resources/views/livewire/pages/auth')
        );
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/'.($functional ? 'livewire-functional' : 'livewire')
            .'/resources/views/livewire/profile',
            base_path('Modules/'.$moduleName.'/resources/views/livewire/profile')
        );
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/'.($functional ? 'livewire-functional' : 'livewire')
            .'/resources/views/livewire/welcome',
            base_path('Modules/'.$moduleName.'/resources/views/livewire/welcome')
        );

        // Views Components...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/blade-module/resources/views/components',
            base_path('Modules/'.$moduleName.'/resources/views/components')
        );

        // Components...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/blade-module/app/View/Components',
            base_path('Modules/'.$moduleName.'/app/View/Components')
        );

        // Actions...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/app/Livewire/Actions',
            base_path('Modules/'.$moduleName.'/app/Livewire/Actions')
        );

        // Forms...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/app/Livewire/Forms',
            base_path('Modules/'.$moduleName.'/app/Livewire/Forms')
        );

        // Dark mode...
        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(base_path('Modules/'.$moduleName.'/resources/views'))
                ->name('*.blade.php')
                ->notPath('livewire/welcome/navigation.blade.php')
                ->notName('welcome.blade.php')
            );
        }

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        // Routes...
        $this->copyModuleFilesWithNamespace(
            $moduleName,
            __DIR__.'/../../stubs/livewire-common/routes',
            base_path('Modules/'.$moduleName.'/routes')
        );
        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/blade-module/tailwind.config.js', base_path('tailwind.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/vite.config.js', base_path('vite.config.js'));
        copy(__DIR__.'/../../stubs/blade-module/resources/css/app.css', resource_path('css/app.css'));

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lock')) || file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } elseif (file_exists(base_path('deno.lock'))) {
            $this->runCommands(['deno install', 'deno task build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->components->info('Flextra Modules '.$moduleName.'  Livewire scaffolding installed successfully.');

        return 0;
    }
}
